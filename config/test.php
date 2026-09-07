<?php

require 'supabase.php';
require_once __DIR__ . '/api.php';


if (isset($_GET['test_supabase'])) {
    header('Content-Type: application/json');

    echo json_encode(testSupabaseConnection(), JSON_PRETTY_PRINT);
    exit;
}
$schoolYear = '2026-2027';
$interns    = getInternsBySchoolYear($schoolYear);
echo json_encode([
    'success' => true,
    'interns' => $interns
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);