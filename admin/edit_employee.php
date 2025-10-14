<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('admin');
require_once __DIR__ . '/../includes/config.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: dashboard.php");
    exit;
}

// Fetch employee
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$emp) {
    die("Employee not found");
}

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect inputs (use names that match your form)
    $fullname = trim($_POST['fullname'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $shift = trim($_POST['shift'] ?? '');
    $place = trim($_POST['place'] ?? '');
    $expected_clockin = $_POST['expected_clockin'] !== '' ? $_POST['expected_clockin'] : null;
    $expected_clockout = $_POST['expected_clockout'] !== '' ? $_POST['expected_clockout'] : null;
    $hourly_pay = $_POST['hourly_pay'] !== '' ? (float)$_POST['hourly_pay'] : 0.00;
    $monthly_pay = $_POST['monthly_pay'] !== '' ? (float)$_POST['monthly_pay'] : 0.00;

    // Checkbox: if present -> 1, otherwise -> 0
    $overtime_applicable = isset($_POST['overtime_applicable']) ? 1 : 0;
    // Update query (add/remove columns as needed to match your schema)
    $late_fee_applicable = isset($_POST['late_fee_applicable']) ? 1 : 0;

    $upd = $pdo->prepare("UPDATE employees SET
            fullname = ?,
            role = ?,
            category = ?,
            shift = ?,
            place_of_work = ?,
            expected_clockin = ?,
            expected_clockout = ?,
            hourly_pay = ?,
            monthly_pay = ?,
            overtime_applicable = ?,
            late_fee_applicable = ?
        WHERE id = ?");

    $upd->execute([
        $fullname,
        $role,
        $category,
        $shift,
        $place,
        $expected_clockin,
        $expected_clockout,
        $hourly_pay,
        $monthly_pay,
        $overtime_applicable,
        $late_fee_applicable,
        $id
    ]);

    // Redirect back with a success flag to avoid resubmission
    header("Location: edit_employee.php?id={$id}&updated=1");
    exit;
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - iRoks</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: radial-gradient(circle at center, #001a00, #000);
            color: #e0ffe0;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .card {
            background: rgba(0, 0, 0, 0.85);
            border: 2px solid #00ff7f;
            box-shadow: 0 0 20px #00ff7f;
            border-radius: 15px;
            width: 100%;
            max-width: 500px;
            padding: 30px;
            text-align: center;
            animation: fadeIn 0.8s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        h2 {
            color: #00ff7f;
            text-shadow: 0 0 10px #00ff7f;
            margin-bottom: 25px;
        }

        label {
            display: block;
            text-align: left;
            margin-top: 10px;
            font-weight: bold;
            color: #b5f7b5;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #00ff7f;
            background: transparent;
            color: #06f306ff;
            font-size: 15px;
            outline: none;
            margin-top: 5px;
            transition: all 0.3s ease;
        }

        input:focus, select:focus {
            box-shadow: 0 0 10px #00ff7f;
            background: rgba(7, 7, 7, 0.97);
        }

        .btn {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            background: #00ff7f;
            color: #000;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 0 10px #00ff7f;
        }

        .btn:hover {
            background: #00cc66;
            box-shadow: 0 0 20px #00ff7f;
        }

        p {
            color: #b5f7b5;
            margin-top: 15px;
        }

        a {
            color: #00ff7f;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-shadow: 0 0 10px #00ff7f;
        }

        .alert.error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff4d4d;
            color: #ffb3b3;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .alert.success {
            background: rgba(0, 255, 127, 0.1);
            border: 1px solid #00ff7f;
            color: #b5f7b5;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        @media (max-width: 480px) {
            .card { padding: 20px; }
            input, select { font-size: 14px; }
        }
    </style>
</head>
<body class="page-edit-employee">
<div class="card">
    <div style="text-align:left; margin-bottom:10px;"><a href="dashboard.php">&larr; Back to Dashboard</a></div>
    <h2>Edit Employee</h2>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert success">Employee updated successfully.</div>
    <?php endif; ?>

    <form method="post">
        <label>Full Name
            <input type="text" name="fullname" value="<?= htmlspecialchars($emp['fullname']) ?>" required>
        </label>

        <label>Role</label>
        <select name="role" required>
            <option value="verkoop medewerker" <?= ($emp['role']=='verkoop medewerker') ? 'selected' : '' ?>>Verkoop Medewerker</option>
            <option value="driver" <?= ($emp['role']=='driver') ? 'selected' : '' ?>>Driver</option>
            <option value="content creator" <?= ($emp['role']=='content creator') ? 'selected' : '' ?>>Content Creator</option>
            <option value="software engineer" <?= ($emp['role']=='software engineer') ? 'selected' : '' ?>>Software Engineer</option>
            <option value="hrm" <?= ($emp['role']=='hrm') ? 'selected' : '' ?>>HRM</option>
            <option value="financieel administrator" <?= ($emp['role']=='financieel administrator') ? 'selected' : '' ?>>Financieel Administrator</option>
        </select>

        <label>Category</label>
        <select name="category" required>
            <option value="nuts" <?= ($emp['category']=='nuts') ? 'selected' : '' ?>>Nuts</option>
            <option value="suribet" <?= ($emp['category']=='suribet') ? 'selected' : '' ?>>Suribet</option>
            <option value="copie" <?= ($emp['category']=='copie') ? 'selected' : '' ?>>Copie</option>
            <option value="e-services" <?= ($emp['category']=='e-services') ? 'selected' : '' ?>>E-services</option>
            <option value="delivery" <?= ($emp['category']=='delivery') ? 'selected' : '' ?>>Delivery</option>
            <option value="marketing" <?= ($emp['category']=='marketing') ? 'selected' : '' ?>>Marketing</option>
            <option value="software engineer" <?= ($emp['category']=='software engineer') ? 'selected' : '' ?>>Software Engineer</option>
        </select>

        <label>Shift</label>
        <select name="shift" required>
            <option value="shift_1" <?= ($emp['shift']=='shift_1') ? 'selected' : '' ?>>Shift 1 (07:45–15:45)</option>
            <option value="shift_2" <?= ($emp['shift']=='shift_2') ? 'selected' : '' ?>>Shift 2 (15:30–23:30)</option>
        </select>

        <label>Place of Work</label>
        <select name="place" required>
            <option value="office" <?= ($emp['place_of_work']=='office') ? 'selected' : '' ?>>Office</option>
            <option value="remote" <?= ($emp['place_of_work']=='remote') ? 'selected' : '' ?>>Remote</option>
        </select>

        <label>Expected Clock-in
            <input type="time" name="expected_clockin" value="<?= htmlspecialchars($emp['expected_clockin'] ?? '') ?>">
        </label>

        <label>Expected Clock-out
            <input type="time" name="expected_clockout" value="<?= htmlspecialchars($emp['expected_clockout'] ?? '') ?>">
        </label>

        <label>Hourly Pay (SRD)
            <input type="number" step="0.01" name="hourly_pay" value="<?= htmlspecialchars($emp['hourly_pay'] ?? '0.00') ?>">
        </label>

        <label>Monthly Pay (SRD)
            <input type="number" step="0.01" name="monthly_pay" value="<?= htmlspecialchars($emp['monthly_pay'] ?? '0.00') ?>">
        </label>

        <label style="display:flex;align-items:center;gap:8px;">
            <input type="checkbox" name="overtime_applicable" value="1" <?= (!empty($emp['overtime_applicable']) ? 'checked' : '') ?>>
            Overtime applies for this role
        </label>

        <label style="display:flex;align-items:center;gap:8px;">
            <input type="checkbox" name="late_fee_applicable" value="1" <?= (!empty($emp['late_fee_applicable']) ? 'checked' : '') ?>>
            Late fee applies for this role
        </label>

        <button type="submit" class="btn">Save Changes</button>
    </form>
</div>
</body>
</html>
