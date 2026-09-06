<?php
/*
|--------------------------------------------------------------------------
| SUPABASE CONFIGURATION
|--------------------------------------------------------------------------
*/

define('SUPABASE_URL', 'https://kqstxnydtzcpmlexwhcx.supabase.co');
define('SUPABASE_SERVICE_ROLE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imtxc3R4bnlkdHpjcG1sZXh3aGN4Iiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc4ODcwMjg1OSwiZXhwIjoyMTA0Mjc4ODU5fQ.a_BmnyAn4F37s5rxuolSNHp7oTOlzH5PJY-Fx_QRRCE');
define('SUPABASE_BUCKET', 'intern-files');



/*
|--------------------------------------------------------------------------
| UPLOAD INTERN DOCUMENT
|--------------------------------------------------------------------------
*/

function uploadInternDocument(
    int $internId,
    string $type,
    array $fileMeta,
    string $notes,
    ?int $coordinatorId
): array {

    $pdo = getDB();

    /*
     * 1. Check upload
     */
    if (!isset($fileMeta['error'])) {
        return [
            'success' => false,
            'message' => 'No upload information received.'
        ];
    }

    if ($fileMeta['error'] !== UPLOAD_ERR_OK) {

        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the allowed form limit.',
            UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on the server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write the uploaded file to disk.'
        ];

        return [
            'success' => false,
            'message' => $errorMessages[$fileMeta['error']]
                ?? 'Unknown upload error occurred.'
        ];
    }


    /*
     * 2. Validate extension
     */

    $allowedExts = [
        'pdf',
        'doc',
        'docx',
        'png',
        'jpg',
        'jpeg'
    ];

    $originalName = $fileMeta['name'] ?? '';

    $extension = strtolower(
        pathinfo($originalName, PATHINFO_EXTENSION)
    );

    if (!in_array($extension, $allowedExts, true)) {
        return [
            'success' => false,
            'message' => 'Invalid file type. Allowed: ' .
                implode(', ', $allowedExts)
        ];
    }


    /*
     * 3. Validate file size
     */

    if (($fileMeta['size'] ?? 0) > 10 * 1024 * 1024) {
        return [
            'success' => false,
            'message' => 'File exceeds the maximum 10 MB limit.'
        ];
    }


    /*
     * 4. Generate unique filename
     */

    $uniqueName =
        'doc_' .
        bin2hex(random_bytes(16)) .
        '.' .
        $extension;


    /*
     * 5. Supabase storage path
     *
     * Example:
     *
     * documents/25/doc_abc123.pdf
     */

    $storagePath =
        'documents/' .
        $internId .
        '/' .
        $uniqueName;


    /*
     * 6. Read uploaded file
     */

    $fileContents = file_get_contents(
        $fileMeta['tmp_name']
    );

    if ($fileContents === false) {
        return [
            'success' => false,
            'message' => 'Unable to read the uploaded file.'
        ];
    }


    /*
     * 7. Upload to Supabase
     */

    $uploadUrl =
        rtrim(SUPABASE_URL, '/') .
        '/storage/v1/object/' .
        SUPABASE_BUCKET .
        '/' .
        $storagePath;

    $mimeType =
        $fileMeta['type']
        ?? 'application/octet-stream';

    $ch = curl_init($uploadUrl);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $fileContents,
        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' .
                SUPABASE_SERVICE_ROLE_KEY,

            'apikey: ' .
                SUPABASE_SERVICE_ROLE_KEY,

            'Content-Type: ' .
                $mimeType,

            'x-upsert: false'
        ],

        CURLOPT_TIMEOUT => 120
    ]);

    $response = curl_exec($ch);

    $httpCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    $curlError = curl_error($ch);

    curl_close($ch);


    /*
     * 8. Check Supabase result
     */

    if ($response === false || $curlError) {

        error_log(
            'Supabase upload error: ' .
            $curlError
        );

        return [
            'success' => false,
            'message' => 'Unable to connect to Supabase.'
        ];
    }

    if ($httpCode < 200 || $httpCode >= 300) {

        error_log(
            'Supabase upload failed. HTTP ' .
            $httpCode .
            ' Response: ' .
            $response
        );

        return [
            'success' => false,
            'message' => 'Supabase failed to store the file.'
        ];
    }


    /*
     * 9. Save path in MySQL
     */

    try {

        $checkSql = "
            SELECT id, file_path
            FROM intern_documents
            WHERE intern_id = :intern_id
              AND document_type = :type
            LIMIT 1
        ";

        $checkStmt = $pdo->prepare($checkSql);

        $checkStmt->execute([
            ':intern_id' => $internId,
            ':type' => $type
        ]);

        $existingRow =
            $checkStmt->fetch(PDO::FETCH_ASSOC);


        /*
         * Existing document
         */

        if ($existingRow) {

            /*
             * Delete old Supabase file
             */

            if (!empty($existingRow['file_path'])) {
                deleteSupabaseFile(
                    $existingRow['file_path']
                );
            }


            $sql = "
                UPDATE intern_documents
                SET
                    file_path = :file_path,
                    file_name = :file_name,
                    notes = :notes,
                    status = 'pending',
                    feedback = NULL,
                    coordinator_id = :coordinator_id,
                    reviewed_at = NULL
                WHERE id = :id
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':file_path' =>
                    $storagePath,

                ':file_name' =>
                    $originalName,

                ':notes' =>
                    empty($notes)
                        ? null
                        : $notes,

                ':coordinator_id' =>
                    $coordinatorId,

                ':id' =>
                    $existingRow['id']
            ]);

        }

        /*
         * New document
         */

        else {

            $sql = "
                INSERT INTO intern_documents
                (
                    intern_id,
                    document_type,
                    file_path,
                    file_name,
                    notes,
                    status,
                    coordinator_id
                )
                VALUES
                (
                    :intern_id,
                    :type,
                    :file_path,
                    :file_name,
                    :notes,
                    'pending',
                    :coordinator_id
                )
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':intern_id' =>
                    $internId,

                ':type' =>
                    $type,

                ':file_path' =>
                    $storagePath,

                ':file_name' =>
                    $originalName,

                ':notes' =>
                    empty($notes)
                        ? null
                        : $notes,

                ':coordinator_id' =>
                    $coordinatorId
            ]);
        }


        return [
            'success' => true,
            'message' => 'Document uploaded successfully.',
            'file_path' => $storagePath
        ];

    } catch (PDOException $e) {

        /*
         * Database failed after Supabase upload.
         * Remove the orphaned Supabase file.
         */

        deleteSupabaseFile($storagePath);

        error_log(
            'Database error: ' .
            $e->getMessage()
        );

        return [
            'success' => false,
            'message' =>
                'Database error while saving document.'
        ];
    }
}


/*
|--------------------------------------------------------------------------
| DELETE SUPABASE FILE
|--------------------------------------------------------------------------
*/

function deleteSupabaseFile(
    string $storagePath
): bool {

    $deleteUrl =
        rtrim(SUPABASE_URL, '/') .
        '/storage/v1/object/' .
        SUPABASE_BUCKET .
        '/' .
        $storagePath;

    $ch = curl_init($deleteUrl);

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' .
                SUPABASE_SERVICE_ROLE_KEY,

            'apikey: ' .
                SUPABASE_SERVICE_ROLE_KEY
        ],

        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);

    $httpCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    curl_close($ch);

    return (
        $response !== false &&
        $httpCode >= 200 &&
        $httpCode < 300
    );
}