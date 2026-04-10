
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
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
    width: 320px;
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
</style>
</head>

<body>
<div class="container">
    <h2>CITAS</h2>

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