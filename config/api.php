<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require 'functions.php';
require 'report.php';

// Retrieve session user ID
$userId = (int) ($_SESSION['user']['id'] ?? 0);

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
    case 'setProfileReviewed':
        $internId = (int)  ($data['internId']  ?? 0);
        $reviewed = (bool) ($data['reviewed']  ?? false);
 
        if (!$internId) {
            echo json_encode(['success' => false, 'error' => 'Missing internId.']);
            break;
        }
 
        echo json_encode(setProfileReviewed($internId, $reviewed));
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
    // ── Announcement comments ──────────────────────────────────
    case 'getAnnouncementComments':
        $annId = (int)($data['announcement_id'] ?? 0);
        echo json_encode($annId
            ? getAnnouncementComments($annId)
            : ['error' => 'Missing announcement_id']);
        break;
 
    case 'postAnnouncementComment':
        $annId    = (int)      ($data['announcement_id'] ?? 0);
        $comment  =             $data['comment']          ?? '';
        $parentId = isset($data['parent_id']) && $data['parent_id'] !== null
                  ? (int) $data['parent_id']
                  : null;
        echo json_encode($annId
            ? postAnnouncementComment($annId, $comment, $parentId)
            : ['success' => false, 'error' => 'Missing announcement_id']);
        break;
 
    case 'deleteAnnouncementComment':
        $commentId = (int)($data['comment_id'] ?? 0);
        echo json_encode($commentId
            ? deleteAnnouncementComment($commentId)
            : ['success' => false, 'error' => 'Missing comment_id']);
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
        if ($userId) {
            echo json_encode(getInternDocuments($userId));
        } else {
            echo json_encode(['error' => 'Unauthenticated session status.']);
        }
        break;
    case 'uploadInternDocument':
        if (!$userId) {
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
            echo json_encode(uploadInternDocument($userId, $type, $file, $notes, $coordinatorId));
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
        $docId    = (int)($data['docId'] ?? 0);
        $status   = $data['status'] ?? '';
        $feedback = $data['feedback'] ?? '';

        if (!$docId || empty($status) || !$userId) {
            echo json_encode(['success' => false, 'message' => 'Missing tracking parameters.']);
        } else {
            echo json_encode(reviewInternDocument($docId, $status, $feedback, $userId));
        }
        break;
    case 'setReportStatus':
        $reportId = (int)   ($data['reportId'] ?? 0);
        $status   =          $data['status']   ?? '';
        $feedback =          $data['feedback'] ?? '';

        if (!$reportId || !$status) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing reportId or status.',
            ]);
            break;
        }

        setReportStatus($reportId, $status, $feedback);
        break;

    case 'getSchoolYears':
        $years   = getSchoolYears();
        $current = getCurrentSchoolYear();
        if (!in_array($current, $years, true)) {
            array_unshift($years, $current);
        }
        echo json_encode([
            'success'      => true,
            'years'        => $years,
            'current_year' => $current,
        ]);
        break;

    case 'getInternsBySchoolYear':
        if (!$userId || !in_array($_SESSION['user']['role'] ?? '', ['coordinator','admin'], true)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
            break;
        }
        $schoolYear = trim($data['school_year'] ?? '');
        $interns    = getInternsBySchoolYear($schoolYear);
        echo json_encode([
            'success'     => true,
            'interns'     => $interns,
            'school_year' => $schoolYear ?: getCurrentSchoolYear(),
        ]);
        break;

    // ── Report Update & Delete actions ───────────────────────
    case 'updateReport':
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'Not logged in.']);
            break;
        }

        $reportId      = (int) ($data['report_id'] ?? 0);
        $weekLabel     = $data['week_label'] ?? '';
        $weekStart     = $data['week_start'] ?? '';
        $description   = $data['description'] ?? '';
        $filesToDelete = $data['delete_file_ids'] ?? [];
        $newFiles      = $_FILES['files'] ?? [];

        if (is_string($filesToDelete)) {
            $decoded = json_decode($filesToDelete, true);
            if (is_array($decoded)) {
                $filesToDelete = $decoded;
            }
        }

        $result = updateReport(
            $userId,
            $reportId,
            $weekLabel,
            $weekStart,
            $description,
            (array) $filesToDelete,
            $newFiles
        );

        echo json_encode($result);
        break;

    case 'deleteReport':
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'Not logged in.']);
            break;
        }

        $reportId = (int) ($data['report_id'] ?? 0);

        $result = deleteReport($userId, $reportId);

        echo json_encode($result);
        break;

    default:
        echo json_encode([
            "error" => "Invalid action"
        ]);
        break;
}
?>