<?php
session_start();
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');
error_reporting(E_ALL);
include '../includes/db.php';

// Protect page
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch announcements
$announcements = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Intern Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="navbar">
    Intern Dashboard
</div>

<div class="container">

    <!-- ANNOUNCEMENTS -->
    <div class="card">
        <h2>Announcements</h2>
        <?php while($row = mysqli_fetch_assoc($announcements)) { ?>
            <p><strong><?php echo $row['title']; ?></strong></p>
            <p><?php echo $row['message']; ?></p>
            <hr>
        <?php } ?>
    </div>

    <div class="flex">

        <!-- PROFILE -->
        <div class="card">
            <h2>Edit Profile</h2>
            <form action="update_profile.php" method="POST">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <button type="submit">Update Profile</button>
            </form>
        </div>

        <!-- COMPANY INFO -->
        <div class="card">
            <h2>Company Info</h2>
            <form action="update_company.php" method="POST">
                <input type="text" name="company_name" placeholder="Company Name" required>
                <input type="text" name="supervisor_name" placeholder="Supervisor Name" required>
                <input type="text" name="supervisor_contact" placeholder="Supervisor Contact" required>
                <button type="submit">Save</button>
            </form>
        </div>

    </div>

    <div class="flex">

        <!-- TIME LOG -->
        <div class="card">
            <h2>Time Log</h2>
            <form action="time_log.php" method="POST">
                <button name="time_in">Time In</button>
                <button name="time_out">Time Out</button>
            </form>
        </div>

        <!-- WEEKLY REPORT -->
        <div class="card">
            <h2>Upload Weekly Report</h2>
            <form action="upload_report.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="report" required>
                <button type="submit">Upload</button>
            </form>
        </div>

    </div>

</div>

</body>
</html>