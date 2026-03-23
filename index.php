<?php
// Start session
session_start();

// Sample login logic (temporary - replace with database later)
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // TEMP credentials (for testing only)
    $valid_user = "admin";
    $valid_pass = "1234";

    if ($username === $valid_user && $password === $valid_pass) {
        $_SESSION["user"] = $username;
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CITAS Internship Monitoring System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            height: 100vh;
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 320px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            text-align: center;
        }

        .login-container h2 {
            margin-bottom: 10px;
            color: #1e3a8a;
        }

        .login-container p {
            font-size: 12px;
            margin-bottom: 20px;
            color: #555;
        }

        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .input-group label {
            font-size: 12px;
            color: #333;
        }

        .input-group input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .btn {
            width: 100%;
            padding: 10px;
            background: #1e3a8a;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover {
            background: #0f172a;
        }

        .error {
            color: red;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .footer {
            margin-top: 15px;
            font-size: 11px;
            color: #888;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>CITAS</h2>
    <p>Internship Monitoring & Data Analytics System</p>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn">Login</button>
    </form>

    <div class="footer">
        © <?php echo date("Y"); ?> CITAS System
    </div>
</div>

</body>
</html>