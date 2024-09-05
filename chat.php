<?php
session_start();
require_once 'config.php';

if (!function_exists('requireLogin')) {
    die("requireLogin function not found. Check your config.php file.");
}

requireLogin();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die("Invalid request.");
}

$user_id = $_SESSION['user_id'];
$chat_id = $_GET['id'];

// Redis connection
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

try {
    $stmt = $conn->prepare("
        SELECT uc.is_admin, c.group_name
        FROM user_chats uc
        JOIN chats c ON uc.chat_id = c.chat_id
        WHERE uc.user_id = ? AND uc.chat_id = ?
    ");
    $stmt->bind_param("is", $user_id, $chat_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        die("Sie haben keinen Zugriff auf diesen Chat oder der Chat existiert nicht.");
    }

    $row = $result->fetch_assoc();
    $is_admin = $row['is_admin'];
    $group_name = $row['group_name'];

    // Fetch user's public key from Redis
    $user_public_key = $redis->get("user:$user_id:public_key");

    if (!$user_public_key) {
        header("Location: generate_keys.php");
        exit;
    }

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
} finally {
    if (isset($stmt)) {
        $stmt->close();
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
    <title><?php echo htmlspecialchars($group_name); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsencrypt/3.2.1/jsencrypt.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
</head>
<body>
<nav><img src="/src/octocrypt-logo.webp" height=64 width=64 alt="OctoCrypt Logo"><h1>OctoCrypt.eu </h1></nav>
    <a href="/app" class="minilink">← Zurück zum Dashboard</a> 
<h1><?php echo htmlspecialchars($group_name); ?></h1>
    <div class="chat">
        <div id="messages"></div>
    </div>
<div class="chatbar">
    <input style="width:60%;" type="text" id="messageInput" placeholder="Nachricht eingeben">
    <button onclick="sendMessage()">Senden</button>
    <?php if ($is_admin): ?>
        <button onclick="createInvite()">Einladungscode erstellen</button>
        <button onclick="openAdminDashboard()" class="admin-button">Admin Dashboard</button>
    <?php endif; ?>
    <button onclick="leaveChat()">Chat verlassen</button>
</div>
    <center><footer><a href="/legal/impressum">Impressum</a> - <a href="/legal/datenschutz">Datenschutz</a> - <a href="/legal/agb">AGBs</a></footer></center>

<script>
const chatId = <?php echo json_encode($chat_id); ?>;
const userPublicKey = <?php echo json_encode($user_public_key); ?>;
let privateKey = localStorage.getItem('privateKey');
if (!privateKey) {
    alert("Bitte generieren Sie zuerst Ihre Schlüssel.");
    window.location.href = 'generate_keys.php';
}

const jsEncrypt = new JSEncrypt();
jsEncrypt.setPrivateKey(privateKey);

let lastMessageId = 0;
let isFirstLoad = true;
const messageInput = document.getElementById("messageInput");
const messagesDiv = document.getElementById("messages");

const encryptMessage = async (message, recipientPublicKeys) => {
    const symmetricKey = CryptoJS.lib.WordArray.random(32);
    const iv = CryptoJS.lib.WordArray.random(16);

    const encryptedMessage = CryptoJS.AES.encrypt(message, symmetricKey, { 
        iv: iv,
        mode: CryptoJS.mode.CBC,
        padding: CryptoJS.pad.Pkcs7,
    }).toString();
    
    const encryptedKeys = {};
    for (const [userId, publicKey] of Object.entries(recipientPublicKeys)) {
        const jsEncrypt = new JSEncrypt();
        jsEncrypt.setPublicKey(publicKey);
        const keyToEncrypt = symmetricKey.toString() + iv.toString();
        encryptedKeys[userId] = jsEncrypt.encrypt(keyToEncrypt);
    }

    return { encryptedMessage, encryptedKeys };
};

const decryptMessage = (encryptedMessage, encryptedKey) => {
    try {
        const decryptedKey = jsEncrypt.decrypt(encryptedKey);
        if (!decryptedKey) {
            console.error("Failed to decrypt key");
            return null;
        }

        const symmetricKey = CryptoJS.enc.Hex.parse(decryptedKey.substr(0, 64));
        const iv = CryptoJS.enc.Hex.parse(decryptedKey.substr(64));
        
        const ciphertext = CryptoJS.enc.Base64.parse(encryptedMessage);
        
        const decryptedMessage = CryptoJS.AES.decrypt(
            { ciphertext: ciphertext },
            symmetricKey,
            { iv: iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 }
        ).toString(CryptoJS.enc.Utf8);
        
        if (!decryptedMessage) {
            console.error("Failed to decrypt message");
            return null;
        }
        
        return decryptedMessage;
    } catch (error) {
        console.error("Decryption error:", error);
        return null;
    }
};

const sendMessage = async () => {
    const message = messageInput.value.trim();
    if (!message) return;

    const recipientPublicKeys = await fetchRecipientPublicKeys();
    const { encryptedMessage, encryptedKeys } = await encryptMessage(message, recipientPublicKeys);

    fetch("send_message.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({chatId, encryptedMessage, encryptedKeys})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageInput.value = "";
            fetchMessages();
        } else {
            throw new Error(data.error);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("Fehler beim Senden der Nachricht: " + error.message);
    });
};

const fetchMessages = async () => {
    try {
        const response = await fetch(`get_messages.php?chatId=${encodeURIComponent(chatId)}&lastId=${lastMessageId}`);
        const data = await response.json();

        let newContent = "";
        for (const msg of data) {
            if (msg.message_id > lastMessageId) {
                let decryptedMessage;
                try {
                    decryptedMessage = decryptMessage(msg.encrypted_message, msg.encrypted_key);
                } catch (error) {
                    console.error("Decryption error for message:", msg.message_id, error);
                    decryptedMessage = "Entschlüsselungsfehler";
                }
                if (decryptedMessage) {
                    newContent += `<p><strong>${escapeHTML(msg.username)}</strong> (${escapeHTML(msg.timestamp)}): ${escapeHTML(decryptedMessage)}</p>`;
                    lastMessageId = msg.message_id;
                }
            }
        }

        if (newContent) {
            messagesDiv.insertAdjacentHTML('beforeend', newContent);
            if (!isFirstLoad) scrollToBottom();
        }

        if (isFirstLoad) {
            scrollToBottom();
            isFirstLoad = false;
        }
    } catch (error) {
        console.error("Error fetching messages:", error);
    }

    setTimeout(fetchMessages, 1000);
};

const fetchRecipientPublicKeys = async () => {
    const response = await fetch(`get_chat_members.php?chatId=${encodeURIComponent(chatId)}`);
    return await response.json();
};

const createInvite = () => {
    const maxUses = prompt("Maximale Anzahl der Nutzungen:");
    if (!maxUses) return;

    fetch("create_invite.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({chatId, maxUses})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Neuer Einladungscode: ${data.inviteCode}`);
        } else {
            throw new Error(data.error);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("Fehler beim Erstellen des Einladungscodes: " + error.message);
    });
};

const scrollToBottom = () => {
    const chatDiv = document.querySelector(".chat");
    chatDiv.scrollTop = chatDiv.scrollHeight;
};

const openAdminDashboard = () => {
    window.location.href = `chatadmin/dashboard?id=${encodeURIComponent(chatId)}`;
};

const leaveChat = () => {
    if (confirm("Möchten Sie diesen Chat wirklich verlassen?")) {
        fetch("leave_chat.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({chatId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/app';
            } else {
                throw new Error(data.error);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Fehler beim Verlassen des Chats: " + error.message);
        });
    }
};

const escapeHTML = str => str.replace(/[&<>'"]/g, 
    tag => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;'
    }[tag] || tag)
);

fetchMessages();
messageInput.addEventListener("keypress", e => {
    if (e.key === "Enter") sendMessage();
});
</script>
</body>
</html>
