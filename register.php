<?php
// chat.php
session_start();
$servername = "localhost";
$username = "skrime_user_3171_4909_octocrypt";
$password = "ZRj6GYCNMHaJcwmlODiUllzEt8eOUAs7ecrGn8NicmGzVIsAZlPsthehn6QHxUYhVCOi0D3PI9IO373EqjjdrZP8sGXTtI3yg4wKsTm7N3PmmY4qZsBPlsU4yZnexR5n";
$dbname = "skrime_user_3171_4909_octocrypt";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Überprüfen Sie, ob der Benutzername bereits existiert
    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo "Username already exists";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $password);

        if ($stmt->execute()) {
            echo "Registration successful";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    $check_stmt->close();
}
?>

<form method="post">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="submit" value="Register">
</form>






