<?php
// login.php
require_once __DIR__ . '/includes/config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullname === '' || $password === '') {
        $errors[] = "Please fill in fullname and password.";
    } else {
        // 🔹 Try admin first
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE fullname = ?");
        $stmt->execute([$fullname]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user'] = [
                'id' => $admin['id'],
                'fullname' => $admin['fullname'],
                'type' => 'admin'
            ];
            header('Location: admin/dashboard.php');
            exit;
        }

        // 🔹 If not admin, try employee
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE fullname = ?");
        $stmt->execute([$fullname]);
        $emp = $stmt->fetch();

        if ($emp && password_verify($password, $emp['password'])) {
            $_SESSION['user'] = [
                'id' => $emp['id'],
                'fullname' => $emp['fullname'],
                'type' => 'employee'
            ];
            header('Location: employee/dashboard.php');
            exit;
        }

        // 🔹 If neither worked
        $errors[] = "Invalid credentials.";
    }
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - iRoks</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-login">
<div class="card">
    <img src="assets/img/IROKS.jpg" alt="iRoks" style="width:120px;margin:0 auto;display:block;">
    <h2>Login</h2>

    <?php if ($errors): ?>
        <div class="alert error">
            <?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <!--label>Login as
            <select name="type">
                <option value="employee">Employee</option>
                <option value="admin">Admin</option>
                <option value="any">Try both</option>
            </select>
        </label-->

        <label>Full name
            <input type="text" name="fullname" required>
        </label>

        <label>Password
            <input type="password" name="password" required>
        </label>

        <button type="submit" class="btn">Login</button>
    </form>

    <p>No account? <a href="register.php">Register here</a></p>
</div>
</body>
</html>
