<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/supabase.php';
function getSessionUser() {
    if (isset($_SESSION['user'])) {
        return [
            'success' => true,
            'user' => $_SESSION['user']
        ];
    }
    return [
        'success' => false,
        'message' => 'No active user session found.'
    ];
}
function getUsers() {
  global $conn;
  $user = $_SESSION['user'];
  $userId = $user['id'];
  $stmt = $conn->query("SELECT id, username FROM intern_profiles");
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
  
}
function logout() {
    session_destroy();
    header("Location: login.php");
    exit();
}
function getInternProfile() {
    if (!isset($_SESSION['user']['id'])) {
        return null;
    }

    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT *
        FROM intern_profiles
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$_SESSION['user']['id']]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function getInternCoordinatorId () {
    if (!isset($_SESSION['user']['id'])) {
        return null;
    }

    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT coordinator_id
        FROM intern_profiles
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$_SESSION['user']['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ? (int)$result['coordinator_id'] : null;
}
function getAllInternData() {
    if (!isset($_SESSION['user']['id'])) {
        return null;
    }

    $pdo = getDB();
    $userId = $_SESSION['user']['id'];

    // ---------------------------
    // 1. Get user
    // ---------------------------
    $stmt = $pdo->prepare("
        SELECT id, name, email
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) return null;

    // ---------------------------
    // 2. Get intern profile
    // ---------------------------
    $stmt = $pdo->prepare("
        SELECT course, year_level, phone, coordinator_id, required_hours, joined_date
        FROM intern_profiles
        WHERE user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    // ---------------------------
    // 3. Get internships + company
    // ---------------------------
    $stmt = $pdo->prepare("
        SELECT 
            i.id,
            i.position,
            i.supervisor,
            i.supervisor_phone,
            i.start_date,
            i.end_date,
            i.status,
            i.created_at,
            i.total_hours,
            i.days_present,
            i.reports_submitted,

            c.id AS company_id,
            c.name AS company_name,
            c.address,
            c.phone AS company_phone,
            c.email AS company_email,
            c.created_at AS company_created

        FROM internships i
        LEFT JOIN companies c ON c.id = i.company_id
        WHERE i.intern_id = ?
        ORDER BY i.created_at DESC
    ");

    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $internships = [];

    foreach ($rows as $row) {
        $internships[] = [
            "id" => $row["id"],
            "position" => $row["position"],
            "supervisor" => $row["supervisor"],
            "supervisor_phone" => $row["supervisor_phone"],
            "start_date" => $row["start_date"],
            "end_date" => $row["end_date"],
            "status" => $row["status"],
            "created_at" => $row["created_at"],
            "total_hours" => $row["total_hours"],
            "days_present" => $row["days_present"],
            "reports_submitted" => $row["reports_submitted"],

            "company" => [
                "id" => $row["company_id"],
                "name" => $row["company_name"],
                "address" => $row["address"],
                "phone" => $row["company_phone"],
                "email" => $row["company_email"],
                "created_at" => $row["company_created"]
            ]
        ];
    }

    // ---------------------------
    // Final structured response
    // ---------------------------

    /** @var array{
     *  user: array{id:int,name:string,email:string},
     *  profile: array{
     *      school:string,
     *      course:string,
     *      year_level:string,
     *      phone:string,
     *      required_hours:int,
     *      joined_date:string
     *  }|null,
     *  internships: array<array{
     *      id:int,
     *      position:string,
     *      supervisor:string,
     *      start_date:string,
     *      end_date:string,
     *      status:string,
     *      created_at:string,
     *      company: array{
     *          id:int|null,
     *          name:string|null,
     *          address:string|null,
     *          phone:string|null,
     *          email:string|null,
     *          created_at:string|null
     *      }|null
     *  }>
     * } */
    $result = [
        "user" => $user,
        "profile" => $profile,
        "internships" => $internships
    ];

    return $result;
}

function getPendingInterns(): array
{
    $pdo = getDB();

    try {
        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.name,
                u.email,
                u.created_at,
                ip.course,
                ip.year_level
            FROM users u
            INNER JOIN intern_profiles ip
                ON ip.user_id = u.id
            WHERE u.is_active = 0
              AND u.role = 'intern'
            ORDER BY u.created_at DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('getPendingInterns(): ' . $e->getMessage());
        return [];
    }
}
function approvePendingIntern($internId) {
    $pdo = getDB();

    try {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        $stmt->execute([$internId]);
        return true;
    } catch (PDOException $e) {
        error_log('approavPendingIntern(): ' . $e->getMessage());
        return false;
    }
}
function updateInternProfile(array $data): void
{
    $pdo = getDB();
    if (empty($_SESSION['user']['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized.'
        ]);
        return;
    }

    $userId = (int) $_SESSION['user']['id'];

    $user       = $data['user'] ?? [];
    $profile    = $data['profile'] ?? [];
    $internship = $data['internship'] ?? [];
    $company = $data['company'] ?? [];


    try {
        $pdo->beginTransaction();

        /*
        |--------------------------------------------------------------------------
        | Update users table
        |--------------------------------------------------------------------------
        */
        $stmt = $pdo->prepare("
            UPDATE users
            SET name = ?
            WHERE id = ?
        ");

        $stmt->execute([
            trim($user['name'] ?? ''),
            $userId
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update intern_profiles table
        |--------------------------------------------------------------------------
        */
        $stmt = $pdo->prepare("
            UPDATE intern_profiles
            SET
                phone = ?,
                course = ?,
                year_level = ?
            WHERE user_id = ?
        ");

        $stmt->execute([
            trim($profile['phone'] ?? ''),
            trim($profile['course'] ?? ''),
            trim($profile['year_level'] ?? ''),
            $userId
        ]);

        /*
        |--------------------------------------------------------------------------
        | Get active internship
        |--------------------------------------------------------------------------
        */
        $stmt = $pdo->prepare("
            SELECT id, company_id
            FROM internships
            WHERE intern_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");

        $stmt->execute([$userId]);
        $currentInternship = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($currentInternship) {
            
            /*
            --------------------------------------------------------------
            | Update companies table
            --------------------------------------------------------------
            */
            $stmt = $pdo->prepare("
                UPDATE companies
                SET
                    name = ?,
                    address = ?,
                    phone = ?,
                    email = ?
                WHERE id = ?
            ");

            $stmt->execute([
                trim($internship['company_name'] ?? ''),
                trim($internship['address'] ?? ''),
                trim($company['phone'] ?? ''),
                trim($company['email'] ?? ''),
                $currentInternship['company_id']
            ]);
            /*
            --------------------------------------------------------------
            | Update internships table
            --------------------------------------------------------------
            */
            $stmt = $pdo->prepare("
                UPDATE internships
                SET
                    position = ?,
                    supervisor = ?,
                    supervisor_phone = ?,
                    start_date = ?,
                    end_date = ?
                WHERE id = ?
            ");

            $stmt->execute([
                trim($internship['position'] ?? ''),
                trim($internship['supervisor'] ?? ''),
                trim($internship['supervisor_phone'] ?? ''),
                !empty($internship['start_date']) ? $internship['start_date'] : null,
                !empty($internship['end_date']) ? $internship['end_date'] : null,
                $currentInternship['id']
            ]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => [
                'name' => trim($user['name'] ?? '')
            ],
            'profile' => [
                'phone' => trim($profile['phone'] ?? ''),
                'course' => trim($profile['course'] ?? ''),
                'year_level' => trim($profile['year_level'] ?? '')
            ],
            'internship' => [
                'position' => trim($internship['position'] ?? ''),
                'supervisor' => trim($internship['supervisor'] ?? ''),
                'supervisor_phone' => trim($internship['supervisor_phone'] ?? ''),
                'start_date' => !empty($internship['start_date']) ? $internship['start_date'] : null,
                'end_date' => !empty($internship['end_date']) ? $internship['end_date'] : null,
                'company' => [
                    'name' => trim($internship['company_name'] ?? ''),
                    'address' => trim($internship['address'] ?? '')
                ]
            ]
        ]);

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function submitWeeklyReport(): void
{
    $pdo = getDB();
    header('Content-Type: application/json');

    try {
        // Verify login
        if (empty($_SESSION['user']['id'])) {
            throw new Exception('You must be logged in.');
        }

        $internId    = (int) $_SESSION['user']['id'];
        $weekLabel   = trim($_POST['week_label'] ?? '');
        $weekStart   = trim($_POST['week_start'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Validation
        if ($weekLabel === '') {
            throw new Exception('Week label is required.');
        }

        if ($weekStart === '') {
            throw new Exception('Week start date is required.');
        }

        if (!isset($_FILES['report_file'])) {
            throw new Exception('No file uploaded.');
        }

        $file = $_FILES['report_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed.');
        }

        // File validation
        $maxSize = 10 * 1024 * 1024; // 10 MB

        if ($file['size'] > $maxSize) {
            throw new Exception('File exceeds 10 MB limit.');
        }

        $allowedExtensions = [
            'pdf',
            'doc',
            'docx',
            'png',
            'jpg',
            'jpeg'
        ];

        $originalName = $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new Exception('Invalid file type.');
        }

        // Create upload directory
        $uploadDir = __DIR__ . '/../uploads/weekly_reports/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique filename
        $storedName = sprintf(
            'report_%d_%s.%s',
            $internId,
            uniqid(),
            $extension
        );

        $absolutePath = $uploadDir . $storedName;
        $relativePath = 'uploads/weekly_reports/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            throw new Exception('Failed to save uploaded file.');
        }

        // Save to database
        $stmt = $pdo->prepare("
            INSERT INTO weekly_reports (
                intern_id,
                week_label,
                week_start,
                description,
                file_path,
                file_name,
                file_size,
                status
            ) VALUES (
                :intern_id,
                :week_label,
                :week_start,
                :description,
                :file_path,
                :file_name,
                :file_size,
                'pending'
            )
        ");

        $stmt->execute([
            ':intern_id'   => $internId,
            ':week_label'  => $weekLabel,
            ':week_start'  => $weekStart,
            ':description' => $description ?: null,
            ':file_path'   => $relativePath,
            ':file_name'   => $originalName,
            ':file_size'   => $file['size']
        ]);

        echo json_encode([
            'success'   => true,
            'message'   => 'Weekly report submitted successfully.',
            'report_id' => $pdo->lastInsertId()
        ]);

    } catch (Throwable $e) {
        http_response_code(400);

        echo json_encode([
            'error' => $e->getMessage()
        ]);
    }

    exit;
}
function getInternReports(): array
{
    if (empty($_SESSION['user']['id'])) return [];

    $pdo    = getDB();
    $userId = (int) $_SESSION['user']['id'];

    try {
        // Fetch all reports for this intern, newest first
        $stmt = $pdo->prepare("
            SELECT
                r.id,
                r.week_label,
                r.week_start,
                r.description,
                r.status,
                r.feedback,
                r.uploaded_at,
                r.reviewed_at
            FROM weekly_reports r
            WHERE r.intern_id = :intern_id
            ORDER BY r.week_start DESC
        ");
        $stmt->execute([':intern_id' => $userId]);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($reports)) return [];

        // Fetch all files for these reports in one query
        $reportIds    = array_column($reports, 'id');
        $placeholders = implode(',', array_fill(0, count($reportIds), '?'));

        $stmt = $pdo->prepare("
            SELECT report_id, file_name, file_path, file_size, mime_type
            FROM   weekly_report_files
            WHERE  report_id IN ($placeholders)
            ORDER BY id ASC
        ");
        $stmt->execute($reportIds);
        $allFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group files by report_id for easy lookup
        $filesByReport = [];
        foreach ($allFiles as $f) {
            $filesByReport[$f['report_id']][] = $f;
        }

        // Attach files array to each report
        foreach ($reports as &$report) {
            $report['files'] = $filesByReport[$report['id']] ?? [];
        }

        return $reports;

    } catch (PDOException $e) {
        error_log('getInternReports(): ' . $e->getMessage());
        return [];
    }
}

function setReportStatus(int $reportId, string $status, string $feedback = ''): void
{
    // header() must be called before any output — move it to the top of the function
    header('Content-Type: application/json');

    $status = strtolower(trim($status));
    $pdo    = getDB();

    try {
        // ── Auth check ────────────────────────────────────────
        if (empty($_SESSION['user']['id'])) {
            throw new Exception('You must be logged in.');
        }

        $reviewerId = (int) $_SESSION['user']['id'];

        // ── Validate report ID ────────────────────────────────
        if ($reportId <= 0) {
            throw new Exception('Invalid report ID.');
        }

        // ── Bug 1 fix: include all statuses the coordinator page uses ──
        $allowedStatuses = ['pending', 'approved', 'reviewed', 'revision'];

        if (!in_array($status, $allowedStatuses, true)) {
            throw new Exception('Invalid status: "' . $status . '". Allowed: ' . implode(', ', $allowedStatuses));
        }

        // ── Check report exists ───────────────────────────────
        $checkStmt = $pdo->prepare("
            SELECT id FROM weekly_reports WHERE id = :id LIMIT 1
        ");
        $checkStmt->execute([':id' => $reportId]);

        if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('Report not found.');
        }

        // ── Bug 2 + 3 + 4 fix: correct columns ───────────────
        //  - reviewed_at  instead of updated_at (which doesn't exist)
        //  - reviewed_by  saves the coordinator's ID
        //  - feedback     saves the coordinator's written comment
        $stmt = $pdo->prepare("
            UPDATE weekly_reports
            SET
                status      = :status,
                feedback    = :feedback,
                reviewed_at = NOW(),
                reviewed_by = :reviewer_id
            WHERE id = :id
        ");

        $stmt->execute([
            ':status'      => $status,
            ':feedback'    => trim($feedback) ?: null,
            ':reviewer_id' => $reviewerId,
            ':id'          => $reportId,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('No rows updated — report may not exist.');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Report status updated successfully.',
            'data'    => [
                'report_id'   => $reportId,
                'status'      => $status,
                'reviewed_by' => $reviewerId,
            ],
        ]);

    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
        ]);
    }

    exit;
}

/**
 * Fetches all weekly reports assigned to the logged-in coordinator,
 * including the intern's name and any associated files.
 *
 * @param int $coordinatorId The ID of the signed-in coordinator.
 * @return array An array of reports, each containing an 'intern_name' and a 'files' array.
 */
function getReportsByCoordinator(int $coordinatorId): array 
{
    $pdo = getDB(); 

    // Query 1: Fetch reports joined with the users table to get the intern's name
    $reportSql = "SELECT r.id, r.intern_id, u.name AS intern_name, r.week_label, r.week_start, 
                         r.description, r.status, r.feedback, r.uploaded_at, r.reviewed_at, r.reviewed_by 
                  FROM weekly_reports r
                  INNER JOIN users u ON r.intern_id = u.id
                  WHERE r.coordinator_id = :coordinator_id
                  ORDER BY r.uploaded_at DESC";

    // Query 2: Fetch files for a specific report
    $fileSql = "SELECT id, file_path, file_name, file_size, mime_type, uploaded_at 
                FROM weekly_report_files 
                WHERE report_id = :report_id";

    try {
        // Fetch the reports
        $reportStmt = $pdo->prepare($reportSql);
        $reportStmt->execute([':coordinator_id' => $coordinatorId]);
        $reports = $reportStmt->fetchAll(PDO::FETCH_ASSOC);

        // Prepare the file statement outside the loop for optimization
        $fileStmt = $pdo->prepare($fileSql);

        // Loop through each report and attach its files
        foreach ($reports as &$report) {
            $fileStmt->execute([':report_id' => $report['id']]);
            $report['files'] = $fileStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        unset($report); // Break reference pointer loop safety

        return $reports;

    } catch (PDOException $e) {
        // Check error_log.txt in your config directory if it still fails!
        error_log("Database error in getReportsByCoordinator: " . $e->getMessage());
        return [];
    }
}

function getCoordinatorInternDatas() {
    if (!isset($_SESSION['user']['id'])) {
        return [];
    }

    $pdo = getDB();
    $coordinatorId = $_SESSION['user']['id'];

    // -------------------------------------------------
    // Get all interns handled by this coordinator
    // -------------------------------------------------
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,

            ip.course,
            ip.year_level,
            ip.phone,
            ip.required_hours,
            ip.joined_date

        FROM intern_profiles ip

        INNER JOIN users u
            ON u.id = ip.user_id

        WHERE ip.coordinator_id = ? and u.is_active = 1 and u.role = 'intern'

        ORDER BY u.name ASC
    ");

    $stmt->execute([$coordinatorId]);

    $internUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $internDatas = [];

    // -------------------------------------------------
    // Build complete intern data structure
    // -------------------------------------------------
    foreach ($internUsers as $intern) {

        $internId = $intern['id'];

        // ---------------------------------------------
        // Get internships + company
        // ---------------------------------------------
        $stmt = $pdo->prepare("
            SELECT 
                i.id,
                i.position,
                i.supervisor,
                i.supervisor_phone,
                i.start_date,
                i.end_date,
                i.status,
                i.created_at,
                i.total_hours,
                i.days_present,
                i.reports_submitted,

                c.id AS company_id,
                c.name AS company_name,
                c.address,
                c.phone AS company_phone,
                c.email AS company_email,
                c.created_at AS company_created

            FROM internships i

            LEFT JOIN companies c
                ON c.id = i.company_id

            WHERE i.intern_id = ?

            ORDER BY i.created_at DESC
        ");

        $stmt->execute([$internId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $internships = [];

        foreach ($rows as $row) {

            $internships[] = [
                "id" => $row["id"],
                "position" => $row["position"],
                "supervisor" => $row["supervisor"],
                "supervisor_phone" => $row["supervisor_phone"],
                "start_date" => $row["start_date"],
                "end_date" => $row["end_date"],
                "status" => $row["status"],
                "created_at" => $row["created_at"],
                "total_hours" => $row["total_hours"],
                "days_present" => $row["days_present"],
                "reports_submitted" => $row["reports_submitted"],

                "company" => [
                    "id" => $row["company_id"],
                    "name" => $row["company_name"],
                    "address" => $row["address"],
                    "phone" => $row["company_phone"],
                    "email" => $row["company_email"],
                    "created_at" => $row["company_created"]
                ]
            ];
        }

        // ---------------------------------------------
        // Final intern data structure
        // ---------------------------------------------
        $internDatas[] = [
            "user" => [
                "id" => $intern["id"],
                "name" => $intern["name"],
                "email" => $intern["email"]
            ],

            "profile" => [
                "course" => $intern["course"],
                "year_level" => $intern["year_level"],
                "phone" => $intern["phone"],
                "required_hours" => $intern["required_hours"],
                "joined_date" => $intern["joined_date"]
            ],

            "internships" => $internships
        ];
    }

    return $internDatas;
}
// -----------------------------------
// Save current page to session
// -----------------------------------

function setCurrentPage($page) {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['currentPage'] = $page;

    return [
        "success" => true,
        "currentPage" => $page
    ];
}


// -----------------------------------
// Get current page from session
// -----------------------------------

function getCurrentPage() {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return [
        "currentPage" => $_SESSION['currentPage'] ?? null
    ];
}

function getAnnouncements() {
    if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['role'])) {
        return [];
    }

    $pdo = getDB();
    $userId = $_SESSION['user']['id'];
    $userRole = $_SESSION['user']['role'];
    $targetCoordinatorId = null;

    if ($userRole === 'coordinator') {
        // If coordinator, they see their own announcements
        $targetCoordinatorId = $userId;
    } elseif ($userRole === 'intern') {
        // If intern, find who their coordinator is from intern_profiles
        $stmt = $pdo->prepare("SELECT coordinator_id FROM intern_profiles WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($profile) {
            $targetCoordinatorId = $profile['coordinator_id'];
        }
    }

    // If we couldn't find a coordinator ID (or user has no coordinator), return empty
    if (!$targetCoordinatorId) {
        return [];
    }

    // Fetch announcements ordered by pinned status first, then newest date
    $stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            body,
            is_pinned,
            created_at,
            updated_at
        FROM announcements
        WHERE coordinator_id = ?
        ORDER BY is_pinned DESC, created_at DESC
    ");

    $stmt->execute([$targetCoordinatorId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function addAnnouncement($title, $body, $isPinned = 0) {
    // Only coordinators should be allowed to add announcements
    if (!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'coordinator') {
        return [
            'success' => false,
            'message' => 'Unauthorized: Only coordinators can create announcements.'
        ];
    }

    $pdo = getDB();
    $coordinatorId = $_SESSION['user']['id'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO announcements (
                coordinator_id, 
                title, 
                body, 
                is_pinned, 
                created_at
            ) VALUES (?, ?, ?, ?, NOW())
        ");

        $result = $stmt->execute([
            $coordinatorId,
            trim($title),
            trim($body),
            $isPinned ? 1 : 0
        ]);

        if ($result) {
            return [
                'success' => true, 
                'message' => 'Announcement posted successfully!',
                'id' => $pdo->lastInsertId()
            ];
        }

        return ['success' => false, 'message' => 'Failed to save announcement.'];

    } catch (PDOException $e) {
        // Log error and return a clean message
        error_log("Add Announcement Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'A database error occurred.'];
    }
}
function updateAnnouncement($id, $title, $body, $isPinned) {
    if (!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'coordinator') {
        return ['success' => false, 'message' => 'Unauthorized.'];
    }

    $pdo = getDB();
    $coordinatorId = $_SESSION['user']['id'];

    $stmt = $pdo->prepare("
        UPDATE announcements 
        SET title = ?, body = ?, is_pinned = ?, updated_at = NOW() 
        WHERE id = ? AND coordinator_id = ?
    ");
    
    $success = $stmt->execute([trim($title), trim($body), $isPinned ? 1 : 0, $id, $coordinatorId]);
    
    return [
        'success' => $success,
        'message' => $success ? 'Updated successfully!' : 'Update failed or unauthorized.'
    ];
}

function deleteAnnouncement($id) {
    if (!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'coordinator') {
        return ['success' => false, 'message' => 'Unauthorized.'];
    }

    $pdo = getDB();
    $coordinatorId = $_SESSION['user']['id'];

    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ? AND coordinator_id = ?");
    $success = $stmt->execute([$id, $coordinatorId]);

    return [
        'success' => $success,
        'message' => $success ? 'Deleted successfully!' : 'Delete failed or unauthorized.'
    ];
}
/**
 * Calculates the weekly average of time logs for the current week 
 * and saves/updates the summary in the coordinator_weekly_hours_summary table.
 *
 * @return array Status array indicating success or failure.
 */
function generateWeeklyCoordinatorSummary() {
    try {
        // 1. Use your existing database connection function
        $pdo = getDB(); 
        
        // Ensure PDO is set to throw exceptions for clean error handling
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 2. Calculate Current Week Dates (Monday to Sunday)
        $monday = new DateTime('monday this week');
        $sunday = new DateTime('sunday this week');
        
        $weekStart = $monday->format('Y-m-d 00:00:00');
        $weekEnd   = $sunday->format('Y-m-d 23:59:59');

        // 3. Query to aggregate time_logs by coordinator for the current week
        $sql = "
            SELECT 
                coordinator_id,
                COUNT(DISTINCT intern_id) AS total_interns,
                SUM(duration_hours) AS total_hours,
                ROUND(SUM(duration_hours) / COUNT(DISTINCT intern_id), 2) AS batch_avg_hours
            FROM time_logs
            WHERE coordinator_id IS NOT NULL
              AND log_date BETWEEN :week_start AND :week_end
            GROUP BY coordinator_id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'week_start' => $weekStart,
            'week_end'   => $weekEnd
        ]);
        
        $summaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($summaries)) {
            return [
                'status'  => 'success',
                'message' => 'No time logs found to process for the current week.'
            ];
        }

        // 4. Insert or Update the summary table
        $insertSql = "
            INSERT INTO coordinator_weekly_hours_summary 
                (coordinator_id, week_start, week_end, total_interns, total_hours, batch_avg_hours, created_at)
            VALUES 
                (:coordinator_id, :week_start, :week_end, :total_interns, :total_hours, :batch_avg_hours, NOW())
            ON DUPLICATE KEY UPDATE
                total_interns   = VALUES(total_interns),
                total_hours     = VALUES(total_hours),
                batch_avg_hours = VALUES(batch_avg_hours),
                created_at      = NOW()
        ";

        $insertStmt = $pdo->prepare($insertSql);

        foreach ($summaries as $row) {
            $insertStmt->execute([
                'coordinator_id'  => $row['coordinator_id'],
                'week_start'      => $monday->format('Y-m-d'), 
                'week_end'        => $sunday->format('Y-m-d'),
                'total_interns'   => $row['total_interns'],
                'total_hours'     => $row['total_hours'],
                'batch_avg_hours' => $row['batch_avg_hours']
            ]);
        }

        return [
            'status'  => 'success',
            'message' => 'Successfully processed summaries for ' . count($summaries) . ' coordinator(s).'
        ];

    } catch (PDOException $e) {
        // Log database errors silently and return a clean response to the API
        error_log("[" . date('Y-m-d H:i:s') . "] Summary Calculation Error: " . $e->getMessage());
        
        return [
            'status'  => 'error',
            'message' => 'Internal database error processing summary calculations.'
        ];
    }
}


// ════════════════════════════════════════════════════════════
//  ADMIN FUNCTIONS
// ════════════════════════════════════════════════════════════

/**
 * System-wide stats for the admin dashboard.
 */
function getSystemStats(): array
{
    $pdo = getDB();
    try {
        $stats = [];

        // Total active interns
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='intern' AND is_active=1");
        $stats['total_interns'] = (int)$stmt->fetchColumn();

        // Pending interns
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='intern' AND is_active=0");
        $stats['pending_interns'] = (int)$stmt->fetchColumn();

        // Total active coordinators
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='coordinator' AND is_active=1");
        $stats['total_coordinators'] = (int)$stmt->fetchColumn();

        // Pending coordinators
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='coordinator' AND is_active=0");
        $stats['pending_coordinators'] = (int)$stmt->fetchColumn();

        // Total reports
        $stmt = $pdo->query("SELECT COUNT(*) FROM weekly_reports");
        $stats['total_reports'] = (int)$stmt->fetchColumn();

        // Total companies
        $stmt = $pdo->query("SELECT COUNT(*) FROM companies WHERE name != 'Not yet assigned'");
        $stats['total_companies'] = (int)$stmt->fetchColumn();

        // Recent registrations (last 5, any role)
        $stmt = $pdo->query("
            SELECT id, name, email, role, is_active, created_at
            FROM users
            WHERE role != 'admin'
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stats['recent_registrations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;

    } catch (PDOException $e) {
        error_log('getSystemStats(): ' . $e->getMessage());
        return [];
    }
}

/**
 * Get all coordinator accounts (pending + active).
 */
function getAllCoordinators(): array
{
    $pdo = getDB();
    try {
        $stmt = $pdo->query("
            SELECT id, name, email, is_active, created_at
            FROM users
            WHERE role = 'coordinator'
            ORDER BY is_active ASC, created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('getAllCoordinators(): ' . $e->getMessage());
        return [];
    }
}

/**
 * Approve a coordinator account (set is_active = 1).
 */
function approveCoordinator(int $id): array
{
    $pdo = getDB();
    try {
        $stmt = $pdo->prepare("
            UPDATE users SET is_active = 1
            WHERE id = ? AND role = 'coordinator'
        ");
        $stmt->execute([$id]);
        return $stmt->rowCount()
            ? ['success' => true]
            : ['success' => false, 'error' => 'Coordinator not found.'];
    } catch (PDOException $e) {
        error_log('approveCoordinator(): ' . $e->getMessage());
        return ['success' => false, 'error' => 'Database error.'];
    }
}

/**
 * Deactivate a coordinator account (set is_active = 0).
 * Does NOT delete — account can be reactivated.
 */
function deactivateCoordinator(int $id): array
{
    $pdo = getDB();
    try {
        $stmt = $pdo->prepare("
            UPDATE users SET is_active = 0
            WHERE id = ? AND role = 'coordinator'
        ");
        $stmt->execute([$id]);
        return $stmt->rowCount()
            ? ['success' => true]
            : ['success' => false, 'error' => 'Coordinator not found.'];
    } catch (PDOException $e) {
        error_log('deactivateCoordinator(): ' . $e->getMessage());
        return ['success' => false, 'error' => 'Database error.'];
    }
}

/**
 * Permanently delete a coordinator account.
 */
function deleteCoordinator(int $id): array
{
    $pdo = getDB();
    try {
        // Safety: never allow deleting an admin account this way
        $stmt = $pdo->prepare("
            DELETE FROM users
            WHERE id = ? AND role = 'coordinator'
        ");
        $stmt->execute([$id]);
        return $stmt->rowCount()
            ? ['success' => true]
            : ['success' => false, 'error' => 'Coordinator not found.'];
    } catch (PDOException $e) {
        error_log('deleteCoordinator(): ' . $e->getMessage());
        return ['success' => false, 'error' => 'Database error.'];
    }
}

// ════════════════════════════════════════════════════════════
//  ATTENDANCE FUNCTIONS
// ════════════════════════════════════════════════════════════
 
/**
 * Get today's time log for the logged-in intern.
 * Returns the row or null if nothing logged yet today.
 */
function getTodayLog(): ?array
{
    if (empty($_SESSION['user']['id'])) return null;
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT * FROM time_logs
        WHERE intern_id = ? AND log_date = CURDATE()
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user']['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
 
/**
 * Clock in the intern for today.
 * Fails if already timed in today.
 */
function timeIn(): array
{
    if (empty($_SESSION['user']['id'])) return ['success'=>false,'error'=>'Not logged in.'];
    $pdo    = getDB();
    $userId = (int)$_SESSION['user']['id'];
 
    // Check if already has a log today
    $existing = getTodayLog();
    if ($existing) {
        if ($existing['time_out']) {
            return ['success'=>false,'error'=>'Already completed for today.','state'=>'done'];
        }
        return ['success'=>false,'error'=>'Already timed in.','state'=>'in','log'=>$existing];
    }
 
    try {
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("
            INSERT INTO time_logs (intern_id, log_date, time_in, coordinator_id)
            VALUES (?, CURDATE(), ?, ?)
        ");
        $stmt->execute([$userId, $now, getInternCoordinatorId($userId, $pdo)]);
        $logId = (int)$pdo->lastInsertId();
 
        // Also update days_present in internships
        $pdo->prepare("
            UPDATE internships
            SET days_present = (
                SELECT COUNT(*) FROM time_logs
                WHERE intern_id = ? AND time_out IS NOT NULL
            )
            WHERE intern_id = ?
        ")->execute([$userId, $userId]);
 
        return [
            'success'  => true,
            'state'    => 'in',
            'time_in'  => $now,
            'log_id'   => $logId,
        ];
    } catch (PDOException $e) {
        error_log('timeIn(): '.$e->getMessage());
        return ['success'=>false,'error'=>'Database error.'];
    }
}
 
/**
 * Clock out the intern for today.
 * Calculates total hours and updates the internship summary.
 */
function timeOut(): array
{
    if (empty($_SESSION['user']['id'])) return ['success'=>false,'error'=>'Not logged in.'];
    $pdo    = getDB();
    $userId = (int)$_SESSION['user']['id'];
 
    $existing = getTodayLog();
    if (!$existing)            return ['success'=>false,'error'=>'You have not timed in today.'];
    if ($existing['time_out']) return ['success'=>false,'error'=>'Already timed out today.','state'=>'done'];
 
    try {
        $now        = date('Y-m-d H:i:s');
        $totalHours = (strtotime($now) - strtotime($existing['time_in'])) / 3600;
 
        $pdo->prepare("
            UPDATE time_logs
            SET time_out = ?, total_hours = ?
            WHERE id = ?
        ")->execute([$now, round($totalHours, 2), $existing['id']]);
 
        // Recompute internship totals
        recalcInternshipHours($userId, $pdo);
 
        return [
            'success'     => true,
            'state'       => 'done',
            'time_in'     => $existing['time_in'],
            'time_out'    => $now,
            'total_hours' => round($totalHours, 2),
        ];
    } catch (PDOException $e) {
        error_log('timeOut(): '.$e->getMessage());
        return ['success'=>false,'error'=>'Database error.'];
    }
}
 
/**
 * Get all attendance logs for the intern, newest first.
 */
function getAttendanceLogs(): array
{
    if (empty($_SESSION['user']['id'])) return [];
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT id, log_date, time_in, time_out, total_hours, notes
        FROM time_logs
        WHERE intern_id = ?
        ORDER BY log_date DESC
    ");
    $stmt->execute([$_SESSION['user']['id']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
 
/**
 * Edit an existing time log entry.
 * Both time_in and time_out are required — recalculates total_hours.
 * Verifies the log belongs to the logged-in intern.
 */
function updateTimeLog(array $data): array
{
    if (empty($_SESSION['user']['id'])) return ['success'=>false,'error'=>'Not logged in.'];
    $pdo    = getDB();
    $userId = (int)$_SESSION['user']['id'];
    $logId  = (int)($data['log_id'] ?? 0);
 
    if (!$logId) return ['success'=>false,'error'=>'Missing log_id.'];
 
    // Validate that this log belongs to the intern
    $stmt = $pdo->prepare("SELECT id, log_date FROM time_logs WHERE id=? AND intern_id=?");
    $stmt->execute([$logId, $userId]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$log) return ['success'=>false,'error'=>'Log not found.'];
 
    $date    = $log['log_date'];                       // keep existing date
    $timeIn  = $date . ' ' . ($data['time_in']  ?? '08:00') . ':00';
    $timeOut = !empty($data['time_out'])
             ? $date . ' ' . $data['time_out'] . ':00'
             : null;
 
    // time_out must be after time_in
    if ($timeOut && strtotime($timeOut) <= strtotime($timeIn)) {
        return ['success'=>false,'error'=>'Time out must be after time in.'];
    }
 
    $totalHours = $timeOut
        ? round((strtotime($timeOut) - strtotime($timeIn)) / 3600, 2)
        : null;
 
    try {
        $pdo->prepare("
            UPDATE time_logs
            SET time_in=?, time_out=?, total_hours=?, notes=?
            WHERE id=? AND intern_id=?
        ")->execute([
            $timeIn,
            $timeOut,
            $totalHours,
            trim($data['notes'] ?? ''),
            $logId,
            $userId,
        ]);
 
        recalcInternshipHours($userId, $pdo);
 
        // Return updated internship totals so the frontend can refresh
        $stmt = $pdo->prepare("
            SELECT total_hours, days_present FROM internships WHERE intern_id=? LIMIT 1
        ");
        $stmt->execute([$userId]);
        $internship = $stmt->fetch(PDO::FETCH_ASSOC);
 
        return [
            'success'     => true,
            'log_id'      => $logId,
            'time_in'     => $timeIn,
            'time_out'    => $timeOut,
            'total_hours' => $totalHours,
            'internship'  => $internship,
        ];
    } catch (PDOException $e) {
        error_log('updateTimeLog(): '.$e->getMessage());
        return ['success'=>false,'error'=>'Database error.'];
    }
}
 
/**
 * Recompute total_hours and days_present in the internships table.
 * Called after any time log change.
 */
function recalcInternshipHours(int $userId, PDO $pdo): void
{
    $pdo->prepare("
        UPDATE internships
        SET
            total_hours  = COALESCE(
                (SELECT SUM(total_hours) FROM time_logs
                 WHERE intern_id=? AND total_hours IS NOT NULL), 0),
            days_present = (
                SELECT COUNT(*) FROM time_logs
                WHERE intern_id=? AND time_out IS NOT NULL)
        WHERE intern_id=?
    ")->execute([$userId, $userId, $userId]);
}

//INtern Documents 
/**
 * Retrieves all document submissions filed by an individual intern.
 *
 * @param int $internId The database ID of the active intern.
 * @return array Collection of tracking rows.
 */
function getInternDocuments(int $internId): array
{
    $pdo = getDB();
    $sql = "SELECT id, document_type AS type, file_path, file_name AS file, 
                   notes, status, feedback, DATE_FORMAT(submitted_at, '%b %e, %Y') AS submitted 
            FROM intern_documents 
            WHERE intern_id = :intern_id
            ORDER BY id DESC";
            
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':intern_id' => $internId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("Database error in getInternDocuments: " . $e->getMessage());
        return [];
    }
}


/**
 * Handles the multi-part payload uploading for intern documents,
 * saving files securely and assigning the correct coordinator mapping.
 *
 * @param int $internId The database ID of the active intern.
 * @param string $type The document type designation name (e.g., 'TOR').
 * @param array $fileMeta The native PHP $_FILES metadata subarray structure.
 * @param string $notes Optional comment/description text provided by the intern.
 * @param int|null $coordinatorId The ID of the assigned coordinator tracking this intern.
 * @return array A response array indicating transaction status.
 */

 /*
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
*/
function uploadInternDocument(
    int $internId,
    string $type,
    array $fileMeta,
    string $notes,
    ?int $coordinatorId
): array
{
    $pdo = getDB();

    /*
     * =========================================================
     * 1. INITIAL UPLOAD VALIDATION
     * =========================================================
     */

    if (
        !isset($fileMeta['error']) ||
        $fileMeta['error'] !== UPLOAD_ERR_OK
    ) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   =>
                'The uploaded file exceeds the server upload limit configuration.',

            UPLOAD_ERR_FORM_SIZE  =>
                'The uploaded file exceeds the HTML form directive limit.',

            UPLOAD_ERR_PARTIAL    =>
                'The file was only partially uploaded.',

            UPLOAD_ERR_NO_FILE    =>
                'No file was uploaded.',

            UPLOAD_ERR_NO_TMP_DIR =>
                'Missing a temporary folder on the server.',

            UPLOAD_ERR_CANT_WRITE =>
                'Failed to write the uploaded file.'
        ];

        $errMsg =
            $errorMessages[$fileMeta['error']]
            ?? 'Unknown upload error occurred.';

        return [
            'success' => false,
            'message' => $errMsg
        ];
    }


    /*
     * =========================================================
     * 2. FILE TYPE VALIDATION
     * =========================================================
     */

    $allowedExts = [
        'pdf',
        'doc',
        'docx',
        'png',
        'jpg',
        'jpeg'
    ];

    $originalFileName = $fileMeta['name'] ?? '';

    $info = pathinfo($originalFileName);

    $ext = strtolower(
        $info['extension'] ?? ''
    );

    if (!in_array($ext, $allowedExts, true)) {
        return [
            'success' => false,
            'message' =>
                'Invalid file type. Extensions allowed: ' .
                implode(', ', $allowedExts)
        ];
    }


    /*
     * =========================================================
     * 3. FILE SIZE VALIDATION
     * =========================================================
     */

    if (($fileMeta['size'] ?? 0) > 10 * 1024 * 1024) {
        return [
            'success' => false,
            'message' =>
                'File exceeds maximum 10 megabyte boundary limit.'
        ];
    }


    /*
     * =========================================================
     * 4. VERIFY TEMPORARY FILE
     * =========================================================
     */

    $temporaryFile = $fileMeta['tmp_name'] ?? '';

    if (
        empty($temporaryFile) ||
        !is_uploaded_file($temporaryFile)
    ) {
        return [
            'success' => false,
            'message' =>
                'The uploaded temporary file could not be verified.'
        ];
    }


    /*
     * =========================================================
     * 5. DETERMINE MIME TYPE
     * =========================================================
     *
     * Use the actual file contents rather than trusting the
     * MIME type supplied by the browser.
     */

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    if ($finfo === false) {
        return [
            'success' => false,
            'message' =>
                'Unable to determine the uploaded file type.'
        ];
    }

    $mimeType = finfo_file(
        $finfo,
        $temporaryFile
    );

    finfo_close($finfo);

    if (!$mimeType) {
        $mimeType = 'application/octet-stream';
    }


    /*
     * =========================================================
     * 6. GENERATE UNIQUE FILE NAME
     * =========================================================
     */

    try {

        $uniqueName =
            'doc_' .
            bin2hex(random_bytes(16)) .
            '.' .
            $ext;

    } catch (Exception $e) {

        error_log(
            'Failed to generate unique filename: ' .
            $e->getMessage()
        );

        return [
            'success' => false,
            'message' =>
                'Failed to generate a secure filename.'
        ];
    }


    /*
     * =========================================================
     * 7. SUPABASE STORAGE PATH
     * =========================================================
     *
     * Example:
     *
     * documents/25/doc_a83f92....pdf
     */

    $storagePath =
        'documents/' .
        $internId .
        '/' .
        $uniqueName;


    /*
     * =========================================================
     * 8. CHECK EXISTING DOCUMENT
     * =========================================================
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
            ':type'      => $type
        ]);

        $existingRow =
            $checkStmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {

        error_log(
            'Database error checking existing document: ' .
            $e->getMessage()
        );

        return [
            'success' => false,
            'message' =>
                'Unable to check existing document record.'
        ];
    }


    /*
     * =========================================================
     * 9. UPLOAD NEW FILE TO SUPABASE
     * =========================================================
     *
     * This uses supabase.php.
     */

    $uploadResult = uploadToSupabase(
        $temporaryFile,
        $storagePath,
        $mimeType
    );

    if (!$uploadResult['success']) {

        error_log(
            'Supabase document upload failed: ' .
            ($uploadResult['message'] ?? 'Unknown error')
        );

        return $uploadResult;
    }


    /*
     * =========================================================
     * 10. UPDATE / INSERT DATABASE RECORD
     * =========================================================
     */

    try {

        if ($existingRow) {

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
                    $originalFileName,

                ':notes' =>
                    empty($notes)
                        ? null
                        : $notes,

                ':coordinator_id' =>
                    $coordinatorId,

                ':id' =>
                    $existingRow['id']
            ]);

        } else {

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
                    $originalFileName,

                ':notes' =>
                    empty($notes)
                        ? null
                        : $notes,

                ':coordinator_id' =>
                    $coordinatorId
            ]);
        }


        /*
         * =====================================================
         * 11. DELETE OLD FILE
         * =====================================================
         *
         * Only do this AFTER the database has successfully
         * been updated.
         */

        if (
            $existingRow &&
            !empty($existingRow['file_path']) &&
            $existingRow['file_path'] !== $storagePath
        ) {

            $deleteSuccess =
                deleteFromSupabase(
                    $existingRow['file_path']
                );

            if (!$deleteSuccess) {

                error_log(
                    'Warning: New document saved, but old ' .
                    'Supabase file could not be deleted: ' .
                    $existingRow['file_path']
                );
            }
        }


        /*
         * =====================================================
         * 12. SUCCESS
         * =====================================================
         */

        return [
            'success' => true,
            'message' =>
                'Document submitted successfully.',
            'file_path' =>
                $storagePath
        ];

    } catch (PDOException $e) {

        /*
         * Database failed AFTER the new Supabase upload.
         *
         * Remove the newly uploaded file so it doesn't
         * become an orphaned Supabase object.
         */

        deleteFromSupabase($storagePath);

        error_log(
            'Database error in uploadInternDocument: ' .
            $e->getMessage()
        );

        return [
            'success' => false,
            'message' =>
                'A backend application storage failure occurred.'
        ];
    }
}

function uploadInternDocument1(
int $internId,
string $type,
array $fileMeta,
string $notes,
?int $coordinatorId
): array {


$pdo = getDB();

/*
============================================================
FILE UPLOAD SETTINGS
============================================================
*/

$uploadDir   = dirname(__DIR__) . '/uploads/documents/';
$maxBytes    = 10485760; // 10 MB
$allowedExts = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'];


/*
============================================================
1. VALIDATE FILE UPLOAD
============================================================
*/

if (
    !isset($fileMeta['error']) ||
    !isset($fileMeta['name']) ||
    !isset($fileMeta['tmp_name']) ||
    !isset($fileMeta['size'])
) {
    return [
        'success' => false,
        'message' => 'Invalid file upload data.'
    ];
}


$errorCode = (int) $fileMeta['error'];

if ($errorCode !== UPLOAD_ERR_OK) {

    $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the form upload limit.',
        UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write the file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
    ];

    return [
        'success' => false,
        'message' => $errorMessages[$errorCode]
            ?? 'Unknown upload error (' . $errorCode . ').'
    ];
}


/*
============================================================
2. VALIDATE FILE SIZE
============================================================
*/

$fileSize = (int) $fileMeta['size'];

if ($fileSize <= 0) {
    return [
        'success' => false,
        'message' => 'The uploaded file is empty.'
    ];
}

if ($fileSize > $maxBytes) {
    return [
        'success' => false,
        'message' => 'File exceeds the 10 MB limit.'
    ];
}


/*
============================================================
3. VALIDATE FILE EXTENSION
============================================================
*/

$originalFileName = basename($fileMeta['name']);

$ext = strtolower(
    pathinfo($originalFileName, PATHINFO_EXTENSION)
);

if (!in_array($ext, $allowedExts, true)) {

    return [
        'success' => false,
        'message' => 'Invalid file type. Allowed types: ' .
            implode(', ', $allowedExts)
    ];
}


/*
============================================================
4. MAKE SURE THE UPLOAD DIRECTORY EXISTS
============================================================

Same approach as upload_report.php
*/

if (!is_dir($uploadDir)) {

    if (!mkdir($uploadDir, 0755, true)) {

        error_log(
            'uploadInternDocument: cannot create ' .
            $uploadDir
        );

        return [
            'success' => false,
            'message' => 'Server error: cannot create upload folder.'
        ];
    }
}


/*
============================================================
5. GENERATE UNIQUE FILENAME

Same style as upload_report.php
============================================================
*/

$storedFileName =
    uniqid('doc_', true) . '.' . $ext;

$targetPath = $uploadDir . $storedFileName;


/*
============================================================
6. DETECT MIME TYPE

Same approach as upload_report.php
============================================================
*/

$mimeType = 'application/octet-stream';

if (function_exists('finfo_open')) {

    $fi = finfo_open(FILEINFO_MIME_TYPE);

    if ($fi !== false) {

        $detectedMime = finfo_file(
            $fi,
            $fileMeta['tmp_name']
        );

        if ($detectedMime !== false) {
            $mimeType = $detectedMime;
        }

        finfo_close($fi);
    }

} elseif (!empty($fileMeta['type'])) {

    $mimeType = $fileMeta['type'];
}


/*
============================================================
7. DATABASE TRANSACTION

Same structure as upload_report.php
============================================================
*/

try {

    $pdo->beginTransaction();


    /*
    --------------------------------------------------------
    CHECK IF THIS DOCUMENT ALREADY EXISTS
    --------------------------------------------------------
    */

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
        ':type'      => $type
    ]);

    $existingRow = $checkStmt->fetch(PDO::FETCH_ASSOC);


    /*
    --------------------------------------------------------
    MOVE THE FILE

    Same location strategy as upload_report.php
    --------------------------------------------------------
    */

    if (!move_uploaded_file(
        $fileMeta['tmp_name'],
        $targetPath
    )) {

        throw new RuntimeException(
            'Could not save uploaded file: ' .
            $originalFileName
        );
    }


    /*
    --------------------------------------------------------
    UPDATE EXISTING DOCUMENT
    --------------------------------------------------------
    */

    if ($existingRow) {

        $oldFileName = $existingRow['file_path'];

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
            ':file_path'      => $storedFileName,
            ':file_name'      => $originalFileName,
            ':notes'          => trim($notes) !== ''
                ? trim($notes)
                : null,
            ':coordinator_id' => $coordinatorId,
            ':id'             => $existingRow['id']
        ]);


        /*
        ----------------------------------------------------
        DELETE OLD FILE ONLY AFTER DATABASE UPDATE
        ----------------------------------------------------
        */

        if (!empty($oldFileName)) {

            $oldPath =
                $uploadDir .
                basename($oldFileName);

            if (
                file_exists($oldPath) &&
                is_file($oldPath)
            ) {
                @unlink($oldPath);
            }
        }


    } else {

        /*
        ----------------------------------------------------
        INSERT NEW DOCUMENT
        ----------------------------------------------------
        */

        $sql = "
            INSERT INTO intern_documents (
                intern_id,
                document_type,
                file_path,
                file_name,
                notes,
                status,
                coordinator_id
            )
            VALUES (
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
            ':intern_id'      => $internId,
            ':type'           => $type,
            ':file_path'      => $storedFileName,
            ':file_name'      => $originalFileName,
            ':notes'          => trim($notes) !== ''
                ? trim($notes)
                : null,
            ':coordinator_id' => $coordinatorId
        ]);
    }


    /*
    ========================================================
    COMMIT DATABASE TRANSACTION
    ========================================================
    */

    $pdo->commit();


    return [
        'success' => true,
        'message' => 'Document submitted successfully.',
        'file_name' => $originalFileName,
        'stored_file' => $storedFileName,
        'mime_type' => $mimeType
    ];


} catch (Throwable $e) {

    /*
    ========================================================
    ROLLBACK DATABASE
    ========================================================
    */

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    /*
    ========================================================
    DELETE NEW FILE IF DATABASE OPERATION FAILED
    ========================================================
    */

    if (
        file_exists($targetPath) &&
        is_file($targetPath)
    ) {
        @unlink($targetPath);
    }


    error_log(
        'uploadInternDocument: ' .
        $e->getMessage()
    );


    return [
        'success' => false,
        'message' => 'Document submission failed: ' .
            $e->getMessage()
    ];
}

}


/**
 * Retrieves all files submitted by interns assigned to the currently logged-in coordinator.
 * Pulls the coordinator's ID directly from the active session.
 *
 * @return array
 */
function getCoordinatorDocuments(): array
{
    $pdo = getDB();

    // Grab the logged-in coordinator's ID directly from the session
    $coordinatorId = $_SESSION['user']['id'] ?? null;

    if (!$coordinatorId) {
        error_log("getCoordinatorDocuments called without an active session.");
        return [];
    }

    $sql = "SELECT d.id, 
                   d.intern_id AS internId, 
                   u.name AS internName, 
                   p.course AS dept,
                   d.document_type AS type, 
                   d.file_path, 
                   d.file_name AS file, 
                   d.status, 
                   IFNULL(d.feedback, '') AS feedback,
                   DATE_FORMAT(d.submitted_at, '%b %e, %Y') AS submitted
            FROM intern_documents d
            JOIN users u 
                ON d.intern_id = u.id
            LEFT JOIN intern_profiles p 
                ON d.intern_id = p.user_id
            WHERE d.coordinator_id = :coordinator_id
            ORDER BY d.id DESC";

    try {
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':coordinator_id' => $coordinatorId
        ]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debugging
        error_log("Coordinator ID: " . $coordinatorId);
        error_log("Documents Found: " . count($results));

        return $results ?: [];

    } catch (PDOException $e) {

        // Show actual SQL error while developing
        die("Database error in getCoordinatorDocuments: " . $e->getMessage());

        // Production version:
        // error_log("Database error in getCoordinatorDocuments: " . $e->getMessage());
        // return [];
    }
}

/**
 * Updates review metrics, status changes, and notes on an intern's checklist submission.
 */
function reviewInternDocument(int $docId, string $status, string $feedback, int $coordinatorId): array
{
    $pdo = getDB();
    $allowed = ['pending', 'approved', 'rejected'];
    if (!in_array($status, $allowed)) {
        return ['success' => false, 'message' => 'Invalid status option provided.'];
    }

    $sql = "UPDATE intern_documents 
            SET status = :status, 
                feedback = :feedback, 
                reviewed_at = NOW() 
            WHERE id = :id AND coordinator_id = :coordinator_id";

    try {
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':status'         => $status,
            ':feedback'       => empty($feedback) ? null : $feedback,
            ':id'             => $docId,
            ':coordinator_id' => $coordinatorId
        ]);

        if ($result && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Review updated successfully!'];
        }
        return ['success' => false, 'message' => 'No modifications made or record authorization failure.'];
    } catch (PDOException $e) {
        error_log("Database error in reviewInternDocument: " . $e->getMessage());
        return ['success' => false, 'message' => 'A backend application storage failure occurred.'];
    }
}

// ============================================================
//  SCHOOL YEAR FUNCTIONS
//  Append these to functions.php
// ============================================================

/**
 * Calculate the current Philippine school year.
 * School year starts June (month 6).
 * June 2025 – May 2026  → "2025-2026"
 *
 * @param int|null $month  Override month for testing (1-12)
 * @param int|null $year   Override year for testing
 */
function getCurrentSchoolYear(?int $month = null, ?int $year = null): string
{
    $month = $month ?? (int) date('n');
    $year  = $year  ?? (int) date('Y');

    if ($month >= 6) {
        return $year . '-' . ($year + 1);
    }
    return ($year - 1) . '-' . $year;
}

/**
 * Get all distinct school years that have internship records,
 * sorted newest first.
 * Returns array of strings: ["2025-2026", "2024-2025", ...]
 */
function getSchoolYears(): array
{
    $pdo = getDB();
    try {
        $stmt = $pdo->query("
            SELECT DISTINCT school_year
            FROM internships
            WHERE school_year IS NOT NULL
            ORDER BY school_year DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log('getSchoolYears(): ' . $e->getMessage());
        return [getCurrentSchoolYear()];
    }
}

/**
 * Get all active interns filtered by school year.
 * Used by the coordinator Interns section.
 *
 * @param string $schoolYear  e.g. "2025-2026"  (empty = current year)
 */
function getInternsBySchoolYear(string $schoolYear = ''): array
{
    $pdo = getDB();

    if (empty($schoolYear)) {
        $schoolYear = getCurrentSchoolYear();
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.name,
                u.email,
                u.is_active,
                ip.school,
                ip.course,
                ip.year_level,
                ip.phone,
                ip.required_hours,
                ip.joined_date,
                i.id            AS internship_id,
                i.school_year,
                i.position,
                i.supervisor,
                i.start_date,
                i.end_date,
                i.status        AS internship_status,
                i.total_hours,
                i.days_present,
                i.reports_submitted,
                c.name          AS company_name,
                c.address       AS company_address,
                c.phone         AS company_phone,
                c.email         AS company_email
            FROM users u
            JOIN intern_profiles ip  ON ip.user_id  = u.id
            JOIN internships     i   ON i.intern_id  = u.id
            LEFT JOIN companies  c   ON c.id         = i.company_id
            WHERE u.role     = 'intern'
              AND u.is_active = 1
              AND i.school_year = :school_year
              AND i.id = (
                  SELECT id FROM internships
                  WHERE intern_id = u.id
                  ORDER BY created_at DESC
                  LIMIT 1
              )
            ORDER BY u.name ASC
        ");
        $stmt->execute([':school_year' => $schoolYear]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('getInternsBySchoolYear(): ' . $e->getMessage());
        return [];
    }
}

/**
 * Auto-assign school_year when a new internship row is created.
 * Call this from register.php / createInternship().
 * Uses the internship start_date if provided, otherwise today.
 *
 * @param int         $internshipId  The newly inserted internship row ID
 * @param string|null $startDate     YYYY-MM-DD or null
 */
function assignSchoolYear(int $internshipId, ?string $startDate = null): void
{
    $pdo = getDB();

    if ($startDate) {
        $month = (int) date('n', strtotime($startDate));
        $year  = (int) date('Y', strtotime($startDate));
    } else {
        $month = (int) date('n');
        $year  = (int) date('Y');
    }

    $schoolYear = getCurrentSchoolYear($month, $year);

    $pdo->prepare("
        UPDATE internships
        SET school_year = ?
        WHERE id = ?
    ")->execute([$schoolYear, $internshipId]);
}