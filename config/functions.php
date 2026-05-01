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