<?php
//echo password_hash('SuperSecret123', PASSWORD_DEFAULT);

// register.php
require_once __DIR__ . '/includes/config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Employee extra fields:
    $role = trim($_POST['role'] ?? 'Employee');
    $category = trim($_POST['category'] ?? '');
    $shift = trim($_POST['shift'] ?? '');
    $place_of_work = trim($_POST['place_of_work'] ?? '');
    $hourly_pay = $_POST['hourly_pay'] ?? 0.00;
    $monthly_pay = $_POST['monthly_pay'] ?? 0.00;
    $expected_clockin = $_POST['expected_clockin'] ?? null;
    $expected_clockout = $_POST['expected_clockout'] ?? null;

    if ($fullname === '' || $password === '' || $confirm === '') {
        $errors[] = "Please fill all required fields.";
    } elseif ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    } else {
        // Hash password
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Check for duplicate employee
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE fullname = ?");
        $stmt->execute([$fullname]);
        if ($stmt->fetch()) {
            $errors[] = "Employee with that name already exists.";
        } else {
            $ins = $pdo->prepare("INSERT INTO employees 
                (fullname, phone, password, role, category, shift, place_of_work, hourly_pay, monthly_pay, expected_clockin, expected_clockout)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([
                $fullname,
                $phone,
                $hash,
                $role,
                $category,
                $shift,
                $place_of_work,
                $hourly_pay,
                $monthly_pay,
                $expected_clockin ?: null,
                $expected_clockout ?: null
            ]);
            $success = "Employee account created. You can now login.";
        }
    }
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - iRoks</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-register">
<div class="card">
    <h2>Register</h2>

    <?php if ($errors): ?>
        <div class="alert error">
            <?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert success"><?=htmlspecialchars($success)?></div>
    <?php endif; ?>

    <form method="post" action="register.php">
        <!--label>Account type
            <select name="type" id="type" onchange="toggleEmployeeFields()">
                <option value="employee" selected>Employee</option>
                <option value="admin">Admin (CEO)</option>
            </select>
        </label-->

        <label>Full name
            <input type="text" name="fullname" required>
        </label>

        <label>Phone
            <input type="text" name="phone">
        </label>

        <div id="employeeFields">
            <label>Role</label>
            <select name="role" required>
                <option value="verkoop medewerker">Verkoop Medewerker</option>
                <option value="driver">Driver</option>
                <option value="content creator">Content Creator</option>
                <option value="software engineer">Software Engineer</option>
                <option value="hrm">HRM</option>
                <option value="financieel administrator">Financieel Administrator</option>
            </select>

            <label>Category</label>
            <select name="category" required>
                <option value="nuts">Nuts</option>
                <option value="suribet">Suribet</option>
                <option value="copie">Copie</option>
                <option value="e-services">E-services</option>
                <option value="delivery">Delivery</option>
                <option value="marketing">Marketing</option>
                <option value="software engineer">Software Engineer</option>
            </select>

            <label>Shift</label>
            <select name="shift" required>
                <option value="shift_1">Shift 1 (07:45–15:45)</option>
                <option value="shift_2">Shift 2 (15:30–23:30)</option>
            </select>

            <label>Place of Work</label>
            <select name="place_of_work" required>
                <option value="office">Office</option>
                <option value="remote">Remote</option>
            </select>

            <label>Hourly pay
                <input type="number" step="0.01" name="hourly_pay" value="0.00">
            </label>

            <label>Monthly pay
                <input type="number" step="0.01" name="monthly_pay" value="0.00">
            </label>

            <label>Expected clock-in
                <input type="time" name="expected_clockin">
            </label>

            <label>Expected clock-out
                <input type="time" name="expected_clockout">
            </label>
        </div>

        <label>Password
            <input type="password" name="password" required>
        </label>

        <label>Confirm password
            <input type="password" name="confirm_password" required>
        </label>

        <button type="submit" class="btn">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login</a></p>
</div>

<script>
function toggleEmployeeFields(){
    const type = document.getElementById('type').value;
    document.getElementById('employeeFields').style.display = (type === 'employee') ? 'block' : 'none';
}
toggleEmployeeFields();
</script>
</body>
</html>
