<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newPassword = $_POST["new_password"];
    $confirmPassword = $_POST["confirm_password"];

    if ($newPassword === $confirmPassword && !empty($newPassword)) {
        // Example: Save new password to DB here (hashed)
        echo "<script>alert('✅ Your password has been successfully reset!'); window.location='login.php';</script>";
    } else {
        echo "<script>alert('⚠️ Passwords do not match or are empty. Please try again.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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

        .reset-container {
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

        .input-group {
            position: relative;
            width: 90%;
            margin: 0 auto 20px;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #00ff7f;
            background: transparent;
            color: #e0ffe0;
            font-size: 15px;
            outline: none;
            transition: all 0.3s ease;
        }

        input[type="password"]:focus {
            box-shadow: 0 0 10px #00ff7f;
            background: rgba(0, 255, 127, 0.05);
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 12px;
            cursor: pointer;
            color: #00ff7f;
            font-size: 14px;
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
            .reset-container {
                padding: 20px;
                width: 95%;
            }
            input[type="password"] {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <h2>Reset Password</h2>
        <p>Enter and confirm your new password below.</p>
        <form method="POST" action="">
            <div class="input-group">
                <input type="password" name="new_password" id="new_password" placeholder="New Password" required>
                <span class="toggle-password" onclick="togglePassword('new_password', this)">👁</span>
            </div>

            <div class="input-group">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                <span class="toggle-password" onclick="togglePassword('confirm_password', this)">👁</span>
            </div>

            <button type="submit">🔒 Reset Password</button>
        </form>
        <a href="login.php" class="back-link">← Back to Login</a>
    </div>

    <script>
        function togglePassword(id, el) {
            const input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
                el.textContent = "🙈";
            } else {
                input.type = "password";
                el.textContent = "👁";
            }
        }
    </script>
</body>
</html>
