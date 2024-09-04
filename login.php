<?php
session_start();
require_once 'config.php';

function handleLoginRequest($conn) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => "Invalid credentials"]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => "Invalid credentials"]);
    }

    $stmt->close();
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    handleLoginRequest($conn);
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
    <nav>
        <img src="/src/octocrypt-logo.webp" height="64" width="64" alt="OctoCrypt Logo">
        <h1>OctoCrypt.eu</h1>
    </nav>
    <div class="app">
        <div class="container">
            <h1>Login</h1>
            <form method="post" id="loginForm">
                <input type="text" name="username" placeholder="Benutzername" required>
                <input type="password" name="password" placeholder="Passwort" required>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="submit" value="Einloggen">
            </form>
            <p>Noch kein Konto? <a href="register.php">Registrieren</a></p>
        </div>
    </div>
    <footer>
        <a href="/legal/impressum">Impressum</a> - 
        <a href="/legal/datenschutz">Datenschutz</a> - 
        <a href="/legal/agb">AGBs</a>
    </footer>

    <script>
    document.getElementById('loginForm').addEventListener('submit', async function(event) {
        event.preventDefault();
        const formData = new FormData(this);

        try {
            const response = await fetch('login.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                window.location.href = 'index.php';
            } else {
                alert(result.error);
            }
        } catch (error) {
            alert('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
        }
    });
    </script>
</body>
</html>
