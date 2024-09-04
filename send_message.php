<?php
session_start();
// send_message.php
$servername = "localhost";
$username = "skrime_user_3171_4909_octocrypt";
$password = "ZRj6GYCNMHaJcwmlODiUllzEt8eOUAs7ecrGn8NicmGzVIsAZlPsthehn6QHxUYhVCOi0D3PI9IO373EqjjdrZP8sGXTtI3yg4wKsTm7N3PmmY4qZsBPlsU4yZnexR5n";
$dbname = "skrime_user_3171_4909_octocrypt";

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$chatId = $_POST['chatId'];
$message = $_POST['message'];

$stmt = $conn->prepare("INSERT INTO messages (chat_id, user_id, message) VALUES (?, ?, ?)");
$stmt->bind_param("sis", $chatId, $user_id, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>