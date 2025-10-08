<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('employee');
require_once __DIR__ . '/../includes/config.php';


if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'employee') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

// determine today's latest row for initial state
$todayStmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND work_date = CURDATE() ORDER BY id DESC LIMIT 1");
$todayStmt->execute([$user_id]);
$todayShift = $todayStmt->fetch(PDO::FETCH_ASSOC);
$isClockedIn = ($todayShift && $todayShift['clockout_time'] === null) ? true : false;
$lastClockIn = $todayShift['clockin_time'] ?? null;
$lastClockOut = $todayShift['clockout_time'] ?? null;

// Fetch employee
$stmt = $pdo->prepare(query: "SELECT * FROM employees WHERE id=?");
$stmt->execute([$user_id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch attendance
$stmt = $pdo->prepare("
    SELECT *,
    TIMESTAMPDIFF(MINUTE, clockin_time, clockout_time) AS minutes_worked
    FROM attendance
    WHERE employee_id=?
    ORDER BY work_date DESC
");
$stmt->execute([$user_id]);
$attendance = $stmt->fetchAll();

// Attendance percent for donut (admin logic replicated)
$monthStart = date("Y-m-01");
$monthEnd = date("Y-m-t");
// Count distinct worked days this month
$wdStmt = $pdo->prepare("SELECT COUNT(DISTINCT DATE(clockin_time)) FROM attendance WHERE employee_id=? AND clockin_time BETWEEN ? AND ?");
$wdStmt->execute([$user_id, $monthStart, $monthEnd]);
$workedDaysThisMonth = (int)$wdStmt->fetchColumn();

// Total working days (Mon–Sat)
$totalWorkingDays = 0;
$period = new DatePeriod(new DateTime($monthStart), new DateInterval('P1D'), new DateTime($monthEnd . ' +1 day'));
foreach ($period as $d) {
    if ((int)$d->format("N") < 7) { // 1..6 = Mon..Sat
        $totalWorkingDays++;
    }
}
$attendancePercent = $totalWorkingDays ? round(($workedDaysThisMonth / $totalWorkingDays) * 100, 1) : 0;

// Totals
$total_hours = 0;
$total_days = count($attendance);
foreach ($attendance as $row) {
    $total_hours += $row['minutes_worked'] / 60;
}

// Salary calculation
$hourly_rate = $emp['hourly_pay'] ?? 0;
$monthly_salary = $emp['monthly_pay'] ?? ($total_hours * $hourly_rate);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - iRoks</title>
    <!--link rel="stylesheet" href="../assets/css/style.css"-->
    <!-- jQuery (required for Lightbox) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Lightbox2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
   
 <style>
        body { 
    background: #111; 
    color: #fff; 
    font-family: Arial, sans-serif; 
}
.container { 
    max-width: 1000px; 
    margin: 30px auto; 
    padding: 20px; 
    background: #1a1a1a; 
    border-radius: 12px; 
}
h1, h2 { 
    color: #32CD32; 
}
.profile { 
    display: flex; 
    align-items: center; 
    gap: 15px; 
}
.profile img { 
    width: 80px; 
    height: 80px; 
    border-radius: 50%; 
    object-fit: cover; 
    border: 2px solid #32CD32; 
    z-index: 10; 
    animation: rgbGlow 5s infinite linear;
}

.summary { 
    margin-top: 20px; 
    background: #222; 
    padding: 15px; 
    border-radius: 8px; 
}
table { 
    width: 100%; 
    border-collapse: collapse; 
    margin-top: 20px; 
}
th, td { 
    padding: 10px; 
    box-shadow: 0 0 15px rgba(0, 255, 100, 0.25); border: 1px solid rgba(0,255,100,0.2); transition: transform 0.3s ease, box-shadow 0.3s ease; text-align: center; animation: fadeInUp 0.7s ease;
}
th { 
    background: #222; color: #32CD32;
}
tr:hover { 
    background: #222; 
}
.actions { 
    margin-top: 20px; 
    display: flex; 
    gap: 15px; 
}

/*
.btn{
    position: relative;
    width: 240px;
    height: 80px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 10px;
    cursor: pointer;
    overflow: hidden;
    font-size: 2em;
    transition: 0.5s;
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #32CD32;
}

.btn:hover{
    letter-spacing: 0.2em;
    color: #111;
}

.btn span{
    position: absolute;
    top: 0;
    width: 2px;
    height: 100%;
    background: #32CD32;
    pointer-events: none;
    z-index: -1;
    transform: scaleY(1);
    transform-origin: bottom;
}

.btn:hover span {
    transform: scaleY(1);
    transform-origin: top;
}

.btn span:nth-child(even) {
    transform-origin: top;
}

.btn:hover span:nth-child(even) {
    transform-origin: bottom;
} */

/* Buttons base */
/* 🌟 Shared glow for ALL buttons */
.btn, button, .btn-clock {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #32CD32;
    color: #111;
    padding: 10px 15px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 800;
    letter-spacing: 0.02em;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.15s ease, 0.2s ease;

    /* continuous glowing pulse */
    animation: pulseGlow 2.5s ease-in-out infinite;
    box-shadow: 0 0 8px rgba(50,205,50,0.4);
}
.btn:hover, button:hover, .btn-clock:hover {
    background: #2cbc2c;
    transform: translateY(-2px);
    box-shadow: 0 0 12px rgba(50,205,50,0.6), 0 0 20px rgba(50,205,50,0.4);
}
.btn:active, button:active, .btn-clock:active { transform: translateY(1px); }
.btn[disabled], .btn-clock:disabled, a.btn[aria-disabled="true"] {
    opacity: 0.6; cursor: not-allowed;
}

/* 🔄 Pulse animation */
@keyframes pulseGlow {
    0%, 100% { box-shadow: 0 0 8px rgba(50,205,50,0.3), 0 0 14px rgba(50,205,50,0.15); }
    50%      { box-shadow: 0 0 14px rgba(50,205,50,0.6), 0 0 28px rgba(50,205,50,0.35); }
}

/* 🔄 Circulating glow ring (hover only) */
.btn::before, button::before, .btn-clock::before {
    content: "";
    position: absolute;
    inset: -2px;
    padding: 2px;
    border-radius: inherit;
    background: conic-gradient(from 0deg,
        rgba(50,205,50,0) 0deg,
        rgba(255, 255, 255, 0.9) 60deg,
        rgba(50,205,50,0) 120deg,
        rgba(50,205,50,0) 360deg
    );
    -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
    -webkit-mask-composite: xor;
            mask-composite: exclude;
    opacity: 0;
    animation: spin 2s linear infinite;
    animation-play-state: paused;
    pointer-events: none;
}
.btn:hover::before, button:hover::before, .btn-clock:hover::before {
    opacity: 1;
    animation-play-state: running;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* 🕒 Clock button */
.btn-anim-clock .icon-clock {
    transform-origin: 50% 55%;
    animation: clock-rotate 8s linear infinite;
}
.btn-anim-clock:hover .icon-clock {
    animation-duration: 2.2s;
    filter: drop-shadow(0 0 6px rgba(50,205,50,0.6));
}
@keyframes clock-rotate {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}

/* ✈ Plane button */
.btn-anim-plane .icon-plane { transform: translateX(0) translateY(0) rotate(0deg); }
.btn-anim-plane:hover .icon-plane {
    animation: plane-leave 1.4s ease-in-out infinite;
}
@keyframes plane-leave {
    0%   { transform: translateX(0) translateY(0) rotate(0deg); opacity: 1; }
    55%  { transform: translateX(140%) translateY(-12%) rotate(12deg); opacity: 0.8; }
    56%  { transform: translateX(-10%) translateY(0) rotate(0deg); opacity: 0; }
    100% { transform: translateX(0) translateY(0) rotate(0deg); opacity: 1; }
}

/* 💬 Chat button (wiggle effect) */
.btn-chat:hover {
    animation: wiggle 0.4s ease-in-out infinite;
}
@keyframes wiggle {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(-5deg); }
    75% { transform: rotate(5deg); }
}

/* 📄 PDF button (page flip) */
.btn-pdf:hover {
    animation: flip 0.6s ease-in-out;
}
@keyframes flip {
    0% { transform: rotateY(0deg); }
    50% { transform: rotateY(180deg); }
    100% { transform: rotateY(0deg); }
}

/* 📷 Upload button (camera flash) */
.btn-upload:hover {
    animation: flash 0.8s ease-in-out;
}
@keyframes flash {
    0%, 100% { filter: brightness(1); }
    50%      { filter: brightness(2); }
}

@keyframes rgbGlow {
  0%   { box-shadow: 0 0 15px red, 0 0 30px red, 0 0 45px red; }
  25%  { box-shadow: 0 0 15px orange, 0 0 30px orange, 0 0 45px orange; }
  50%  { box-shadow: 0 0 15px lime, 0 0 30px lime, 0 0 45px lime; }
  75%  { box-shadow: 0 0 15px cyan, 0 0 30px cyan, 0 0 45px cyan; }
  100% { box-shadow: 0 0 15px red, 0 0 30px red, 0 0 45px red; }
}

.chart-glow {
  width: 150px;   /* match your chart canvas size */
  height: 150px;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  animation: rgbGlow 5s infinite linear;
  margin-left: 330px;
}

.comment-box { 
    margin-top: 20px; 
}
textarea { 
    width: 100%; 
    padding: 10px; 
    border-radius: 6px; 
    background: #222; 
    border: none; 
    color: #fff; 
}

/* ✅ Responsive Design */
@media (max-width: 768px) {
    .container {
        margin: 15px;
        padding: 15px;
    }

    .profile {
        flex-direction: column;
        text-align: center;
    }

    .profile img {
        width: 70px;
        height: 70px;
    }

    .actions {
        flex-direction: column;
        gap: 10px;
    }

    .btn, .btn-clock, button {
        width: 100%;
        text-align: center;
    }

    /* 🔹 Make tables scrollable */
    .table-wrapper {
        overflow-x: auto;
    }

    table {
        min-width: 600px; /* so it scrolls instead of shrinking too much */
    }

    table, th, td {
        font-size: 14px;
        padding: 8px;
    }

    .chart-glow {
      
        
        display: flex;
        
      
        margin-left: 20px;
        }
}

@media (max-width: 480px) {
    h1, h2 {
        font-size: 20px;
    }

    .container {
        padding: 10px;
    }

    .profile img {
        width: 60px;
        height: 60px;
    }

    table, th, td {
        font-size: 12px;
        padding: 6px;
    }
}

    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
<div class="container">
    <div class="profile">
        
        <?php if (!empty($emp['profile_pic'])): ?>
            <a href="../uploads/profiles/<?= htmlspecialchars($emp['profile_pic']) ?>" 
            class="zoomable-image" 
            data-lightbox="profile-<?= $emp['id'] ?>" 
            data-title="Profile Photo of <?= htmlspecialchars($emp['fullname']) ?>">
                <img src="../uploads/profiles/<?= htmlspecialchars($emp['profile_pic']) ?>" 
                    alt="Profile Photo" 
                    class="thumbnail-profile">
            </a>
        <?php else: ?>
            <span class="no-profile">No profile photo uploaded</span>
        <?php endif; ?>

        <div>
            <h1>Welcome, <?= htmlspecialchars($emp['fullname']) ?></h1>
            <p><strong>Role:</strong> <?= htmlspecialchars($emp['role']) ?> | 
               <strong>Shift:</strong> <?= htmlspecialchars($emp['shift']) ?> | 
               <strong>Place:</strong> <?= htmlspecialchars($emp['place_of_work']) ?></p>
        </div>

        <div class="chart-glow">
            <canvas id="workPercentageChart"></canvas>
            <!--p><strong>Work Attendance:</strong> <?= round(($total_days / date('t')) * 100, 1) ?>%</p-->
        </div>
    </div>

    <div class="summary">
        <p><strong>Total Days Worked:</strong> <?= $total_days ?></p>
        <p><strong>Total Hours Worked:</strong> <?= round($total_hours, 2) ?> h</p>
        <p><strong>Hourly Rate:</strong> SRD<?= number_format($hourly_rate, 2) ?></p>
        <p><strong>Estimated Monthly Salary:</strong> SRD<?= number_format($monthly_salary, 2) ?></p>

        <!-- Clock status here -->
        <p>
            <strong>Status:</strong>
            <span id="clockStatus">
            <?php
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id=? AND work_date=CURDATE()");
            $stmt->execute([$user_id]);
            $today = $stmt->fetch();

            if (!$today) {
                echo '<span style="color: orange; font-weight: bold;">⏳ Not Clocked In</span>';
            } elseif ($today && $today['clockout_time'] === null) {
                echo '<span style="color: limegreen; font-weight: bold;">✅ Clocked In at ' 
                    . htmlspecialchars(date('H:i', strtotime($today['clockin_time']))) . '</span>';
            } else {
                echo '<span style="color: red; font-weight: bold;">✔ Clocked Out at ' 
                    . htmlspecialchars(date('H:i', strtotime($today['clockout_time']))) . '</span>';
            }
            ?>
            </span>
        </p>
    </div>

    

    <div class="actions">
        <!-- Clock button (AJAX, stays on dashboard) -->
        <?php
            $initialClocked = $isClockedIn ? '1' : '0';
            $initialLabel = $isClockedIn ? 'Clock Out' : 'Clock In';
        ?>
        <button id="clockBtn" class="btn btn-anim-clock" data-clocked="<?= $initialClocked ?>">
            <span class="icon-clock">🕒</span>
            <span id="clockBtnLabel"><?= htmlspecialchars($initialLabel) ?></span>
        </button>

        <!-- Other actions -->
        <a href="../pdf/generate.php?id=<?= $emp['id'] ?>" class="btn btn-chat">👁️ View Timesheet</a>
        <!--a href="../pdf/generate.php?id=<?= $emp['id'] ?>" class="btn btn-pdf">📄 Export as PDF</a-->
        <a href="../chat/conversation.php" class="btn btn-chat">💬 Chat with Admin</a>
        <a href="upload_profile.php" class="btn btn-upload">📷 Upload Picture</a>
        <a href="request_leave.php" class="btn btn-anim-plane"><span class="icon-plane">✈</span> Request Leave</a>
        <a href="../logout.php" class="btn btn-chat"> Logout</a>
    </div>

    <h2>Recent Attendance</h2>
    <div class="table-wrapper">
        <table>
            <tr>
                <th>Date</th>
                <th>Clock-in</th>
                <th>Clock-out</th>
                <th>Worked Hours</th>
                <th>Comment</th>
            </tr>
            <?php foreach ($attendance as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['work_date']) ?></td>
                    <td><?= $row['clockin_time'] ? date('H:i', strtotime($row['clockin_time'])) : '-' ?></td>
                    <td><?= $row['clockout_time'] ? date('H:i', strtotime($row['clockout_time'])) : '-' ?></td>
                    <td><?= round($row['minutes_worked']/60, 2) ?> h</td>
                    <td><?= htmlspecialchars($row['comment'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="comment-box">
        <form method="post" action="save_comment.php">
            <label for="comment">Leave a Comment (e.g., lateness reason):</label>
            <textarea name="comment" id="comment" rows="3" required></textarea>
            <br>
            <button type="submit">Submit Comment</button>
        </form>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'comment_saved'): ?>
            <p style="color:green;">✅ Comment saved successfully.</p>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'empty_comment'): ?>
            <p style="color:red;">⚠️ Comment cannot be empty.</p>
        <?php endif; ?>

    </div>
</div>


<script>
    document.getElementById('profile_image')?.addEventListener('change', function () {
        document.getElementById('profileFileName').textContent =
            this.files[0] ? this.files[0].name : 'No file chosen';
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('clockBtn');
    const label = document.getElementById('clockBtnLabel');
    const status = document.getElementById('clockStatus');

    if (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        btn.disabled = true;
        const currentlyClocked = btn.dataset.clocked === '1';
        const action = currentlyClocked ? 'clockout' : 'clockin';
        const prevLabel = label.textContent;
        label.textContent = '⏳ Processing...';

        fetch('clock.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin',
          body: 'action=' + encodeURIComponent(action)
        })
        .then(res => res.json())
        .then(data => {
          if (!data || data.success === false) {
            const message = (data && data.error) ? data.error : 'Unknown error';
            Swal.fire({ icon: 'error', title: 'Clock action failed', text: message });
            label.textContent = prevLabel;
            btn.disabled = false;
            return;
          }

          if (data.action === 'clockin') {
            btn.dataset.clocked = '1';
            label.textContent = 'Clock Out';
            if (data.clockin_time) {
              const d = new Date(data.clockin_time.replace(' ', 'T'));
              const t = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
              status.innerHTML = '<span style="color: limegreen; font-weight: bold;">✅ Clocked In at ' + t + '</span>';
              Swal.fire({ icon: 'success', title: 'Successfully clocked in', text: 'Successfully clocked in at ' + t, timer: 2200, showConfirmButton: false });
            }
          } else if (data.action === 'clockout') {
            btn.dataset.clocked = '0';
            label.textContent = 'Clock In';
            let html = '';
            let t = '';
            if (data.clockout_time) {
              const dOut = new Date(data.clockout_time.replace(' ', 'T'));
              t = dOut.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
              html += '<span style="color: red; font-weight: bold;">✔ Clocked Out at ' + t + '</span>';
            }
            if (typeof data.worked_hours !== 'undefined' && data.worked_hours !== null) {
              html += ' &nbsp; Worked: <strong>' + data.worked_hours + ' h</strong>';
            }
            status.innerHTML = html;
            Swal.fire({ icon: 'success', title: 'Successfully clocked out', text: 'Successfully clocked out at ' + t + (data.worked_hours != null ? '. Worked: ' + data.worked_hours + ' h' : ''), timer: 2300, showConfirmButton: false });
          }

          setTimeout(() => btn.disabled = false, 400);
        })
        .catch(err => {
          console.error(err);
          Swal.fire({ icon: 'error', title: 'Request failed', text: String(err) });
          label.textContent = prevLabel;
          btn.disabled = false;
        });
      });
    }

    // Fallback: show SweetAlert if redirected with query params
    try {
      const params = new URLSearchParams(window.location.search);
      if (params.has('success')) {
        const success = params.get('success') === '1';
        const action = params.get('action');
        const timeStr = params.get('time');
        const errorMsg = params.get('error');
        let msg = '';
        if (success && timeStr && action) {
          const d = new Date(timeStr.replace(' ', 'T'));
          const t = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
          msg = (action === 'clockin')
            ? 'Successfully clocked in at ' + t
            : 'Successfully clocked out at ' + t;
          Swal.fire({ icon: 'success', title: (action === 'clockin') ? 'Successfully clocked in' : 'Successfully clocked out', text: msg, timer: 2400, showConfirmButton: false });
        } else if (!success) {
          Swal.fire({ icon: 'error', title: 'Clock action failed', text: errorMsg || 'Unknown error' });
        }
        // Clean URL
        const url = new URL(window.location.href);
        url.search = '';
        window.history.replaceState({}, '', url);
      }
    } catch (e) { /* ignore */ }
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('workPercentageChart').getContext('2d');
    const percentage = <?= $attendancePercent ?>;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Worked Days', 'Missed Days'],
            datasets: [{
                data: [percentage, 100 - percentage],
                backgroundColor: ['#32CD32', '#444'], // Green for worked, grey for missed
                borderWidth: 0
            }]
        },
        options: {
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true }
            }
        }
    });

    // Add percentage text in middle of donut
    Chart.register({
        id: 'centerText',
        afterDraw(chart) {
            const {width} = chart;
            const {height} = chart;
            const ctx = chart.ctx;
            ctx.restore();
            const fontSize = (height / 8).toFixed(2);
            ctx.font = fontSize + "px Arial";
            ctx.fillStyle = "#fff";
            ctx.textBaseline = "middle";
            const text = percentage + "%";
            const textX = Math.round((width - ctx.measureText(text).width) / 2);
            const textY = height / 2;
            ctx.fillText(text, textX, textY);
            ctx.save();
        }
    });
});
</script>

</body>
</html>
