<?php
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
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            echo "Login successful";
        } else {
            echo "Invalid password";
        }
    } else {
        echo "User not found";
    }

    $stmt->close();
}
?>

<form method="post">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="submit" value="Login">
</form>