<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>CITAS Registration</title>

<style>
body {
    height: 100vh;
    background: linear-gradient(135deg, rgb(142, 38, 12), rgb(224, 86, 17));
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    font-family: Arial;
    margin: 0;
}

.container {
    background: white;
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 420px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    text-align: center;
}

h2 {
    margin-bottom: 5px;
}

.subtitle {
    font-size: 13px;
    color: #555;
    margin-bottom: 15px;
}

.notice {
    font-size: 12px;
    background: #fff3cd;
    padding: 8px;
    border-radius: 6px;
    margin-bottom: 10px;
    color: #856404;
}

input, select {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
}

button {
    width: 100%;
    padding: 10px;
    background: orange;
    border: none;
    color: white;
    cursor: pointer;
    border-radius: 6px;
}

button:hover {
    background: darkorange;
}

.error { color: red; font-size: 12px; }
.success { color: green; font-size: 12px; }

.hint {
    font-size: 11px;
    color: #777;
    text-align: left;
    margin-top: -5px;
}

.footer {
    margin-top: 20px;
    font-size: 12px;
    color: #ddd;
    text-align: center;
}
</style>
</head>

<body>

<div class="container">
    <h2>CITAS Internship System</h2>
    <div class="subtitle">Monitoring & Data Analytics Platform</div>

    <div class="notice">
        ⚠️ This registration is for a <strong>Capstone Project (Academic Use Only)</strong>.
    </div>

    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="name" placeholder="Full Name" required>

        <input type="email" name="email" placeholder="School Email Address" required>
        <div class="hint">Use your official school email (if applicable)</div>

        <select name="role" required>
            <option value="">Select Role</option>
            <option value="student">Student Intern</option>
            <option value="faculty">Faculty Adviser</option>
            <option value="coordinator">Internship Coordinator</option>
        </select>

        <input type="password" name="password" placeholder="Create Password" required>
        <div class="hint">Minimum 6 characters recommended</div>

        <button type="submit">Create Account</button>
    </form>

    <p>Already have an account? <a href="index.php">Login here</a></p>
</div>

<div class="footer">
    <p>Developed by BSIT Students | Capstone Project 2026</p>
    <p>CITAS Internship Monitoring System</p>
</div>

</body>
</html>