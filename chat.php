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

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$chatId = $_GET['id'];
$inviteCode = isset($_GET['invite']) ? $_GET['invite'] : null;

// Check if user is a member of the chat
$stmt = $conn->prepare("SELECT is_admin FROM user_chats WHERE user_id = ? AND chat_id = ?");
$stmt->bind_param("is", $user_id, $chatId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // User is not a member, check invite code
    if ($inviteCode) {
        // Verify invite code and add user to chat
        $stmt = $conn->prepare("INSERT INTO user_chats (user_id, chat_id, invite_code) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $chatId, $inviteCode);
        if ($stmt->execute()) {
            // Send welcome message
            $welcomeMsg = "User " . $_SESSION['username'] . " has joined the chat.";
            $stmt = $conn->prepare("INSERT INTO messages (chat_id, user_id, message) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $chatId, $user_id, $welcomeMsg);
            $stmt->execute();
        } else {
            echo "Invalid invite code";
            exit();
        }
    } else {
        echo "You don't have access to this chat";
        exit();
    }
}

$chatId = $_GET['id'];
$inviteCode = isset($_GET['invite']) ? $_GET['invite'] : null;

$validAccess = false;

if ($inviteCode) {
    $stmt = $conn->prepare("SELECT max_uses, uses FROM invites WHERE chat_id = ? AND invite_code = ?");
    $stmt->bind_param("ss", $chatId, $inviteCode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['uses'] < $row['max_uses']) {
            // Increase the usage count
            $stmt = $conn->prepare("UPDATE invites SET uses = uses + 1 WHERE chat_id = ? AND invite_code = ?");
            $stmt->bind_param("ss", $chatId, $inviteCode);
            $stmt->execute();
            $validAccess = true;
        } else {
            echo "This invite code has been used too many times.";
        }
    } else {
        echo "Invalid invite code.";
    }
}

$stmt = $conn->prepare("SELECT group_name, password, invite_code FROM chats WHERE chat_id = ?");
$stmt->bind_param("s", $chatId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $groupName = $row['group_name'];
    $hashedPassword = $row['password'];
    $storedInviteCode = $row['invite_code'];

    if ($inviteCode && $inviteCode === $storedInviteCode) {
        $validAccess = true;
    }
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verschlüsselter Chat</title>
        <link rel="stylesheet" href="/src/style.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    </head>
    <body>
        <div class="container">
            <h1>Verschlüsselter Chat</h1>
            <?php if ($validAccess): ?>
                <button id="createInviteButton">Einladungscode erstellen</button>
                <div id="inviteForm" style="display:none;">
                    <input type="password" id="invitePassword" placeholder="Chat-Passwort">
                    <input type="number" id="maxUses" placeholder="Maximale Nutzungen">
                    <button onclick="createInvite()">Erstellen</button>
                </div>
                <div id="inviteResult"></div>
                <div id="chatArea">
                    <div id="messages"></div>
                    <input type="text" id="messageInput" placeholder="Nachricht eingeben">
                    <button onclick="sendMessage()">Senden</button>
                </div>
            <?php else: ?>
                <input type="password" id="password" placeholder="Chat-Passwort eingeben">
                <button onclick="joinChat()">Beitreten</button>
                <div id="chatArea" style="display:none;">
                    <div id="messages"></div>
                    <input type="text" id="messageInput" placeholder="Nachricht eingeben">
                    <button onclick="sendMessage()">Senden</button>
                </div>
            <?php endif; ?>
        </div>
        <script>
            let chatPassword = '';
            const chatId = '<?php echo $chatId; ?>';
            const encryptedGroupName = '<?php echo $groupName; ?>';
            const hashedPassword = '<?php echo $hashedPassword; ?>';

            <?php if ($validAccess): ?>
                chatPassword = '<?php echo $inviteCode; ?>';
                startMessagePolling();
            <?php else: ?>
                function joinChat() {
                    chatPassword = document.getElementById('password').value;
                    fetch('verify_password.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `chatId=${chatId}&password=${encodeURIComponent(chatPassword)}`
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            document.getElementById('chatArea').style.display = 'block';
                            document.getElementById('password').style.display = 'none';
                            document.querySelector('button').style.display = 'none';
                            startMessagePolling();
                        } else {
                            alert('Falsches Passwort.');
                        }
                    });
                }
            <?php endif; ?>

            function sendMessage() {
                const message = document.getElementById('messageInput').value;
                const encryptedMessage = CryptoJS.AES.encrypt(message, chatPassword).toString();
                
                fetch('send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `chatId=${chatId}&message=${encodeURIComponent(encryptedMessage)}`
                }).then(response => response.text())
                  .then(result => {
                      if (result === 'success') {
                          document.getElementById('messageInput').value = '';
                      } else {
                          console.error('Fehler beim Senden der Nachricht:', result);
                          alert('Fehler beim Senden der Nachricht.');
                      }
                  });
            }

            function startMessagePolling() {
                setInterval(() => {
                    fetch(`get_messages.php?chatId=${chatId}`)
                        .then(response => response.json())
                        .then(messages => {
                            const messagesDiv = document.getElementById('messages');
                            messagesDiv.innerHTML = '';
                            messages.forEach(msg => {
                                try {
                                    const decryptedMessage = CryptoJS.AES.decrypt(msg.message, chatPassword).toString(CryptoJS.enc.Utf8);
                                    messagesDiv.innerHTML += `<p><strong>${msg.timestamp}</strong>: ${decryptedMessage}</p>`;
                                } catch (error) {
                                    console.error('Fehler beim Entschlüsseln einer Nachricht');
                                }
                            });
                        });
                }, 1000); // Poll every second
            }

            document.getElementById('createInviteButton').addEventListener('click', function() {
                document.getElementById('inviteForm').style.display = 'block';
            });

            function createInvite() {
                const password = document.getElementById('invitePassword').value;
                const maxUses = document.getElementById('maxUses').value;
                fetch('create_invite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `chatId=${chatId}&password=${encodeURIComponent(password)}&maxUses=${maxUses}`
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        document.getElementById('inviteResult').innerHTML = `Neuer Einladungscode: ${result.inviteCode}`;
                        document.getElementById('inviteForm').style.display = 'none';
                    } else {
                        alert('Fehler beim Erstellen des Einladungscodes: ' + result.error);
                    }
                });
            }
        </script>
    </body>
    </html>
    <?php
} else {
    echo "Chat nicht gefunden.";
}

$stmt->close();
$conn->close();
?>