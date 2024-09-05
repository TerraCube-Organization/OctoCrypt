<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Hole die Chats des Benutzers
$stmt = $conn->prepare("
    SELECT c.id, c.name, uc.is_admin 
    FROM chats c 
    JOIN user_chats uc ON c.id = uc.chat_id 
    WHERE uc.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$chats = $result->fetch_all(MYSQLI_ASSOC);

// Hole den Benutzernamen
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'];
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/src/style.css">
    <link rel="icon" type="image/x-icon" href="/src/octocrypt-logo.webp">
    <title>OctoCrypt - Dashboard</title>
</head>
<body>
    <nav>
        <img src="/src/octocrypt-logo.webp" height="64" width="64" alt="OctoCrypt Logo">
        <h1>OctoCrypt.eu</h1>
    </nav>
    
    <div class="app">
        <h1>Willkommen, <?php echo htmlspecialchars($username); ?>!</h1>
        
        <div class="actions">
            <button onclick="createNewChat()">Neuen Chat erstellen</button>
            <a href="join.php" class="button">Chat beitreten</a>
            <a href="logout.php" class="button">Ausloggen</a>
        </div>

        <h2>📑 Chat Übersicht</h2>
        <div id="chatList">
            <?php if (empty($chats)): ?>
                <p>Sie haben noch keine Chats.</p>
            <?php else: ?>
                <div class="chatlist">
                    <?php foreach ($chats as $chat): ?>
                        <div class="chat-item">
                            <a href="chat.php?id=<?php echo htmlspecialchars($chat['id']); ?>">
                                <?php echo htmlspecialchars($chat['name']); ?>
                            </a>
                            <?php if ($chat['is_admin']): ?>
                                <span class="admin-badge">Admin</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <a href="/legal/impressum">Impressum</a> - 
        <a href="/legal/datenschutz">Datenschutz</a> - 
        <a href="/legal/agb">AGBs</a>
    </footer>

    <script>
    function createNewChat() {
        const chatName = prompt("Geben Sie einen Namen für den neuen Chat ein:");
        if (chatName) {
            fetch('create_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
                },
                body: JSON.stringify({ chatName: chatName }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Fehler beim Erstellen des Chats: ' + data.error);
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten');
            });
        }
    }
    </script>
</body>
</html>
