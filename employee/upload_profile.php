<?php
// employee/profile.php
require_once __DIR__ . '/../includes/auth.php';
require_login('employee');
require_once __DIR__ . '/../includes/config.php';

$user_id = $_SESSION['user']['id'];

// Fetch employee info
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id=?");
$stmt->execute([$user_id]);
$emp = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "profile_" . $user_id . "." . strtolower($ext);
        $destination = __DIR__ . "/../uploads/profiles/" . $filename;

        // create uploads/profiles dir if missing
        if (!is_dir(__DIR__ . "/../uploads/profiles")) {
            mkdir(__DIR__ . "/../uploads/profiles", 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // save filename in DB (make sure column matches!)
            $stmt = $pdo->prepare("UPDATE employees SET profile_pic=? WHERE id=?");
            $stmt->execute([$filename, $user_id]);
            echo "Profile photo uploaded successfully.";
        } else {
            echo "Error moving uploaded file.";
        }
    } else {
        echo "Upload error code: " . $file['error'];
    }
}   else {
        echo "No file uploaded.";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- jQuery (required for Lightbox) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Lightbox2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
<div class="container">
    <h2>My Profile</h2>

    <?php if (isset($_GET['updated'])): ?>
        <p class="success">Profile updated successfully!</p>
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
                    <img src="../uploads/profiles/<?= htmlspecialchars($emp['profile_pic']) ?>" 
                        alt="Profile Photo" 
                        class="clickable-image" 
                        width="120">
                </a>
                <div class="image-actions">
                    <label for="profile_image" class="btn btn-secondary">Change Profile Photo</label>
                </div>
            </div>
        <?php endif; ?>

        <div class="file-input-wrapper" <?= !empty($emp['profile_pic']) ? 'style="display:none"' : '' ?>>
            <label for="profile_image" class="file-label">
                <span class="file-button">Choose Profile Photo</span>
                <span class="file-name" id="profileFileName">No file chosen</span>
            </label>
            <input type="file" name="profile_image" id="profile_image" accept="image/*" class="file-input">
        </div>

        <button type="submit" class="btn btn-primary upload-button">
            <?= !empty($emp['profile_pic']) ? 'Update' : 'Upload' ?> Profile Photo
        </button>

        <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>

    <a href="dashboard.php">← Back to Dashboard</a>
</div>

<script>
    document.getElementById('profile_image')?.addEventListener('change', function () {
        document.getElementById('profileFileName').textContent =
            this.files[0] ? this.files[0].name : 'No file chosen';
    });
</script>

</body>
</html>
