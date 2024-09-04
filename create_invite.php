<?php
// create_invite.php
$servername = "localhost";
$username = "skrime_user_3171_4909_octocrypt";
$password = "ZRj6GYCNMHaJcwmlODiUllzEt8eOUAs7ecrGn8NicmGzVIsAZlPsthehn6QHxUYhVCOi0D3PI9IO373EqjjdrZP8sGXTtI3yg4wKsTm7N3PmmY4qZsBPlsU4yZnexR5n";
$dbname = "skrime_user_3171_4909_octocrypt";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$chatId = $_POST['chatId'];
$password = $_POST['password'];
$maxUses = intval($_POST['maxUses']);

// Überprüfen Sie das Passwort
$stmt = $conn->prepare("SELECT password FROM chats WHERE chat_id = ?");
$stmt->bind_param("s", $chatId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $hashedPassword = $row['password'];

    if (password_verify($password, $hashedPassword)) {
        // Passwort ist korrekt, erstellen Sie einen neuen Einladungscode
        $inviteCode = bin2hex(random_bytes(8));

        $stmt = $conn->prepare("INSERT INTO invites (chat_id, invite_code, max_uses, uses) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("ssi", $chatId, $inviteCode, $maxUses);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'inviteCode' => $inviteCode]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Fehler beim Speichern des Einladungscodes']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Falsches Passwort']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Chat nicht gefunden']);
}

$stmt->close();
$conn->close();
?>



