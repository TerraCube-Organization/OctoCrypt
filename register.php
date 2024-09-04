<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Registration successful. Please log in.";
        header("Location: login.php");
        exit();
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/src/style.css">
    <link rel="icon" type="image/x-icon" href="/src/octocrypt-logo.webp">
    <title>Registrierung</title>
</head>
<body>
    <nav>
        <img src="/src/octocrypt-logo.webp" height="64" width="64" alt="OctoCrypt Logo">
        <h1>OctoCrypt.eu</h1>
    </nav>
    <div class="app">
        <div class="container">
            <h1>Registrierung</h1>
            <form method="post">
                <input type="text" name="username" placeholder="Benutzername" required>
                <input type="password" name="password" placeholder="Passwort" required>
                <div>
                    <input type="checkbox" id="agb" name="agb" required>
                    <label for="agb">Ich akzeptiere die AGBs.</label>
                </div>
                <input type="submit" value="Registrieren">
            </form>
            <p>Bereits registriert? <a href="login.php">Login</a></p>
        </div>
    </div>
    <footer>
        <a href="/legal/impressum">Impressum</a> - 
        <a href="/legal/datenschutz">Datenschutz</a> - 
        <a href="/legal/agb">AGBs</a>
    </footer>
</body>
</html>
