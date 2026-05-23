<?php
require 'db.php';
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
                    address = ?
                WHERE id = ?
            ");

            $stmt->execute([
                trim($internship['company_name'] ?? ''),
                trim($internship['address'] ?? ''),
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
/**
 * Fetches all weekly reports assigned to the logged-in coordinator.
 *
 * @param PDO $pdo An active PDO database connection instance.
 * @param int $coordinatorId The ID of the signed-in coordinator.
 * @return array An array of associative arrays containing the report data.
 */
function getReportsByCoordinator(PDO $pdo, int $coordinatorId): array 
{
    // Prepared statement to securely query the reports
    $sql = "SELECT id, intern_id, week_label, week_start, description, status, feedback, uploaded_at, reviewed_at, reviewed_by 
            FROM weekly_reports 
            WHERE coordinator_id = :coordinator_id
            ORDER BY uploaded_at DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':coordinator_id' => $coordinatorId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle database errors gracefully or log them
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