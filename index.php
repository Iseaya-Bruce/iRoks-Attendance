<?php
session_start();

// If already logged in, redirect to dashboards
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['type'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } elseif ($_SESSION['user']['type'] === 'employee') {
        header("Location: employee/dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden; /* ✅ Prevents scroll */
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #000000ff, #000000ff);
            color: white;
        }

        /* Fixed animated background waves */
        .wave {
            position: fixed; /* ✅ stays inside viewport */
            top: 0;
            left: 0;
            width: 100vw;   /* ✅ only viewport width */
            height: 100vh;  /* ✅ only viewport height */
            background: radial-gradient(circle, rgba(0, 255, 0, 0.2) 20%, transparent 20%);
            background-size: 50px 50px;
            animation: drift 20s linear infinite;
            opacity: 1;
            z-index: 0;
        }
        .wave:nth-child(2) { animation-duration: 30s; opacity: 1; }
        .wave:nth-child(3) { animation-duration: 40s; opacity: 1; }
        @keyframes drift {
            from { transform: translate(0,0) rotate(0deg); }
            to { transform: translate(-50px,-50px) rotate(-360deg); }
        }

        /* Main container */
        .container {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 30px;
            width: 90%;
            max-width: 450px;
            background: rgba(0,0,0,0.35);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            animation: fadeIn 1.5s ease;
        }

        .container h1 {
            font-size: 2.3rem;
            margin-bottom: 10px;
            animation: slideDown 1s ease;
            word-wrap: break-word;
        }

        .container p {
            font-size: 1.1rem;
            margin-bottom: 25px;
            opacity: 0.9;
            animation: slideUp 1.2s ease;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin: 10px;
            font-size: 1rem;
            color: #fff;
            text-decoration: none;
            border-radius: 30px;
            background: linear-gradient(135deg, #00c6ff, #0072ff);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
            width: 100%;
            max-width: 280px;
        }

        .btn i { margin-right: 8px; }

        .btn:hover { transform: scale(1.05); }

        .btn::after {
            content: "";
            position: absolute;
            width: 200%;
            height: 200%;
            top: -100%;
            left: -50%;
            background: rgba(255,255,255,0.3);
            transform: rotate(25deg);
            transition: 0.5s;
            animation: scan 2s linear infinite;
        }

        .btn:hover::after { top: 0; }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes scan {
            0% { transform: translateX(-100%) rotate(25deg); }
            100% { transform: translateX(100%) rotate(25deg); }
        }

        /* ✅ Mobile responsiveness */
        @media (max-width: 600px) {
            .container { padding: 20px; border-radius: 15px; }
            .container h1 { font-size: 1.8rem; }
            .container p { font-size: 1rem; margin-bottom: 20px; }
            .btn { font-size: 0.95rem; padding: 10px 20px; margin: 8px 0; }
        }

        @media (max-width: 400px) {
            .container h1 { font-size: 1.5rem; }
            .btn { font-size: 0.9rem; padding: 9px 18px; }
        }
    </style>
</head>
<body>
    <!-- Background waves -->
    <div class="wave"></div>
    <div class="wave"></div>
    <div class="wave"></div>

    <div class="container">
        <h1><i class="fas fa-clock"></i>iRoks Attendance System</h1>
        <p>Track your work time and attendance effortlessly.</p>
        
        <a href="login.php" class="btn"><i class="fas fa-sign-in-alt"></i> Login</a>
        <a href="register.php" class="btn"><i class="fas fa-user-plus"></i> Register</a>
    </div>
</body>
</html>

