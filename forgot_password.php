<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);

    if (!empty($email)) {
        // Example: logic to handle password reset (adjust for your app)
        echo "<script>alert('A reset link has been sent to $email');</script>";
    } else {
        echo "<script>alert('Please enter your email address.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body {
            background: radial-gradient(circle at center, #001a00, #000);
            color: #e0ffe0;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .forgot-container {
            background: rgba(0, 0, 0, 0.85);
            border: 2px solid #00ff7f;
            box-shadow: 0 0 20px #00ff7f;
            border-radius: 15px;
            width: 90%;
            max-width: 380px;
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
            margin-bottom: 20px;
            text-shadow: 0 0 10px #00ff7f;
        }

        p {
            font-size: 14px;
            color: #b5f7b5;
            margin-bottom: 25px;
        }

        input[type="email"] {
            width: 90%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #00ff7f;
            background: transparent;
            color: #e0ffe0;
            font-size: 15px;
            outline: none;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        input[type="email"]:focus {
            box-shadow: 0 0 10px #00ff7f;
            background: rgba(0, 255, 127, 0.05);
        }

        button {
            width: 100%;
            padding: 12px;
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

        button:hover {
            background: #00cc66;
            box-shadow: 0 0 20px #00ff7f;
        }

        a.back-link {
            display: inline-block;
            color: #00ff7f;
            margin-top: 15px;
            font-size: 14px;
            text-decoration: none;
            transition: 0.3s;
        }

        a.back-link:hover {
            text-shadow: 0 0 10px #00ff7f;
        }

        @media (max-width: 480px) {
            .forgot-container {
                padding: 20px;
                width: 95%;
            }
            input[type="email"] {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <h2>Forgot Password</h2>
        <p>Enter your email address below, and we’ll send you a password reset link.</p>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Enter your email address" required>
            <button type="submit">Send Reset Link</button>
        </form>
        <a href="login.php" class="back-link">← Back to Login</a>
    </div>
</body>
</html>
