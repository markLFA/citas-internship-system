<?php
/*
|--------------------------------------------------------------------------
| SUPABASE CONFIGURATION
|--------------------------------------------------------------------------
*/

define('SUPABASE_URL', 'https://kqstxnydtzcpmlexwhcx.supabase.co');
define('SUPABASE_SERVICE_ROLE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imtxc3R4bnlkdHpjcG1sZXh3aGN4Iiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc4ODcwMjg1OSwiZXhwIjoyMTA0Mjc4ODU5fQ.a_BmnyAn4F37s5rxuolSNHp7oTOlzH5PJY-Fx_QRRCE');
define('SUPABASE_BUCKET', 'intern-files');


// =========================================================
// UPLOAD FILE TO SUPABASE STORAGE
// =========================================================

function uploadToSupabase(
    string $localFile,
    string $storagePath,
    string $mimeType
): array {

    if (!file_exists($localFile)) {
        return [
            'success' => false,
            'message' => 'Temporary upload file does not exist.'
        ];
    }

    $fileContents = file_get_contents($localFile);

    if ($fileContents === false) {
        return [
            'success' => false,
            'message' => 'Unable to read uploaded file.'
        ];
    }

    $uploadUrl =
        rtrim(SUPABASE_URL, '/') .
        '/storage/v1/object/' .
        SUPABASE_BUCKET .
        '/' .
        $storagePath;

    $ch = curl_init($uploadUrl);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $fileContents,
        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . SUPABASE_SERVICE_ROLE_KEY,
            'apikey: ' . SUPABASE_SERVICE_ROLE_KEY,
            'Content-Type: ' . $mimeType,
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

    if ($response === false || $curlError) {

        error_log(
            'Supabase CURL error: ' . $curlError
        );

        return [
            'success' => false,
            'message' => 'Unable to connect to Supabase Storage.'
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
            'message' => 'Supabase failed to upload the file.'
        ];
    }

    return [
        'success' => true,
        'message' => 'File uploaded successfully.',
        'path' => $storagePath
    ];
}


// =========================================================
// DELETE FILE FROM SUPABASE STORAGE
// =========================================================

function deleteFromSupabase(
    string $storagePath
): bool {

    if (empty($storagePath)) {
        return false;
    }

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
            'Authorization: Bearer ' . SUPABASE_SERVICE_ROLE_KEY,
            'apikey: ' . SUPABASE_SERVICE_ROLE_KEY
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