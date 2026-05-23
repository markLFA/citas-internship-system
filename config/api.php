<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require 'functions.php';

// Check if this is a multipart/form-data request (File Uploads)
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $data = $_POST; // Read text fields like notes or document_type from $_POST
} 
// Fallback to traditional raw input reading for standard JSON payloads
else {
    $data = json_decode(file_get_contents("php://input"), true) ?? [];
    $action = $data['action'] ?? '';
}

switch ($action) {
    case 'getSessionUser':
        echo json_encode(getSessionUser());
        break;
    case 'logout':
        logout();
        break;
    case 'getInternProfile':
        echo json_encode(getInternProfile());
        break;
    case 'getAllInternData':
        echo json_encode(getAllInternData());
        break;
    case 'getPendingInterns':
        echo json_encode(getPendingInterns());
        break;
    case 'approavePendingIntern':
        $internId = $data['internId'] ?? null;
        if ($internId) {
            approvePendingIntern($internId);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Missing internId']);
        }
        break;
    case 'updateInternProfile':
        updateInternProfile($data);
        break;
    case 'submitWeeklyReport':
        submitWeeklyReport();
        break;
    case 'getInternReports':
        echo json_encode(getInternReports());
        break;
    case 'getCoordinatorReports':
        if (($_SESSION['user']['role'] ?? '') !== 'coordinator') {
            echo json_encode(['error' => 'Unauthorized access']);
            break;
        }

        $coordinatorId = $_SESSION['user']['id'] ?? null;
        if ($coordinatorId) {
            echo json_encode(getReportsByCoordinator($coordinatorId));
        } else {
            echo json_encode(['error' => 'Session expired or missing coordinator ID']);
        }
        break;
    case 'updateWeeklyReportStatus':
        if (($_SESSION['user']['role'] ?? '') !== 'coordinator') {
            echo json_encode(['error' => 'Unauthorized access']);
            break;
        }

        $reportId   = (int)($data['reportId'] ?? 0);
        $status     = $data['status'] ?? '';
        $feedback   = $data['feedback'] ?? '';
        $reviewerId = $_SESSION['user']['id'] ?? null;

        if (!$reportId || empty($status)) {
            echo json_encode(['success' => false, 'message' => 'Missing reportId or status']);
        } else {
            echo json_encode(updateWeeklyReportStatus($reportId, $status, $feedback, $reviewerId));
        }
        break;
    case 'getCoordinatorInternDatas':
        echo json_encode(getCoordinatorInternDatas());
        break;
    case 'setCurrentPage':
        $page = $data['page'] ?? null;
        echo json_encode(setCurrentPage($page));
        break;
    case 'getCurrentPage':
        echo json_encode(getCurrentPage());
        break;
    case 'getAnnouncements':
        echo json_encode(getAnnouncements());
        break;
    case 'addAnnouncement':
        $title = $data['title'] ?? '';
        $body = $data['body'] ?? '';
        $isPinned = $data['isPinned'] ?? 0;

        if (empty($title) || empty($body)) {
            echo json_encode(['success' => false, 'message' => 'Title and Body are required.']);
        } else {
            echo json_encode(addAnnouncement($title, $body, $isPinned));
        }
        break;
    case 'updateAnnouncement':
        echo json_encode(updateAnnouncement(
            $data['id'], 
            $data['title'], 
            $data['body'], 
            $data['isPinned']
        ));
        break;
    case 'deleteAnnouncement':
        echo json_encode(deleteAnnouncement($data['id']));
        break;
    // ── Admin actions ──────────────────────────────────────────
    case 'getSystemStats':
        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            echo json_encode(['error' => 'Unauthorized']); break;
        }
        echo json_encode(getSystemStats());
        break;
    case 'getAllCoordinators':
        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            echo json_encode(['error' => 'Unauthorized']); break;
        }
        echo json_encode(getAllCoordinators());
        break;
    case 'approveCoordinator':
        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            echo json_encode(['error' => 'Unauthorized']); break;
        }
        $id = (int)($data['coordinatorId'] ?? 0);
        echo json_encode($id ? approveCoordinator($id) : ['success'=>false,'error'=>'Missing coordinatorId']);
        break;
    case 'deactivateCoordinator':
        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            echo json_encode(['error' => 'Unauthorized']); break;
        }
        $id = (int)($data['coordinatorId'] ?? 0);
        echo json_encode($id ? deactivateCoordinator($id) : ['success'=>false,'error'=>'Missing coordinatorId']);
        break;
    case 'deleteCoordinator':
        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            echo json_encode(['error' => 'Unauthorized']); break;
        }
        $id = (int)($data['coordinatorId'] ?? 0);
        echo json_encode($id ? deleteCoordinator($id) : ['success'=>false,'error'=>'Missing coordinatorId']);
        break;
    // ── Attendance actions ────────────────────────────────────
    case 'timeIn':
        echo json_encode(timeIn($data));
        break;
    case 'timeOut':
        echo json_encode(timeOut());
        break;
    case 'getAttendanceLogs':
        echo json_encode(getAttendanceLogs());
        break;
    case 'getTodayLog':
        echo json_encode(getTodayLog() ?? (object)[]);
        break;
    case 'updateTimeLog':
        echo json_encode(updateTimeLog($data));
        break;
    // ── Document Checklist actions ────────────────────────────
    case 'getInternDocuments':
        $internId = $_SESSION['user']['id'] ?? null;
        if ($internId) {
            echo json_encode(getInternDocuments($internId));
        } else {
            echo json_encode(['error' => 'Unauthenticated session status.']);
        }
        break;
    case 'uploadInternDocument':
        $internId = $_SESSION['user']['id'] ?? null;
        if (!$internId) {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please re-authenticate.']);
            break;
        }

        $type          = $data['document_type'] ?? '';
        $notes         = $data['notes'] ?? '';
        $coordinatorId = !empty($data['coordinator_id']) ? (int)$data['coordinator_id'] : null;
        $file          = $_FILES['doc_file'] ?? null;

        if (empty($type) || !$file) {
            echo json_encode(['success' => false, 'message' => 'Required multi-part form files or tracking metadata elements missing.']);
        } else {
            echo json_encode(uploadInternDocument($internId, $type, $file, $notes, $coordinatorId));
        }
        break;
    case 'getCoordinatorDocuments':
        if (($_SESSION['user']['role'] ?? '') !== 'coordinator') {
            echo json_encode(['error' => 'Unauthorized access']); break;
        }
        echo json_encode(getCoordinatorDocuments());
        break;

    case 'reviewInternDocument':
        if (($_SESSION['user']['role'] ?? '') !== 'coordinator') {
            echo json_encode(['error' => 'Unauthorized access']); break;
        }
        $coordinatorId = $_SESSION['user']['id'] ?? null;
        $docId         = (int)($data['docId'] ?? 0);
        $status        = $data['status'] ?? '';
        $feedback      = $data['feedback'] ?? '';

        if (!$docId || empty($status) || !$coordinatorId) {
            echo json_encode(['success' => false, 'message' => 'Missing tracking parameters.']);
        } else {
            echo json_encode(reviewInternDocument($docId, $status, $feedback, $coordinatorId));
        }
        break;
    default:
        echo json_encode([
            "error" => "Invalid action"
        ]);
        break;
}
?>