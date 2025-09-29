<?php
// chat/chat_messages.php
require_once __DIR__ . '/../includes/auth.php';
require_login('any');
require_once __DIR__ . '/../includes/config.php';

$user = $_SESSION['user'];
$partnerId = isset($_GET['partner']) ? (int)$_GET['partner'] : null;

// Employees always chat with admin (id=0)
if ($user['type'] === 'employee') {
    $partnerId = 0;
    $stmt = $pdo->prepare("
        SELECT * FROM messages 
        WHERE (sender_id = ? AND receiver_id = 0)
           OR (sender_id = 0 AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $stmt->execute([$user['id'], $user['id']]);
} else {
    // Admin side — must have a partner (employee id)
    if (!$partnerId) {
        exit; // no chat selected
    }
    $stmt = $pdo->prepare("
        SELECT * FROM messages
        WHERE (sender_id = ? AND receiver_id = 0)
           OR (sender_id = 0 AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $stmt->execute([$partnerId, $partnerId]);
}
$messages = $stmt->fetchAll();

// Output only messages HTML
foreach ($messages as $m):
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
