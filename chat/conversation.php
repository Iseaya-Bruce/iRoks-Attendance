<?php
// chat/conversation.php
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
    header("Location: conversation.php" . ($user['type'] === 'admin' ? "?id={$receiverId}" : ""));
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
    if ($user['type'] === 'employee') {
        // Employee ↔ Admin (id = 0)
        $stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = 0)
               OR (sender_id = 0 AND receiver_id = ?)
            ORDER BY created_at ASC
        ");
        $stmt->execute([$user['id'], $user['id']]);
    } else {
        // Admin ↔ Employee
        $stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = 0)
               OR (sender_id = 0 AND receiver_id = ?)
            ORDER BY created_at ASC
        ");
        $stmt->execute([$chatPartnerId, $chatPartnerId]);
    }
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
        body {
        font-family: "Poppins", sans-serif;
        background: linear-gradient(135deg, #000000ff, #00d812ff);
        margin: 0;
        padding: 0;
        color: #fff;
        }

        .top-bar { display:flex; align-items:center; gap:12px; padding:10px; background:#1b1b1b; border-bottom:1px solid #333; }
        .back-btn { text-decoration:none; font-weight:700; border-radius:8px; }

        .chat-layout {
        display: flex;
        flex-direction: column;
        height: 100vh;
        }

        /* === CHAT HEADER === */
        .chat-header {
        background: rgba(7, 194, 32, 0.8);
        padding: 14px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        font-weight: 600;
        font-size: 18px;
        text-shadow: 0 0 4px rgba(255, 247, 247, 0.4);
        color: #000000;
        }

        /* === CHAT BOX === */
        .chat-box {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-image: url('../assets/img/Chat_bg.jpg');
            display: flex;               /* Add this */
            flex-direction: column;      /* Add this — makes messages stack vertically */
        }

        .message {
        display: inline-block;
        padding: 10px 14px;
        margin: 8px 0;
        border-radius: 14px;
        max-width: 70%;
        word-wrap: break-word;
        line-height: 1.4;
        font-size: 15px;
        font-weight: 500;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        /* My messages */
        .me {
        background: linear-gradient(135deg, #186e1dff, #00d812ff);
        color: #fff;
        margin-left: auto;
        border-top-right-radius: 0;
        }

        .message a {
            color: #ffffff;           /* make the link white */
            text-decoration: underline; /* underline it */
            font-weight: 600;         /* optional, makes it stand out */
        }

        .message a:hover {
            color: #15ff00ff;           /* light ocean-blue on hover */
            text-decoration-thickness: 2px; /* slightly thicker underline when hovering */
        }

        /* Other messages */
        .other {
        background: #000000;
        color: #05ff05ff;
        margin-right: auto;
        border-top-left-radius: 0;
        }

        .message small {
        display: block;
        text-align: right;
        font-size: 11px;
        color: rgba(255,255,255,0.5);
        margin-top: 4px;
        }

        /* === CHAT FORM === */
        .chat-form {
        display: flex;
        align-items: flex-end;
        padding: 10px;
        background: rgba(7, 194, 32, 0.8);
        border-top: 1px solid rgba(255,255,255,0.1);
        }

        /* Auto-size text box */
        .chat-form input[type="text"] {
        min-width: 100px;
        max-width: 80%;
        width: auto;
        flex-grow: 0;
        padding: 10px 12px;
        border-radius: 20px;
        border: none;
        background: #000000ff;
        color: #fff;
        font-family: inherit;
        font-size: 14px;
        outline: none;
        transition: width 0.3s ease;
        }

        /* Expand slightly while typing */
        .chat-form input[type="text"]:focus {
        background: #000000ff;
        width: 90%;
        }

        /* Paperclip and Send */
        .chat-form label {
        font-size: 22px;
        color: #90e0ef;
        margin: 0 8px;
        cursor: pointer;
        transition: color 0.3s ease;
        }
        .chat-form label:hover {
        color: #caf0f8;
        }

        .chat-form button {
        background: linear-gradient(135deg, #000000ff, #000000ff);
        color: #fff;
        border: none;
        padding: 10px 18px;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 600;
        transition: 0.3s ease, transform 0.1s ease;
        }
        .chat-form button:hover {
        background: linear-gradient(135deg, #909290ff, #29c536ff);
        transform: scale(1.05);
        }

        /* === EMPLOYEE CHIPS === */
        .month-scroll { display:flex; gap:10px; overflow-x:auto; padding:8px 4px 2px; margin: 4px 0; scrollbar-width: thin; scrollbar-color: #32CD32 #222; }
        .month-scroll::-webkit-scrollbar { height: 8px; }
        .month-scroll::-webkit-scrollbar-track { background: #222; border-radius: 999px; }
        .month-scroll::-webkit-scrollbar-thumb { background: #32CD32; border-radius: 999px; }
        .month-chip { flex:0 0 auto; padding:8px 12px; border-radius:999px; background:#222; color:#fff; border:1px solid rgba(255,255,255,0.1); text-decoration:none; font-weight:700; font-size:14px; box-shadow: 0 0 6px rgba(50,205,50,0.25) inset; white-space: nowrap; }
        .month-chip:hover { background:#2a2a2a; box-shadow: 0 0 8px rgba(50,205,50,0.45) inset; }
        .month-chip.active { background:#32CD32; color:#111; box-shadow: 0 0 12px rgba(50,205,50,0.65); }
        .month-chip .badge { background:red; color:white; font-size:11px; border-radius:999px; padding:2px 6px; margin-left:6px; }

        /* Mobile tweaks */
        @media (max-width: 768px) {
        .chat-form input[type="text"] {
            max-width: 70%;
        }
        }
    </style>
</head>
<body>
<div class="chat-layout">

    <!-- Top bar with back button and employee chips (admin) -->
    <div class="top-bar">
        <a href="<?= $user['type'] === 'admin' ? '../admin/dashboard.php' : '../employee/dashboard.php' ?>" class="btn back-btn">← Back to Dashboard</a>
        <?php if ($user['type'] === 'admin'): ?>
            <div class="month-scroll">
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
                    <a href="conversation.php?id=<?= $e['id'] ?>" class="month-chip<?= ($chatPartnerId == $e['id'] ? ' active' : '') ?>">
                        <?= htmlspecialchars($e['fullname']) ?>
                        <?php if ($empUnread > 0): ?><span class="badge"><?= $empUnread ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Chat window -->
    <div class="chat-container">
        <?php if ($chatPartnerId === null): ?>
            <div class="chat-header">Select an employee to chat with</div>
        <?php else: ?>
            <div class="chat-header">Chat with Boss<!--?= htmlspecialchars($chatPartnerName) ?--></div>
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
                        <small style="font-size:10px;color: #fcfcfcff;"><?= htmlspecialchars($m['created_at']) ?></small>
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
