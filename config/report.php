<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/supabase.php';
/**
 * Updates an existing pending weekly report and manages optional file updates.
 *
 * @param PDO   $pdo          Database connection
 * @param int   $userId       ID of the authenticated user
 * @param int   $reportId     ID of the report to update
 * @param string $weekLabel   Updated week label
 * @param string $weekStart   Updated week start date (Y-m-d)
 * @param string $description Updated description
 * @param array $filesToDelete List of file IDs in weekly_report_files to delete
 * @param array $newFiles     Raw $_FILES['files'] array for new attachments
 * @return array Response array containing status and message
 */
function updateReport(
    int $userId,
    int $reportId,
    string $weekLabel,
    string $weekStart,
    string $description = '',
    array $filesToDelete = [],
    array $newFiles = []
): array {
    $pdo = getDB();
    if ($reportId <= 0) {
        return ['success' => false, 'error' => 'Invalid report ID.'];
    }

    $weekLabel = trim($weekLabel);
    $weekStart = trim($weekStart);
    $description = trim($description);

    if ($weekLabel === '') {
        return ['success' => false, 'error' => 'Week label is required.'];
    }

    $dateObj = DateTime::createFromFormat('Y-m-d', $weekStart);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $weekStart) {
        return ['success' => false, 'error' => 'Invalid week start date.'];
    }

    // Verify ownership and status
    $stmt = $pdo->prepare("
        SELECT id 
        FROM weekly_reports 
        WHERE id = ? AND intern_id = ? AND status = 'pending' 
        LIMIT 1
    ");
    $stmt->execute([$reportId, $userId]);
    if (!$stmt->fetch()) {
        return ['success' => false, 'error' => 'Report not found, access denied, or report is not editable.'];
    }

    // Validate new files if attached
    $validatedNewFiles = [];
    if (!empty($newFiles['name'][0])) {
        $fileCount = count($newFiles['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            $name = $newFiles['name'][$i] ?? '';
            $tmp  = $newFiles['tmp_name'][$i] ?? '';
            $size = (int) ($newFiles['size'][$i] ?? 0);
            $err  = (int) ($newFiles['error'][$i] ?? UPLOAD_ERR_NO_FILE);

            if ($err !== UPLOAD_ERR_OK) {
                return ['success' => false, 'error' => '"' . $name . '" failed to upload.'];
            }

            if (empty($tmp) || !is_uploaded_file($tmp)) {
                return ['success' => false, 'error' => '"' . $name . '" could not be verified.'];
            }

            if ($size > MAX_BYTES) {
                return ['success' => false, 'error' => '"' . $name . '" exceeds 10 MB limit.'];
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_EXTS, true)) {
                return ['success' => false, 'error' => 'File type "' . $ext . '" is not allowed.'];
            }

            $mime = 'application/octet-stream';
            if (function_exists('finfo_open')) {
                $fi = finfo_open(FILEINFO_MIME_TYPE);
                if ($fi !== false) {
                    $detectedMime = finfo_file($fi, $tmp);
                    finfo_close($fi);
                    if ($detectedMime) {
                        $mime = $detectedMime;
                    }
                }
            } elseif (!empty($newFiles['type'][$i])) {
                $mime = $newFiles['type'][$i];
            }

            try {
                $stored = 'rpt_' . bin2hex(random_bytes(16)) . '.' . $ext;
            } catch (Throwable $e) {
                return ['success' => false, 'error' => 'Failed to generate secure filename.'];
            }

            $validatedNewFiles[] = [
                'original' => $name,
                'tmp'      => $tmp,
                'size'     => $size,
                'mime'     => $mime,
                'stored'   => $stored
            ];
        }
    }

    $uploadedSupabaseFiles = [];

    try {
        $pdo->beginTransaction();

        // 1. Update text metadata
        $updateStmt = $pdo->prepare("
            UPDATE weekly_reports 
            SET week_label = ?, week_start = ?, description = ?
            WHERE id = ? AND intern_id = ?
        ");
        $updateStmt->execute([
            $weekLabel,
            $weekStart,
            $description !== '' ? $description : null,
            $reportId,
            $userId
        ]);

        // 2. Process requested file deletions
        if (!empty($filesToDelete)) {
            $inClause = implode(',', array_fill(0, count($filesToDelete), '?'));
            $params = array_merge([$reportId], array_map('intval', $filesToDelete));

            $fetchDelFiles = $pdo->prepare("
                SELECT id, file_path 
                FROM weekly_report_files 
                WHERE report_id = ? AND id IN ($inClause)
            ");
            $fetchDelFiles->execute($params);
            $filesToRemove = $fetchDelFiles->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($filesToRemove)) {
                $deleteDbStmt = $pdo->prepare("DELETE FROM weekly_report_files WHERE id = ?");
                foreach ($filesToRemove as $f) {
                    $deleteDbStmt->execute([$f['id']]);
                    deleteFromSupabase($f['file_path']);
                }
            }
        }

        // 3. Upload new files to Supabase and save DB records
        $savedFiles = [];
        foreach ($validatedNewFiles as $file) {
            $storagePath = 'reports/' . $userId . '/' . $file['stored'];

            $uploadResult = uploadToSupabase($file['tmp'], $storagePath, $file['mime']);
            if (empty($uploadResult['success'])) {
                throw new RuntimeException($uploadResult['message'] ?? 'Failed to upload to Supabase.');
            }

            $uploadedSupabaseFiles[] = $storagePath;

            $insertFileStmt = $pdo->prepare("
                INSERT INTO weekly_report_files (report_id, file_path, file_name, file_size, mime_type)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertFileStmt->execute([
                $reportId,
                $storagePath,
                $file['original'],
                $file['size'],
                $file['mime']
            ]);

            $savedFiles[] = [
                'file_name' => $file['original'],
                'file_size' => $file['size']
            ];
        }

        $pdo->commit();

        return [
            'success'   => true,
            'message'   => 'Report updated successfully.',
            'report_id' => $reportId,
            'new_files' => $savedFiles
        ];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Clean up any files uploaded during this failed request
        foreach ($uploadedSupabaseFiles as $storagePath) {
            deleteFromSupabase($storagePath);
        }

        error_log('updateReport error: ' . $e->getMessage());

        return [
            'success' => false,
            'error'   => 'Update failed: ' . $e->getMessage()
        ];
    }
}

/**
 * Deletes a weekly report and all associated files from Database and Supabase.
 *
 * @param PDO $pdo      Database connection
 * @param int $userId   ID of the authenticated user
 * @param int $reportId ID of the report to delete
 * @return array Response array containing status and message
 */
function deleteReport( int $userId, int $reportId): array
{
    $pdo = getDB();
    if ($reportId <= 0) {
        return ['success' => false, 'error' => 'Invalid report ID.'];
    }

    try {
        // Verify ownership and status
        $stmt = $pdo->prepare("
            SELECT id 
            FROM weekly_reports 
            WHERE id = ? AND intern_id = ? AND status = 'pending' 
            LIMIT 1
        ");
        $stmt->execute([$reportId, $userId]);

        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => 'Report not found, access denied, or report cannot be deleted.'];
        }

        // Fetch file paths prior to DB removal
        $filesStmt = $pdo->prepare("SELECT file_path FROM weekly_report_files WHERE report_id = ?");
        $filesStmt->execute([$reportId]);
        $files = $filesStmt->fetchAll(PDO::FETCH_ASSOC);

        $pdo->beginTransaction();

        // Remove associated database records
        $delFilesStmt = $pdo->prepare("DELETE FROM weekly_report_files WHERE report_id = ?");
        $delFilesStmt->execute([$reportId]);

        $delReportStmt = $pdo->prepare("DELETE FROM weekly_reports WHERE id = ? AND intern_id = ?");
        $delReportStmt->execute([$reportId, $userId]);

        $pdo->commit();

        // Delete underlying Supabase files after successful DB transaction
        foreach ($files as $f) {
            if (!empty($f['file_path'])) {
                if (!deleteFromSupabase($f['file_path'])) {
                    error_log('Failed to clean up Supabase file during report deletion: ' . $f['file_path']);
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Report and associated files deleted successfully.'
        ];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('deleteReport error: ' . $e->getMessage());

        return [
            'success' => false,
            'error'   => 'Deletion failed: ' . $e->getMessage()
        ];
    }
}