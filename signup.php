<?php
require 'config/db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST["name"];
    $email = $_POST["email"];
    $role = $_POST["role"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Email already exists!";
    } else {

        $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password, $role);

        if ($stmt->execute()) {
            $success = "Account created! You can now login.";
        } else {
            $error = "Registration failed!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>CITAS Sign Up</title>
<style>
body {
    height: 100vh;
    background: linear-gradient(135deg,rgb(142, 38, 12),rgb(224, 86, 17));
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: Arial;
}

.container {
    background: white;
    padding: 30px;
    border-radius: 12px;
    width: 90%;          /* takes 90% of screen width */
    max-width: 380px;    /* but won't stretch too wide on desktop */
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    text-align: center;
}
* {
    box-sizing: border-box;
}
input, select {
    width: 100%;
    padding: 8px;
    margin: 10px 0;
}

button {
    width: 100%;
    padding: 10px;
    background: orange;
    border: none;
    color: white;
    cursor: pointer;
}

.error { color: red; font-size: 12px; }
.success { color: green; font-size: 12px; }
</style>
</head>

<body>
<div class="container">
    <h2>Create Account</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>

        <select name="role" required>
            <option value="">Select Role</option>
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
            <option value="coordinator">Coordinator</option>
        </select>

        <input type="password" name="password" placeholder="Password" required>

        <button type="submit">Sign Up</button>
    </form>

    <p>Already have an account? <a href="index.php">Login</a></p>
</div>
</body>
</html>