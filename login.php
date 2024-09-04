<?php
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            exit();
        } else {
            echo json_encode(['success' => false, 'error' => "Invalid password"]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => "User not found"]);
    }
    $stmt->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/src/style.css">
    <link rel="icon" type="image/x-icon" href="/src/octocrypt-logo.webp">
    <title>Login</title>
</head>
<body>
<nav><img src="/src/octocrypt-logo.webp" height=64px width=64px><h1>OctoCrypt.eu </h1></nav>
<div class="app">
    <div class="container">
        <center><h1>Login</h1></center>
        <?php 
        if (isset($_SESSION['message'])) {
            echo "<p>{$_SESSION['message']}</p>";
            unset($_SESSION['message']);
        }
        if (isset($error)) echo "<p style='color:red;'>$error</p>"; 
        ?>
        <form method="post" id="loginForm">
            <center>
                <input type="text" name="username" placeholder="Benutzername" required>
                <br>
                <input type="password" name="password" placeholder="Passwort" required>
                <br>
                <input type="submit" value="Einloggen">
            </center>
            <br>
        </form>
        <p>Noch kein Konto? <a href="register.php">Registrieren</a></p>
    </div>
</div>
<center><footer><a href="/legal/impressum">Impressum</a> - <a href="/legal/datenschutz">Datenschutz</a> - <a href="/legal/agb">AGBs</a></footer></center>

<script>
async function handleLogin(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    try {
        const response = await fetch('login.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Login erfolgreich
            const keysManaged = await manageKeys(formData.get('password'), result.encrypted_key);
            if (keysManaged) {
                window.location.href = 'index.php';
            } else {
                alert('Login erfolgreich, aber es gab ein Problem mit der Schlüsselverwaltung. Bitte versuchen Sie es erneut.');
            }
        } else {
            // Login fehlgeschlagen
            alert(result.error);
        }
    } catch (error) {
        console.error('Login error:', error);
        alert('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
    }
}
</script>
?>
