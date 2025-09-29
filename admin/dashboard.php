<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('admin');
require_once __DIR__ . '/../includes/config.php';

// Get filters
$shift = $_GET['shift'] ?? 'all';
$category = $_GET['category'] ?? 'all';
$place = $_GET['place'] ?? 'all';

// Base query
$sql = "SELECT * FROM employees WHERE 1=1";
$params = [];

// Apply filters
if ($shift !== 'all') {
    $sql .= " AND shift = ?";
    $params[] = $shift;
}
if ($category !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $category;
}
if ($place !== 'all') {
    $sql .= " AND place_of_work = ?";
    $params[] = $place;
}


$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

// Function to calculate hours worked this month
function getHoursThisMonth($pdo, $employeeId) {
    $monthStart = date("Y-m-01");
    $monthEnd = date("Y-m-t");
    $stmt = $pdo->prepare("SELECT clockin_time, clockout_time 
                           FROM attendance 
                           WHERE employee_id=? AND clockin_time BETWEEN ? AND ?");
    $stmt->execute([$employeeId, $monthStart, $monthEnd]);
    $records = $stmt->fetchAll();
    $total = 0;
    foreach ($records as $r) {
        if ($r['clockin_time'] && $r['clockout_time']) {
            $total += (strtotime($r['clockout_time']) - strtotime($r['clockin_time'])) / 3600;
        }
    }
    return round($total, 2);
}

function getAttendancePercent($pdo, $employeeId) {
    $monthStart = date("Y-m-01");
    $monthEnd = date("Y-m-t");

    // Count worked days
    $stmt = $pdo->prepare("SELECT DISTINCT DATE(clockin_time) as day 
                           FROM attendance 
                           WHERE employee_id=? AND clockin_time BETWEEN ? AND ?");
    $stmt->execute([$employeeId, $monthStart, $monthEnd]);
    $workedDays = count($stmt->fetchAll());

    // Total working days (Mon–Sat)
    $totalDays = 0;
    $period = new DatePeriod(new DateTime($monthStart), new DateInterval('P1D'), new DateTime($monthEnd . ' +1 day'));
    foreach ($period as $date) {
        if ($date->format("N") < 7) { // Mon–Sat
            $totalDays++;
        }
    }

    return $totalDays ? round(($workedDays / $totalDays) * 100, 1) : 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - iRoks</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- jQuery (required for Lightbox) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Lightbox2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #111; color: #fff; font-family: Arial, sans-serif; }
        .container { max-width: 1200px; margin: 30px auto; padding: 20px; background: #1a1a1a; border-radius: 12px; }
        h1 { color: #32CD32; }
        .filters { margin-bottom: 20px; }
        .filters select { padding: 6px; margin-right: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 5px; border-bottom: 1px solid #333; text-align: center; }
        th { background: #222; }
        tr:hover { background: #2a2a2a; }
        .actions a { margin: 0 5px; padding: 1px 1px; background: #32CD32; color: #111; text-decoration: none; border-radius: 6px; font-size: 14px; }
        .actions a:hover { background: #28a428; }
        .status-badge {font-size: 0.9em; font-weight: bold; padding: 1px 1px; border-radius: 20px; color: #fff;}
        .status-badge.in { background: #28a745; }   /* green */
        .status-badge.out { background: #dc3545; }  /* red */
        /* Dropdown container */
.dropdown {
    position: relative;
    display: inline-block;
}

/* Button */
.dropbtn {
    background-color: #32CD32;
    color: #111;
    padding: 6px 12px;
    font-size: 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.dropbtn:hover {
    background-color: #28a428;
}

/* Dropdown menu */
.dropdown-content {
    display: none;
    position: absolute;
    background-color: #1a1a1a;
    min-width: 160px;
    box-shadow: 0px 8px 16px rgba(0,0,0,0.3);
    border-radius: 6px;
    z-index: 10;
}

/* Links inside dropdown */
.dropdown-content a {
    color: #fff;
    padding: 10px 14px;
    text-decoration: none;
    display: block;
    font-size: 14px;
}

.dropdown-content a:hover {
    background-color: #333;
}

/* Show dropdown on hover */
.dropdown:hover .dropdown-content {
    display: block;
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
    <h1>Admin Dashboard</h1>

    <!-- Filters -->
    <form method="get" class="filters">
        <label>Shift:</label>
        <select name="shift">
            <option value="all" <?= $shift=='all'?'selected':'' ?>>All</option>
            <option value="shift_1" <?= $shift=='shift_1'?'selected':'' ?>>Shift 1</option>
            <option value="shift_2" <?= $shift=='shift_2'?'selected':'' ?>>Shift 2</option>
        </select>

        <label>Category:</label>
        <select name="category">
            <option value="all" <?= $category=='all'?'selected':'' ?>>All</option>
            <option value="manager" <?= $category=='manager'?'selected':'' ?>>Manager</option>
            <option value="assistant" <?= $category=='assistant'?'selected':'' ?>>Assistant</option>
            <option value="intern" <?= $category=='intern'?'selected':'' ?>>Intern</option>
        </select>

        <label>Place of Work:</label>
        <select name="place">
            <option value="all" <?= $place=='all'?'selected':'' ?>>All</option>
            <option value="office" <?= $place=='office'?'selected':'' ?>>Office</option>
            <option value="remote" <?= $place=='remote'?'selected':'' ?>>Remote</option>
        </select>

        <button type="submit">Apply</button>
    </form>

    <!-- Employee Table -->
    <div class="table-wrapper">
        <table>
            <tr>
                <th>Profile</th>
                <th>Name</th>
                <th>Role</th>
                <th>Category</th>
                <th>Shift</th>
                <th>Place</th>
                <th>Hours</th>
                <th>Attendance</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($employees as $emp): ?>
                <tr>
                    <td>
                        <?php if (!empty($emp['profile_pic'])): ?>
                            <a href="../uploads/profiles/<?= htmlspecialchars($emp['profile_pic']) ?>" 
                            data-lightbox="profile-admin-<?= $emp['id'] ?>" 
                            data-title="Profile Photo of <?= htmlspecialchars($emp['fullname']) ?>">
                                <img src="../uploads/profiles/<?= htmlspecialchars($emp['profile_pic']) ?>" 
                                    alt="Profile Photo" 
                                    width="40" height="40" 
                                    style="border-radius:50%; object-fit:cover;">
                            </a>
                        <?php else: ?>
                            <img src="../assets/images/default-avatar.png" 
                                alt="No Profile" 
                                width="40" height="40" 
                                style="border-radius:50%; object-fit:cover;">
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($emp['fullname']) ?></td>
                    <td><?= htmlspecialchars($emp['role']) ?></td>
                    <td><?= htmlspecialchars($emp['category']) ?></td>
                    <td><?= $emp['shift']=='shift_1'?'Shift 1':'Shift 2' ?></td>
                    <td><?= ucfirst($emp['place_of_work']) ?></td>
                    <td><?= getHoursThisMonth($pdo, $emp['id']) ?> h</td>
                    <td>
                        <canvas id="attendanceChart-<?= $emp['id'] ?>"style="width: 100px; margin: -15px auto; text-align: center;"></canvas>
                        <span style="display:none;" 
                            data-emp-id="<?= $emp['id'] ?>" 
                            data-percent="<?= getAttendancePercent($pdo, $emp['id']) ?>">
                        </span>
                    </td>
                    <td>
                        <?php
                        $stmt = $pdo->prepare("SELECT clockout_time FROM attendance WHERE employee_id=? ORDER BY id DESC LIMIT 1");
                        $stmt->execute([$emp['id']]);
                        $last = $stmt->fetch();

                        $isClockedIn = $last && $last['clockout_time'] === null;
                        ?>
                        <span class="status-badge <?= $isClockedIn ? 'in' : 'out' ?>">
                            <?= $isClockedIn ? '🟢 In' : '🔴 Out' ?>
                        </span>
                    </td>
                    <td class="actions">
                        <div class="dropdown">
                            <button class="dropbtn">⚙️ Actions ▾</button>
                            <div class="dropdown-content">
                                <a href="edit_employee.php?id=<?= $emp['id'] ?>">✏️ Edit</a>
                                <a href="view_attendance.php?id=<?= $emp['id'] ?>">📊 Attendance</a>
                                <a href="../chat/chat.php?id=<?= $emp['id'] ?>">💬 Chat</a>
                                <a href="approve_leave.php?id=<?= $emp['id'] ?>">✅ Approve Leave</a>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <h2 style="margin-top:40px;">🏆 Top Attendance This Month</h2>
    <ul style="list-style:none; padding:0;">
    <?php
    $rankStmt = $pdo->query("SELECT id, fullname FROM employees");
    $ranks = [];
    foreach ($rankStmt as $r) {
        $ranks[] = [
            'name' => $r['fullname'],
            'percent' => getAttendancePercent($pdo, $r['id'])
        ];
    }
    usort($ranks, fn($a,$b) => $b['percent'] <=> $a['percent']);
    foreach (array_slice($ranks, 0, 5) as $r) {
        echo "<li style='margin:5px 0;'>".$r['name']." - <strong>".$r['percent']."%</strong></li>";
    }
    ?>
    </ul>
</div>
<script>
    document.getElementById('profile_image')?.addEventListener('change', function () {
        document.getElementById('profileFileName').textContent =
            this.files[0] ? this.files[0].name : 'No file chosen';
    });
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const charts = document.querySelectorAll("span[data-percent]");

    charts.forEach(span => {
        const empId = span.getAttribute("data-emp-id");
        const percent = parseFloat(span.getAttribute("data-percent"));
        const missed = 100 - percent;

        const ctx = document.getElementById("attendanceChart-" + empId).getContext("2d");

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [percent, missed],
                    backgroundColor: ['#32CD32', '#444'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '70%',
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                }
            },
            plugins: [{
                id: 'centerText-' + empId,
                afterDraw(chart) {
                    const {width, height} = chart;
                    const ctx = chart.ctx;
                    ctx.save();
                    ctx.font = (height / 3).toFixed(2) + "px Arial";
                    ctx.fillStyle = "#fff";
                    ctx.textBaseline = "middle";
                    const text = percent + "%";
                    const textX = Math.round((width - ctx.measureText(text).width) / 2);
                    const textY = height / 2;
                    ctx.fillText(text, textX, textY);
                    ctx.restore();
                }
            }]
        });
    });
});
</script>

</body>
</html>
