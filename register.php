<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];

    // Überprüfen Sie die Passwortstärke
    if (strlen($password) < 12 || !preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/", $password)) {
        $_SESSION['error'] = "Das Passwort muss mindestens 12 Zeichen lang sein und Großbuchstaben, Kleinbuchstaben, Zahlen und Sonderzeichen enthalten.";
        header("Location: register.php");
        exit();
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Generieren Sie das Schlüsselpaar
    $config = array(
        "digest_alg" => "sha256",
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );
    $res = openssl_pkey_new($config);
    openssl_pkey_export($res, $privateKey);
    $publicKey = openssl_pkey_get_details($res)['key'];

    // Verschlüsseln Sie den privaten Schlüssel mit dem Benutzerpasswort
    $encrypted_private_key = openssl_encrypt($privateKey, 'AES-256-CBC', $password, 0, substr(md5($username), 0, 16));

    $conn->begin_transaction();

    try {
        // Fügen Sie den Benutzer in die MySQL-Datenbank ein
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password_hash);
        $stmt->execute();
        $user_id = $stmt->insert_id;
        $stmt->close();

        // Speichern Sie die Schlüssel in der MySQL-Datenbank
        $stmt = $conn->prepare("INSERT INTO user_keys (user_id, public_key, encrypted_private_key) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $publicKey, $encrypted_private_key);
        $stmt->execute();
        $stmt->close();

        // Speichern Sie den öffentlichen Schlüssel in Redis
        $redis->set("user:$user_id:public_key", $publicKey);

        $conn->commit();

        $_SESSION['message'] = "Registrierung erfolgreich. Bitte melden Sie sich an.";
        header("Location: login.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Registration error: " . $e->getMessage());
        $_SESSION['error'] = "Bei der Registrierung ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.";
        header("Location: register.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/src/style.css">
    <link rel="icon" type="image/x-icon" href="/src/octocrypt-logo.webp">
    <title>Registrierung - OctoCrypt</title>
</head>
<body>
    <nav>
        <img src="/src/octocrypt-logo.webp" height="64" width="64" alt="OctoCrypt Logo">
        <h1>OctoCrypt.eu</h1>
    </nav>
    <div class="app">
        <div class="container">
            <h1>Registrierung</h1>
            <?php
            if (isset($_SESSION['error'])) {
                echo "<p class='error'>" . htmlspecialchars($_SESSION['error']) . "</p>";
                unset($_SESSION['error']);
            }
            ?>
            <form method="post" onsubmit="return validateForm()">
                <input type="text" name="username" id="username" placeholder="Benutzername" required>
                <input type="email" name="email" id="email" placeholder="E-Mail-Adresse" required>
                <input type="password" name="password" id="password" placeholder="Passwort" required>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Passwort bestätigen" required>
                <div>
                    <input type="checkbox" id="agb" name="agb" required>
                    <label for="agb">Ich akzeptiere die <a href="/legal/agb" target="_blank">AGBs</a> und die <a href="/legal/datenschutz" target="_blank">Datenschutzerklärung</a>.</label>
                </div>
                <input type="submit" value="Registrieren">
            </form>
            <p>Bereits registriert? <a href="login.php">Anmelden</a></p>
        </div>
    </div>
    <footer>
        <a href="/legal/impressum">Impressum</a> - 
        <a href="/legal/datenschutz">Datenschutz</a> - 
        <a href="/legal/agb">AGBs</a>
    </footer>

    <script>
    function validateForm() {
        var password = document.getElementById("password").value;
        var confirmPassword = document.getElementById("confirm_password").value;
        var passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/;

        if (password !== confirmPassword) {
            alert("Die Passwörter stimmen nicht überein.");
            return false;
        }

        if (!passwordRegex.test(password)) {
            alert("Das Passwort muss mindestens 12 Zeichen lang sein und Großbuchstaben, Kleinbuchstaben, Zahlen und Sonderzeichen enthalten.");
            return false;
        }

        return true;
    }
    </script>
</body>
</html>
