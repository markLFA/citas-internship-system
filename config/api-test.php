<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';


header('Content-Type: application/json; charset=utf-8');
echo "USER " . $_SESSION['user']['name'] . " (ID: " . $_SESSION['user']['id'] . ", ROLE: " . $_SESSION['user']['role'] . ")\n";
function setReportStatus(): void
{

    $status = "approved"
    $id = 27
    $pdo = getDB();
    header('Content-Type: application/json');

    try {


        $allowedStatuses = [
            'pending',
            'approved',
            'rejected'
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            throw new Exception('Invalid status.');
        }

        // Check if report exists
        $checkStmt = $pdo->prepare("
            SELECT id
            FROM weekly_reports
            WHERE id = :id
            LIMIT 1
        ");

        $checkStmt->execute([
            ':id' => $reportId
        ]);

        $report = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$report) {
            throw new Exception('Report not found.');
        }

        // Update status
        $stmt = $pdo->prepare("
            UPDATE weekly_reports
            SET
                status = :status,
                updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            ':status' => $status,
            ':id'     => $reportId
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Report status updated successfully.'
        ]);

    } catch (Throwable $e) {

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}
try {
    echo json_encode([
        'success' => true,
        'data'    => setReportStatus()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}






function uploadInternDocument(int $internId, string $type, array $fileMeta, string $notes, ?int $coordinatorId): array
{
    $pdo = getDB();

    // 1. Initial server-side PHP upload array validation check
    if (!isset($fileMeta['error']) || $fileMeta['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the server upload limit configuration.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the HTML form directive limit.',
            UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk permissions.',
        ];
        $errMsg = $errorMessages[$fileMeta['error']] ?? 'Unknown upload error occurred.';
        return ['success' => false, 'message' => $errMsg];
    }

    // 2. File type validation checks
    $allowedExts = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];
    $info = pathinfo($fileMeta['name']);
    $ext = strtolower($info['extension'] ?? '');

    if (!in_array($ext, $allowedExts)) {
        return ['success' => false, 'message' => 'Invalid file type. Extensions allowed: ' . implode(', ', $allowedExts)];
    }

    // 3. File size validation check (10MB Limit)
    if ($fileMeta['size'] > 10 * 1024 * 1024) { 
        return ['success' => false, 'message' => 'File exceeds maximum 10 megabyte boundary limit.'];
    }

    // 4. Setup absolute destination storage path in the true account ROOT directory
    // dirname(..., 2) steps out of 'public_html' and out of 'palegoldenrod-raven-703625.hostingersite.com'
    $accountRoot = dirname($_SERVER['DOCUMENT_ROOT'], 2); 
    $uploadDir = $accountRoot . '/uploads/';

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            error_log("Failed to create uploads directory at the true root: " . $uploadDir);
            return ['success' => false, 'message' => 'Server failed to initialize storage directory folder in root.'];
        }
    }

    // Generate unique tokenized filename
    $uniqueName = 'doc_' . uniqid('', true) . '.' . $ext;
    $targetPath = $uploadDir . $uniqueName;

    // Move file to private root destination
    if (!move_uploaded_file($fileMeta['tmp_name'], $targetPath)) {
        error_log("File upload failed to write to target root path: " . $targetPath);
        return ['success' => false, 'message' => 'Failed to write files. Check your root folder permissions on Hostinger.'];
    }

    try {
        // 5. Check for preexisting submissions of this document type
        $checkSql = "SELECT id, file_path FROM intern_documents WHERE intern_id = :intern_id AND document_type = :type";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([':intern_id' => $internId, ':type' => $type]);
        $existingRow = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingRow) {
            // Delete the older file from the root folder so it doesn't leave disk garbage
            if (!empty($existingRow['file_path']) && file_exists($uploadDir . $existingRow['file_path'])) {
                @unlink($uploadDir . $existingRow['file_path']);
            }

            // Update existing record, reset status to pending, and update coordinator tracking identifier
            $sql = "UPDATE intern_documents 
                    SET file_path = :file_path, 
                        file_name = :file_name, 
                        notes = :notes, 
                        status = 'pending', 
                        feedback = NULL, 
                        coordinator_id = :coordinator_id, 
                        reviewed_at = NULL 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':file_path'      => $uniqueName,
                ':file_name'      => $fileMeta['name'],
                ':notes'          => empty($notes) ? null : $notes,
                ':coordinator_id' => $coordinatorId,
                ':id'             => $existingRow['id']
            ]);
        } else {
            // Create a brand new submission record entry row with assigned coordinator ID linked
            $sql = "INSERT INTO intern_documents (intern_id, document_type, file_path, file_name, notes, status, coordinator_id) 
                    VALUES (:intern_id, :type, :file_path, :file_name, :notes, 'pending', :coordinator_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':intern_id'      => $internId,
                ':type'           => $type,
                ':file_path'      => $uniqueName,
                ':file_name'      => $fileMeta['name'],
                ':notes'          => empty($notes) ? null : $notes,
                ':coordinator_id' => $coordinatorId
            ]);
        }

        return ['success' => true, 'message' => 'Document submitted successfully to root storage!'];

    } catch (PDOException $e) {
        error_log("Database error in uploadInternDocument: " . $e->getMessage());
        return ['success' => false, 'message' => 'A backend application storage failure occurred.'];
    }
}
