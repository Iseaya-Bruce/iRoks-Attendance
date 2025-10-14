<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('employee');
require_once __DIR__ . '/../includes/config.php';

$user_id = $_SESSION['user']['id'];

// Fetch employee info
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id=?");
$stmt->execute([$user_id]);
$emp = $stmt->fetch();

$error = $success = '';

/* =====================
   PROFILE UPDATE LOGIC
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $place_of_work = trim($_POST['place_of_work'] ?? '');

    if ($fullname === '' || $place_of_work === '') {
        $error = "Please fill in all profile fields.";
    } else {
        // Update text fields first
        $stmt = $pdo->prepare("UPDATE employees SET fullname=?, place_of_work=? WHERE id=?");
        $stmt->execute([$fullname, $place_of_work, $user_id]);

        // Handle image upload if present
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_pic'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($ext, $allowed)) {
                $error = "❌ Only JPG, PNG, GIF, WEBP allowed.";
            } elseif ($file['size'] > 2*1024*1024) {
                $error = "❌ File too large (max 2MB).";
            } elseif (!getimagesize($file['tmp_name'])) {
                $error = "❌ Not a valid image.";
            } else {
                $filename = "profile_" . $user_id . "_" . time() . "." . $ext;
                $destination = __DIR__ . "/../uploads/profiles/" . $filename;
                if (!is_dir(__DIR__ . "/../uploads/profiles")) {
                    mkdir(__DIR__ . "/../uploads/profiles", 0777, true);
                }
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $stmt = $pdo->prepare("UPDATE employees SET profile_pic=? WHERE id=?");
                    $stmt->execute([$filename, $user_id]);
                    $success = "Profile updated successfully!";
                } else {
                    $error = "Error saving uploaded image.";
                }
            }
        } else {
            $success = "Profile updated successfully!";
        }

        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE id=?");
        $stmt->execute([$user_id]);
        $emp = $stmt->fetch();
    }
}

/* =====================
   PASSWORD CHANGE LOGIC
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$new || !$confirm) {
        $error = "Please fill in all password fields.";
    } elseif (!password_verify($current, $emp['password'])) {
        $error = "Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } elseif (strlen($new) < 4) {
        $error = "New password must be at least 4 characters long.";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE employees SET password=? WHERE id=?");
        $stmt->execute([$hashed, $user_id]);
        $success = "Password changed successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
     /* Glowing button animation */
        .btn {
            position: relative;
            display: inline-block;
            padding: 10px 18px;
            border-radius: 8px;
            background: #007bff;
            color: #fff;
            font-weight: bold;
            text-decoration: none;
            transition: transform 0.2s ease;
            overflow: hidden;
        }

        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(0,123,255,0.8);
        }

        /* Parsing/glow effect */
        .btn::after {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(120deg, transparent, rgba(255,255,255,0.3), transparent);
            transform: rotate(25deg);
            animation: scan 2s linear infinite;
        }

        @keyframes scan {
            0% { transform: translateX(-100%) rotate(25deg); }
            100% { transform: translateX(100%) rotate(25deg); }
        }

        .file-input-wrapper {
            margin-top: 15px; 
        }
</style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="container">
    <h2>My Profile</h2>

    <!-- Profile Update -->
    <div class="section">
        <h3>Profile Information</h3>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="update_profile" value="1">

            <label>Full Name</label>
            <input type="text" name="fullname" value="<?= htmlspecialchars($emp['fullname']) ?>" required>

            <label>Place of Work</label>
            <input type="text" name="place_of_work" value="<?= htmlspecialchars($emp['place_of_work']) ?>" required>

            <label>Profile Photo</label>
            <?php if (!empty($emp['profile_pic'])): ?>
                <img src="../uploads/profiles/<?= htmlspecialchars($emp['profile_pic']) ?>?t=<?= time() ?>" 
                     width="120" class="profile-preview" alt="Profile Photo">
            <?php endif; ?>

            <input type="file" name="profile_pic" accept="image/*">
            <button type="submit" class="btn">Update Profile</button>
        </form>
    </div>

    <!-- Password Change -->
    <div class="section">
        <h3>Change Password</h3>
        <form method="post">
            <input type="hidden" name="change_password" value="1">
            <label>Current Password</label>
            <input type="password" name="current_password" required>

            <label>New Password</label>
            <input type="password" name="new_password" required>

            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required>

            <button type="submit" class="btn">Change Password</button>
        </form>
    </div>

    <a href="dashboard.php" class="btn">← Back to Dashboard</a>
</div>

<script>
<?php if ($success): ?>
Swal.fire({ icon: 'success', title: 'Success', text: '<?= addslashes($success) ?>', confirmButtonColor: '#007bff' });
<?php elseif ($error): ?>
Swal.fire({ icon: 'error', title: 'Error', text: '<?= addslashes($error) ?>', confirmButtonColor: '#d33' });
<?php endif; ?>
</script>
</body>
</html>
