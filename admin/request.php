<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('admin');
require_once __DIR__ . '/../includes/config.php';

// Fetch pending requests
$stmt = $pdo->query("SELECT * FROM employees WHERE status='pending' ORDER BY created_at DESC");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Pending Requests - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #0a0a0a, #222);
      color: #f2f2f2;
      margin: 0;
      padding: 0;
    }

    h1 {
      text-align: center;
      margin: 30px 10px;
      color: #00ff90;
      font-size: 1.8rem;
      letter-spacing: 1px;
      animation: fadeInDown 0.7s ease;
    }

    .container {
      width: 95%;
      max-width: 1100px;
      margin: 0 auto 60px auto;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 20px;
      padding: 20px;
    }

    .card {
      background: rgba(0, 0, 0, 0.55);
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 0 15px rgba(0, 255, 100, 0.25);
      border: 1px solid rgba(0,255,100,0.2);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      position: relative;
      animation: fadeInUp 0.7s ease;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 0 25px rgba(0,255,100,0.4);
    }

    .field {
      margin-bottom: 12px;
    }

    .field strong {
      display: inline-block;
      width: 130px;
      color: #00ff90;
      font-weight: 600;
    }

    .actions {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-top: 15px;
    }

    button {
      border: none;
      padding: 10px 18px;
      font-size: 15px;
      border-radius: 25px;
      cursor: pointer;
      color: white;
      transition: all 0.3s ease;
    }

    button.approve {
      background: linear-gradient(90deg, #00c851, #007e33);
      box-shadow: 0 0 8px rgba(0,200,80,0.5);
    }

    button.approve:hover {
      background: linear-gradient(90deg, #00ff6a, #00c851);
      transform: scale(1.05);
      box-shadow: 0 0 15px rgba(0,255,100,0.8);
    }

    button.deny {
      background: linear-gradient(90deg, #ff4444, #cc0000);
      box-shadow: 0 0 8px rgba(255,80,80,0.5);
    }

    button.deny:hover {
      background: linear-gradient(90deg, #ff7777, #ff4444);
      transform: scale(1.05);
      box-shadow: 0 0 15px rgba(255,100,100,0.8);
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeInDown {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 600px) {
      h1 {
        font-size: 1.4rem;
      }
      .field strong {
        width: 100px;
      }
      .card {
        padding: 18px;
      }
    }
  </style>
</head>
<body>
  <?php include '../includes/header.php'; ?>

  <h1>Pending Registration Requests</h1>

  <?php if (!$requests): ?>
    <p style="text-align:center; font-size:1.2em; margin-top:40px;">No pending requests 🎉</p>
  <?php else: ?>
    <div class="container">
      <?php foreach ($requests as $r): ?>
        <div class="card">
          <div class="field"><strong>ID:</strong> <?= htmlspecialchars($r['id']) ?></div>
          <div class="field"><strong>Full Name:</strong> <?= htmlspecialchars($r['fullname']) ?></div>
          <div class="field"><strong>Phone:</strong> <?= htmlspecialchars($r['phone']) ?></div>
          <div class="field"><strong>Role:</strong> <?= htmlspecialchars($r['role']) ?></div>
          <div class="field"><strong>Category:</strong> <?= htmlspecialchars($r['category']) ?></div>
          <div class="field"><strong>Shift:</strong> <?= htmlspecialchars($r['shift']) ?></div>
          <div class="field"><strong>Place of Work:</strong> <?= htmlspecialchars($r['place_of_work']) ?></div>

          <div class="actions">
            <form action="request_approve.php" method="post">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button type="submit" class="approve">Approve ✅</button>
            </form>
            <form action="request_deny.php" method="post">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button type="submit" class="deny">Deny ❌</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</body>
</html>
