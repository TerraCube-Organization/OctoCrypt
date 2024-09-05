<?php
session_start();
require_once 'config.php';

function handleLoginRequest($conn, $redis) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'error' => "Invalid CSRF token"]);
        exit();
    }

    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            
            // Generieren eines neuen Session-Tokens
            $session_token = bin2hex(random_bytes(32));
            $_SESSION['session_token'] = $session_token;
            
            // Speichern des Session-Tokens in Redis mit Ablaufzeit
            $redis->setex("user:{$user['id']}:session_token", 3600, $session_token);

            // Aktualisieren des letzten Login-Zeitpunkts
            $stmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => "Ungültige Anmeldeinformationen"]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => "Ungültige Anmeldeinformationen"]);
    }
    $stmt->close();
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    handleLoginRequest($conn, $redis);
}

// Generieren eines neuen CSRF-Tokens bei jedem Seitenaufruf
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Implementieren Sie hier eine Funktion zur Ratenbegrenzung
if (checkRateLimit($_SERVER['REMOTE_ADDR'])) {
    http_response_code(429);
    die("Zu viele Anmeldeversuche. Bitte versuchen Sie es später erneut.");
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/src/style.css">
    <link rel="icon" type="image/x-icon" href="/src/octocrypt-logo.webp">
    <title>Login - OctoCrypt</title>
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
            <p><a href="forgot_password.php">Passwort vergessen?</a></p>
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
