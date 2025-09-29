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
            overtime_applicable = ?
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
        $id
    ]);

    // Redirect back with a success flag to avoid resubmission
    header("Location: edit_employee.php?id={$id}&updated=1");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Employee - iRoks</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #111; color: #fff; font-family: Arial, sans-serif; }
        .container { max-width: 700px; margin: 30px auto; padding: 20px; background: #1a1a1a; border-radius: 12px; }
        h1 { color: #32CD32; margin-bottom: 20px; }
        form { display: grid; gap: 12px; }
        label { font-weight: bold; color: #cfd8dc; }
        input, select { width: 100%; padding: 8px; border-radius: 6px; border: none; background: #222; color: #fff; }
        .btn { padding: 10px; border: none; background: #32CD32; color: #111; font-weight: bold; border-radius: 6px; cursor: pointer; }
        .success { background: #163a16; color: #bff6bf; padding: 10px; border-radius: 6px; margin-bottom: 12px; }
    </style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" style="color:#32CD32; text-decoration:none;">← Back to Dashboard</a>
    <h1>Edit Employee</h1>

    <?php if (isset($_GET['updated'])): ?>
        <div class="success">Employee updated successfully.</div>
    <?php endif; ?>

    <form method="post">
        <label>Full Name</label>
        <input type="text" name="fullname" value="<?= htmlspecialchars($emp['fullname']) ?>" required>

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
            <option value="shift_1" <?= ($emp['shift']=='shift_1') ? 'selected' : '' ?>>Shift 1 (07:45 - 15:45)</option>
            <option value="shift_2" <?= ($emp['shift']=='shift_2') ? 'selected' : '' ?>>Shift 2 (15:45 - 23:45)</option>
        </select>

        <label>Place of Work</label>
        <select name="place" required>
            <option value="office" <?= ($emp['place_of_work']=='office') ? 'selected' : '' ?>>Office</option>
            <option value="remote" <?= ($emp['place_of_work']=='remote') ? 'selected' : '' ?>>Remote</option>
        </select>

        <label>Expected Clock-in</label>
        <input type="time" name="expected_clockin" value="<?= htmlspecialchars($emp['expected_clockin'] ?? '') ?>">

        <label>Expected Clock-out</label>
        <input type="time" name="expected_clockout" value="<?= htmlspecialchars($emp['expected_clockout'] ?? '') ?>">

        <label>Hourly Pay (SRD)</label>
        <input type="number" step="0.01" name="hourly_pay" value="<?= htmlspecialchars($emp['hourly_pay'] ?? '0.00') ?>">

        <label>Monthly Pay (SRD)</label>
        <input type="number" step="0.01" name="monthly_pay" value="<?= htmlspecialchars($emp['monthly_pay'] ?? '0.00') ?>">

        <label style="display:flex;align-items:center;gap:8px;">
            <input type="checkbox" name="overtime_applicable" value="1" <?= (!empty($emp['overtime_applicable']) ? 'checked' : '') ?>>
            Overtime applies for this role
        </label>

        <button type="submit" class="btn">Save Changes</button>
    </form>
</div>
</body>
</html>
