<?php
require_once __DIR__ . '/includes/config.php';

$errors = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullname === '' || $password === '') {
        $errors[] = "Please fill in fullname and password.";
    } else {
        // 1. Check if admin
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE fullname = ?");
        $stmt->execute([$fullname]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user'] = [
                'id' => $admin['id'],
                'fullname' => $admin['fullname'],
                'type' => 'admin'
            ];
            header('Location: admin/dashboard.php');
            exit;
        }

        // 2. Check if employee
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE fullname = ?");
        $stmt->execute([$fullname]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($emp && password_verify($password, $emp['password'])) {
            if ($emp['status'] === 'pending') {
                $message = 'pending';
            } elseif ($emp['status'] === 'disabled') {
                $errors[] = "Your account has been disabled. Contact admin.";
            } elseif ($emp['status'] === 'active') {
                $_SESSION['user'] = [
                    'id' => $emp['id'],
                    'fullname' => $emp['fullname'],
                    'type' => 'employee',
                    'role' => $emp['role'],
                    'category' => $emp['category'],
                    'shift' => $emp['shift'],
                    'place_of_work' => $emp['place_of_work']
                ];
                header('Location: employee/dashboard.php');
                exit;
            } else {
                $message = 'unknown';
            }
        }

        // 3. If no match at all
        if (empty($errors) && !$message) {
            $message = 'invalid';
        }
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body.page-login {
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
        input[type="text"], input[type="password"] {
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
        input:focus {
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
        .alert.error {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: left;
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
<body class="page-login">
<div class="card">
    <img src="assets/img/IROKS.jpg" alt="iRoks" style="width:120px;margin:0 auto;display:block; border: 2px solid #00ff7f;box-shadow: 0 0 20px #00ff7f;">
    <h2>Login</h2>

    <?php if ($errors): ?>
        <div class="alert error">
            <?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <label>Full name
            <input type="text" name="fullname" required>
        </label>

        <label>Password
            <input type="password" name="password" required>
        </label>

        <button type="submit" class="btn">Login</button>
    </form>

    <p>No account? <a href="register.php">Register here</a></p>
    <p><a href="forgot_password.php">Forgot your password?</a></p>
</div>

<script>
<?php if ($message === 'pending'): ?>
Swal.fire({
    icon: 'info',
    title: 'Approval Pending',
    text: 'Your account is awaiting admin approval. Please wait.',
    confirmButtonColor: '#1e88e5'
});
<?php elseif ($message === 'invalid'): ?>
Swal.fire({
    icon: 'error',
    title: 'Login Failed',
    text: 'Invalid name or password.',
    confirmButtonColor: '#d33'
});
<?php elseif ($message === 'unknown'): ?>
Swal.fire({
    icon: 'warning',
    title: 'Unknown Account Status',
    text: 'Please contact the administrator for help.',
});
<?php endif; ?>
</script>
</body>
</html>
