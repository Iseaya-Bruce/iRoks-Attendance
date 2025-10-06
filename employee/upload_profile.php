<?php
// employee/upload-profile.php
require_once __DIR__ . '/../includes/auth.php';
require_login('employee');
require_once __DIR__ . '/../includes/config.php';

$user_id = $_SESSION['user']['id'];

// Fetch employee info
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id=?");
$stmt->execute([$user_id]);
$emp = $stmt->fetch();


// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "profile_" . $user_id . "_" . time() . "." . $ext;
        $destination = __DIR__ . "/../uploads/profiles/" . $filename;
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            die("❌ Only JPG, PNG, GIF, WEBP allowed.");
        }

        if ($file['size'] > 2*1024*1024) { // 2MB limit
            die("❌ File too large.");
        }

        if (!getimagesize($file['tmp_name'])) {
            die("❌ Not a valid image file.");
        }


        // create uploads/profiles dir if missing
        if (!is_dir(__DIR__ . "/../uploads/profiles")) {
            mkdir(__DIR__ . "/../uploads/profiles", 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // save filename in DB
            $stmt = $pdo->prepare("UPDATE employees SET profile_pic=? WHERE id=?");
            $stmt->execute([$filename, $user_id]);
            header("Location: upload_profile.php?updated=1");
            exit;
        } else {
            $error = "Error moving uploaded file.";
        }
    } else {
        $error = "Upload error code: " . $file['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

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

    <?php if (isset($_GET['updated'])): ?>
        <p class="success">Profile updated successfully!</p>
    <?php elseif (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label>Full Name</label>
        <input type="text" name="fullname" value="<?= htmlspecialchars($emp['fullname']) ?>">

        <label>Place of Work</label>
        <input type="text" name="place_of_work" value="<?= htmlspecialchars($emp['place_of_work']) ?>">

        <label>Profile Photo</label>
        <?php if (!empty($emp['profile_pic'])): ?>
            <div class="current-image-wrapper">
                <a href="../uploads/profiles/<?= htmlspecialchars($emp['profile_pic']) ?>" 
                   data-lightbox="profile-pic" 
                   data-title="Profile Photo">
                    <img src="../uploads/profiles/<?= htmlspecialchars($emp['profile_pic']) ?>?t=<?= time() ?>" alt="Profile Photo"                    " 
                        alt="Profile Photo" 
                        class="clickable-image" 
                        width="120">
                </a>
            </div>
        <?php endif; ?>

        <div class="file-input-wrapper">
            <input type="file" name="profile_pic" id="profile_image" accept="image/*" class="file-input">
        </div>

        <button type="submit" class="btn upload-button">
            <?= !empty($emp['profile_pic']) ? 'Update' : 'Upload' ?> Profile Photo
        </button>
    </form>

    <br>
    <a href="dashboard.php" class="btn">← Back to Dashboard</a>
</div>

<script>
    document.getElementById('profile_image')?.addEventListener('change', function () {
        document.getElementById('profileFileName').textContent =
            this.files[0] ? this.files[0].name : 'No file chosen';
    });
</script>

</body>
</html>
