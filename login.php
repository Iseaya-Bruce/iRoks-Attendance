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
            background: linear-gradient(135deg, #1e88e5, #42a5f5);
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .card {
            background: white;
            padding: 35px;
            border-radius: 15px;
            width: 350px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            text-align: center;
        }
        h2 {
            margin-top: 10px;
            color: #333;
        }
        label {
            display: block;
            text-align: left;
            margin: 15px 0 5px;
            font-weight: 500;
            color: #333;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.3s;
        }
        input:focus {
            border-color: #1e88e5;
        }
        .btn {
            width: 100%;
            background-color: #1e88e5;
            color: white;
            border: none;
            padding: 10px;
            margin-top: 15px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            background-color: #42a5f5;
            transform: scale(1.03);
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
            margin-top: 15px;
            color: #444;
        }
        p a {
            color: #1e88e5;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s, transform 0.3s;
        }
        p a:hover {
            color: #1565c0;
            transform: scale(1.05);
        }
        @media (max-width: 480px) {
            .card {
                width: 90%;
                padding: 25px;
            }
        }
    </style>
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
