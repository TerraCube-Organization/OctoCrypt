<?php
// get_messages.php
session_start();
$servername = "localhost";
$username = "skrime_user_3171_4909_octocrypt";
$password ="ZRj6GYCNMHaJcwmlODiUllzEt8eOUAs7ecrGn8NicmGzVIsAZlPsthehn6QHxUYhVCOi0D3PI9IO373EqjjdrZP8sGXTtI3yg4wKsTm7N3PmmY4qZsBPlsU4yZnexR5n";
$dbname = "skrime_user_3171_4909_octocrypt";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$chatId = $_GET['chatId'];

$stmt = $conn->prepare("SELECT m.message, m.timestamp, u.username 
                        FROM messages m 
                        JOIN users u ON m.user_id = u.user_id 
                        WHERE m.chat_id = ? 
                        ORDER BY m.timestamp DESC 
                        LIMIT 50");
$stmt->bind_param("s", $chatId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode($messages);

$stmt->close();
$conn->close();
?>