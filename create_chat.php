<?php
// create_chat.php
$servername = "localhost";
$username = "skrime_user_3171_4909_octocrypt";
$password = "ZRj6GYCNMHaJcwmlODiUllzEt8eOUAs7ecrGn8NicmGzVIsAZlPsthehn6QHxUYhVCOi0D3PI9IO373EqjjdrZP8sGXTtI3yg4wKsTm7N3PmmY4qZsBPlsU4yZnexR5n";
$dbname = "skrime_user_3171_4909_octocrypt";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$encryptedGroupName = $_POST['groupName'];
$chatPassword = $_POST['password'];

// Hash the password
$hashedPassword = password_hash($chatPassword, PASSWORD_DEFAULT);

$chatId = uniqid();
$inviteCode = bin2hex(random_bytes(8)); // Generate a random 16-character invite code

$stmt = $conn->prepare("INSERT INTO chats (chat_id, group_name, password, invite_code) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $chatId, $encryptedGroupName, $hashedPassword, $inviteCode);

if ($stmt->execute()) {
    echo json_encode(['chatId' => $chatId, 'inviteCode' => $inviteCode]);
} else {
    echo json_encode(['error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>