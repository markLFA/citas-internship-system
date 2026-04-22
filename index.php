<?php
session_start();
require 'config/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user["password_hash"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["name"] = $user["name"];
        $_SESSION["role"] = $user["role"];

        header("Location: pages/internPage.php");
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CITAS Login</title>
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

.error {
    color: red;
    font-size: 12px;
}
* {
    box-sizing: border-box;
}
</style>
</head>

<body>
<div class="container">
    <h2>CITAS Internship</h2>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>

        <button type="submit">Login</button>
    </form>

    <p>No account? <a href="signup.php">Sign up</a></p>
</div>
</body>
</html>