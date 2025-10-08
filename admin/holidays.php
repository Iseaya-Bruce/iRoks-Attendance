<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$message = '';
$err = '';

// Handle add holiday
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_holiday'])) {
    $name = trim($_POST['holiday_name'] ?? '');
    $date = trim($_POST['holiday_date'] ?? '');
    $desc = trim($_POST['holiday_description'] ?? ''); // <<< prevents "undefined variable"

    if ($name === '' || $date === '') {
        $err = "⚠️ Please provide a holiday name and date.";
    } else {
        // Check if an entry already exists for this date
        $check = $pdo->prepare("SELECT COUNT(*) FROM holidays WHERE holiday_date = ?");
        $check->execute([$date]);
        $exists = (int)$check->fetchColumn();

        if ($exists > 0) {
            $err = "⚠️ A holiday is already registered for {$date}.";
        } else {
            try {
                $ins = $pdo->prepare("INSERT INTO holidays (holiday_name, holiday_date, description) VALUES (?, ?, ?)");
                $ins->execute([$name, $date, $desc]);
                $message = "✅ Holiday added successfully for {$date}.";
            } catch (PDOException $e) {
                // Log $e->getMessage() to a file in production. Show friendly message to user.
                $err = "❌ Failed to add holiday. Please try again.";
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $del = $pdo->prepare("DELETE FROM holidays WHERE id = ?");
    $del->execute([$id]);
    $message = "🗑️ Holiday deleted.";
}

// Fetch holidays
$stmt = $pdo->query("SELECT * FROM holidays ORDER BY holiday_date ASC");
$holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Manage Holidays</title>
<style>
/* keep the styling simple / similar to your theme */
body { background:#111; color:#fff; font-family: Arial, sans-serif; }
.container { max-width:780px; margin:36px auto; padding:20px; background:#1a1a1a; border-radius:10px; box-shadow:0 0 12px rgba(50,205,50,0.12); }
h2 { color:#32CD32; text-align:center; margin-bottom:16px; }
form { display:flex; flex-direction:column; gap:8px; margin-bottom:16px; }
.row { display:flex; gap:8px; }
input[type="text"], input[type="date"], textarea { padding:8px; border-radius:6px; border:1px solid rgba(50,205,50,0.12); background:#222; color:#fff; }
button { background:#32CD32; color:#111; border:none; padding:8px 12px; border-radius:6px; cursor:pointer; }
button:hover { background:#28a428; }
.table { width:100%; border-collapse:collapse; margin-top:12px; }
.table th, .table td { padding:8px; border:1px solid rgba(255,255,255,0.03); text-align:left; }
.table th { background:#222; color:#32CD32; }
.msg { text-align:center; margin-bottom:10px; }
.err { color:#ffb3b3; text-align:center; margin-bottom:10px; }
.delete-btn { color:#ff7777; text-decoration:none; font-weight:600; }
@media (min-width:700px) { .row > * { flex:1; } .row textarea { flex:2; } }
</style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
<div class="container">
  <h2>🗓 Manage Holidays</h2>

  <?php if ($message): ?>
    <div class="msg"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="err"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <form method="post" novalidate>
    <div style="text-align:right"><a href="fetch_holidays.php" class="btn-refresh">🔄 Fetch Latest Holidays</a></div>
    <div class="row">
      <input type="text" name="holiday_name" placeholder="Holiday name" required>
      <input type="date" name="holiday_date" required>
    </div>
    <textarea name="holiday_description" placeholder="Optional description" rows="2"></textarea>
    <div style="text-align:right">
      <button type="submit" name="add_holiday">➕ Add Holiday</button>
    </div>
  </form>

  <?php if (count($holidays) === 0): ?>
    <p style="text-align:center; color:#ccc;">No holidays defined yet.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr><th>ID</th><th>Name</th><th>Date</th><th>Description</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($holidays as $h): ?>
          <tr>
            <td><?= (int)$h['id'] ?></td>
            <td><?= htmlspecialchars($h['holiday_name'] ?? $h['description']) ?></td>
            <td><?= htmlspecialchars($h['holiday_date']) ?></td>
            <td><?= htmlspecialchars($h['description'] ?? '') ?></td>
            <td><a class="delete-btn" href="?delete=<?= (int)$h['id'] ?>" onclick="return confirm('Delete this holiday?')">Delete</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

</div>
</body>
</html>
