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


?>
