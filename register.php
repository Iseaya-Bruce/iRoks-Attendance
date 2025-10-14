<?php
require_once __DIR__ . '/includes/config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $category = $_POST['category'] ?? '';
    $shift = $_POST['shift'] ?? '';
    $place_of_work = $_POST['place_of_work'] ?? '';

    // Validate required fields
    if (empty($fullname) || empty($password) || empty($confirm_password)) {
        $errors[] = "Please fill in all required fields.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO employees 
            (fullname, phone, password, role, category, shift, place_of_work, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$fullname, $phone, $passwordHash, $role, $category, $shift, $place_of_work]);

        $newUserId = $pdo->lastInsertId();
        $_SESSION['pending_user_id'] = $newUserId;

        $success = true;
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

        @media (max-width: 480px) {
            .card {
                padding: 20px;
            }
            input, select {
                font-size: 14px;
            }
        }
    </style>
</head>
<body class="page-register">
<div class="card">
    <h2>Register</h2>

    <?php if ($errors): ?>
        <div class="alert error">
            <?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="register.php">
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if ($success): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Successfully Registered',
    text: 'Please wait for admin\'s approval before logging in.',
    confirmButtonColor: '#1e88e5'
});
</script>
<?php endif; ?>
</body>
</html>
