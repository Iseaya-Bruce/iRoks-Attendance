<?php
// chat/chat.php
require_once __DIR__ . '/../includes/auth.php';
require_login('any'); // both employee & admin
require_once __DIR__ . '/../includes/config.php';

$user = $_SESSION['user'];
$chatPartnerId = null;
$chatPartnerName = null;

// Employee → only chats with Admin
if ($user['type'] === 'employee') {
    $chatPartnerId = 0;
    $chatPartnerName = "CEO (Admin)";
} else {
    // Admin → show employee list
    if (isset($_GET['id'])) {
        $chatPartnerId = (int) $_GET['id'];
        $empStmt = $pdo->prepare("SELECT fullname FROM employees WHERE id = ?");
        $empStmt->execute([$chatPartnerId]);
        $emp = $empStmt->fetch();
        if ($emp) {
            $chatPartnerName = $emp['fullname'];
        }
    }
}

// Handle new message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['message'] ?? '');

    if ($user['type'] === 'employee') {
        $senderId = $user['id'];
        $receiverId = 0;
    } else {
        $senderId = 0;
        $receiverId = (int) $_GET['id'];
    }

    $filePath = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/chat_files/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $filename = time() . "_" . basename($_FILES['attachment']['name']);
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
            $filePath = 'uploads/chat_files/' . $filename;
        }
    }

    if ($msg !== '' || $filePath !== null) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, file_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$senderId, $receiverId, $msg, $filePath]);
    }
    header("Location: chat.php" . ($user['type'] === 'admin' ? "?id={$receiverId}" : ""));
    exit;
}

// Fetch employees (for admin sidebar)
$employees = [];
if ($user['type'] === 'admin') {
    $empStmt = $pdo->query("SELECT id, fullname FROM employees ORDER BY fullname ASC");
    $employees = $empStmt->fetchAll();
}

// Fetch messages
$messages = [];
if ($chatPartnerId !== null) {
    $stmt = $pdo->prepare("
        SELECT * FROM messages 
        WHERE (sender_id = ? AND receiver_id = 0)
           OR (sender_id = 0 AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $stmt->execute([$chatPartnerId, $chatPartnerId]);
    $messages = $stmt->fetchAll();
}

// Mark messages as read when opening the chat
if ($user['type'] === 'employee') {
    $upd = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = 0");
    $upd->execute([$user['id']]);
} else {
    $upd = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = 0 AND sender_id = ?");
    $upd->execute([$chatPartnerId]);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { font-family: Arial, sans-serif; background: #111; margin:0; padding:0; }
        .chat-layout { display:flex; height:100vh; }
        .sidebar {
            width: 250px;
            background:#222;
            color:#fff;
            overflow-y:auto;
            border-right:1px solid #333;
        }
        .sidebar h2 {
            padding:15px;
            margin:0;
            border-bottom:1px solid #333;
            font-size:16px;
        }
        .sidebar a {
            display:block;
            padding:12px 15px;
            color:#ddd;
            text-decoration:none;
        }
        .sidebar a:hover, .sidebar a.active {
            background:#28a745;
            color:#fff;
        }
        .chat-container { flex:1; display:flex; flex-direction:column; }
        .chat-header { padding:15px; background: #333; font-weight:bold; border-bottom:1px solid #ddd; }
        .chat-box { flex:1; padding:15px; overflow-y:auto; background-image: url('../assets/img/Chat_bg.jpg'); }
        .message { margin:6px 0; max-width:70%; padding:10px; border-radius:12px; word-wrap:break-word; }
        .me { background: #034b14ff; color:#fff; margin-left:auto; }
        .other { background: #282829ff; color: #fff; margin-right:auto; }
        .chat-form { display:flex; padding:10px; border-top:1px solid #ddd; background: #7c7b7bff; }
        .chat-form input[type="text"] { flex:1; padding:10px; border-radius:12px; border:1px solid #ccc; }
        .chat-form button { margin-left:8px; background: #034b14ff; color:#fff; border:none; padding:0 18px; border-radius:12px; cursor:pointer; }
    </style>
</head>
<body>
<div class="chat-layout">

    <!-- Sidebar for Admin -->
    <?php if ($user['type'] === 'admin'): ?>
    <div class="sidebar">
        <h2>Employees</h2>
        <?php foreach ($employees as $e): ?>
            <?php
            // Count unread messages from this employee to admin
            $cntStmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM messages 
                WHERE sender_id = ? AND receiver_id = 0 AND is_read = 0
            ");
            $cntStmt->execute([$e['id']]);
            $empUnread = $cntStmt->fetchColumn();
            ?>
            <a href="chat.php?id=<?= $e['id'] ?>" class="<?= ($chatPartnerId == $e['id'] ? 'active' : '') ?>">
                <?= htmlspecialchars($e['fullname']) ?>
                <?php if ($empUnread > 0): ?>
                    <span style="
                        background:red;
                        color:white;
                        font-size:11px;
                        border-radius:50%;
                        padding:2px 6px;
                        margin-left:6px;
                    ">
                        <?= $empUnread ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Chat window -->
    <div class="chat-container">
        <?php if ($chatPartnerId === null): ?>
            <div class="chat-header">Select an employee to chat with</div>
        <?php else: ?>
            <div class="chat-header">Chat with <?= htmlspecialchars($chatPartnerName) ?></div>
            <div class="chat-box" id="chat-box">
                <?php foreach ($messages as $m): ?>
                    <?php
                    $isMe = false;
                    if ($user['type'] === 'employee' && $m['sender_id'] == $user['id']) $isMe = true;
                    if ($user['type'] === 'admin' && $m['sender_id'] == 0) $isMe = true;
                    ?>
                    <div class="message <?= $isMe ? 'me' : 'other' ?>">
                        <?php if (!empty($m['message'])): ?>
                            <?= nl2br(htmlspecialchars($m['message'])) ?><br>
                        <?php endif; ?>
                        <?php if (!empty($m['file_path'])): ?>
                            📄 <a href="../<?= htmlspecialchars($m['file_path']) ?>" target="_blank">
                                <?= basename($m['file_path']) ?>
                            </a><br>
                        <?php endif; ?>
                        <small style="font-size:10px;color:#666;"><?= htmlspecialchars($m['created_at']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="post" class="chat-form" enctype="multipart/form-data">
                <input type="text" name="message" placeholder="Type your message...">
                
                <!-- Paperclip upload -->
                <label for="file-upload" style="cursor:pointer; margin:0 10px; font-size:20px;">
                    📎
                </label>
                <input id="file-upload" type="file" name="attachment" accept="application/pdf,image/*" style="display:none;">
                
                <button type="submit">Send</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
setInterval(() => {
    let box = document.getElementById("chat-box");
    if (box) {
        fetch("chat_messages.php?partner=<?= $chatPartnerId ?? '' ?>")
            .then(res => res.text())
            .then(html => {
                box.innerHTML = html;
                box.scrollTop = box.scrollHeight; // always scroll down
            });
    }
}, 1000);
</script>

</body>
</html>
