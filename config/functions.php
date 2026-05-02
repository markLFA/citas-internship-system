<?php
require 'db.php';
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
        SELECT course, year_level, phone, required_hours, joined_date
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
            WHERE user_id = ?
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
