<?php

$page = $_GET['page'] ?? 'home';

function layout($title, $content) {
    echo "<!DOCTYPE html>\n<html>\n<head>\n<meta charset='UTF-8'>\n<title>$title</title>\n<style>
        body { font-family: Arial; margin: 0; background: #f5f5f5; }
        header { background: #333; color: white; padding: 15px; }
        nav a { color: white; margin-right: 15px; text-decoration: none; }
        .container { padding: 20px; }
        .card { background: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgb(18, 18, 18); }
        input, textarea { width: 100%; padding: 8px; margin: 5px 0; }
        button { padding: 10px; background: rgb(255, 134, 42); color: white; border: none; }
    </style>\n</head>\n<body>\n<header>\n<h2>CITAS Internship Monotoring and Data Analytics System</h2>\n<nav>\n<a href='?page=home'>Home</a>\n<a href='?page=about'>About</a>\n<a href='?page=contact'>Contact</a>\n</nav>\n</header>\n<div class='container'>\n$content\n</div>\n</body>\n</html>";
}

if ($page === 'home') {
    $content = "<div class='card'><h3>Welcome to hdwhjbcdwjhd</h3><p>This is a simple PHP website.</p></div>";
    layout('Home', $content);
}
elseif ($page === 'about') {
    $content = "<div class='card'><h3>About</h3><p>TBy Zaido.</p></div>";
    layout('About', $content);
}
elseif ($page === 'contact') {
    $message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = htmlspecialchars($_POST['name']);
        $msg = htmlspecialchars($_POST['message']);
        $message = "<p><strong>Thanks, $name!</strong> Your message has been received.</p>";
    }

    $content = "
    <div class='card'>
        <h3>Contact</h3>
        $message
        <form method='POST'>
            <input type='text' name='name' placeholder='Your Name' required>
            <textarea name='message' placeholder='Your Message' required></textarea>
            <button type='submit'>Send</button>
        </form>
    </div>";

    layout('Contact', $content);
}
else {
    layout('404', "<div class='card'><h3>404</h3><p>Page not found.</p></div>");
}

?>