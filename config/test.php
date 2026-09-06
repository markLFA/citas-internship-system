<?php

require 'supabase.php';

if (isset($_GET['test_supabase'])) {
    header('Content-Type: application/json');

    echo json_encode(testSupabaseConnection(), JSON_PRETTY_PRINT);
    exit;
}