<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        // Count unread messages sent to admin (receiver_id = 0)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = 0 AND is_read = 0");
        $stmt->execute();
        $adminUnreadCount = $stmt->fetchColumn();
    } else {
        // Count unread messages sent to this employee
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user']['id']]);
        $employeeUnreadCount = $stmt->fetchColumn();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IROKS</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Lightbox2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* ===== HEADER STYLING ===== */
header {
    background: linear-gradient(135deg, #3ba31bff, #000000ff, #378d1dff);
    color: #fff;
    padding: 12px 0;
    border-bottom: 4px solid #e63946;
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    position: relative;
    top: 0;
    z-index: 1000;
}

header .container {
    width: 90%;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.img-responsive {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid white;
    box-shadow: 0 0 5px rgba(255,255,255,0.3);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

#logo-click:hover img {
    transform: scale(1.05) rotate(-2deg);
    box-shadow: 0 0 12px rgba(255, 255, 255, 0.6);
}

.logo {
    font-size: 1.6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.logo-and-welcome {
    display: flex;
    align-items: center;
    gap: 15px;
    flex: 1; /* take remaining space */
}

.logo-and-welcome img {
    height: 55px;
    border-radius: 50%;
    cursor: pointer;
    transition: transform 0.3s ease;
}
.logo-and-welcome img:hover {
    transform: scale(1.08);
}

.welcome-message {
    font-size: 16px;
    font-weight: 600;
    color: #f1faee;
    background: rgba(230, 57, 70, 0.1);
    padding: 6px 12px;
    border-radius: 20px;
    border: 1px solid #e63946;
}

/* Place nav (or hamburger) on the far right */
.radial-nav {
  position: relative;
  left: 50%;
}

.menu-toggle {
  width: 50px;
  height: 50px;
  background: #1b263b;
  color: #fff;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  font-size: 24px;
  transition: background 0.3s;
}

.menu-toggle:hover {
  background: #e63946;
}

/* Hidden wheel */
#radial-wheel {
  position: absolute;
  top: 50%;
  left: 100%;
  width: 50px;
  height: 50px;
  margin-top: -10px;
  margin-left: 100px;
  border-radius: 50%;
  display: none;
  justify-content: center;
  align-items: center;
  list-style: none;
  padding: 0;
}

#radial-wheel li {
  position: absolute;
  transform-origin: 100px 100px;
}

#radial-wheel li a {
  display: block;
  width: 50px;
  height: 50px;
  background: #1b263b;
  color: #fff;
  text-align: center;
  line-height: 50px;
  border-radius: 50%;
  font-size: 18px;
  transition: 0.3s;
}

#radial-wheel li a:hover,
#radial-wheel li a.active {
  background: #2ecc71;
  box-shadow: 0 0 12px #2ecc71;
}


/* Collapse nav on mobile */
@media (max-width: 768px) {
    header .container {
        flex-direction: row;
        justify-content: space-between;
    }

    nav {
        display: none;
        width: 100%;
        margin-top: 12px;
    }

    nav ul {
        display: none; /* hidden menu */
        flex-direction: column;
        position: absolute;
        top: 70px;
        right: 20px;
        background: #1b263b;
        border-radius: 6px;
        padding: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.25);
    }

    nav ul.show {
        display: flex;
    }

    /* Hamburger button */
    .hamburger {
        display:flex;
        cursor: pointer;
        font-size: 24px;
        margin-left: 270px;
    }

    /* Desktop view hides hamburger */
    .hamburger {
        display: none;
    }

    nav.active {
        display: flex;
    }

    .hamburger {
        display: flex;
    }
}
</style>
</head>
<body>
<header>
    <div class="container">
        <div class="header-content">
            <div class="logo-and-welcome">
                <div id="logo-click">
                    <img src="../assets/img/IROKS.jpg" alt="Website Logo" class="img-responsive">
                </div>

                <?php if (isLoggedIn() && isset($_SESSION['user']['fullname'])): ?>
                    <div class="welcome-message">
                        Welcome, <?= htmlspecialchars($_SESSION['user']['fullname']); ?>!
                    </div>
                <?php else: ?>
                    <div class="logo">IROKS Attendance</div>
                <?php endif; ?>
            </div>

            <div class="radial-nav">
  <div class="menu-toggle" onclick="toggleRadialMenu()">+</div>
  <ul id="radial-wheel">
    <li><a href="<?= isAdmin() ? '../admin/dashboard.php' : '../employee/dashboard.php' ?>" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i></a></li>
    
    <?php if (!isAdmin()): ?>
      <li><a href="../employee/upload_profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'upload_profile.php' ? 'active' : '' ?>"><i class="fas fa-user"></i></a></li>
      <li><a href="../employee/attendance.php" class="<?= basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : '' ?>"><i class="fas fa-clock"></i></a></li>
      <li><a href="../chat/chat.php" class="<?= basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : '' ?>"><i class="fas fa-comments"></i></a></li>
    <?php else: ?>
      <li><a href="../admin/employees.php" class="<?= basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : '' ?>"><i class="fas fa-users"></i></a></li>
      <li><a href="../admin/view_attendance.php" class="<?= basename($_SERVER['PHP_SELF']) == 'view_attendance.php' ? 'active' : '' ?>"><i class="fas fa-calendar-check"></i></a></li>
      <li><a href="../chat/chat.php" class="<?= basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : '' ?>"><i class="fas fa-comments"></i></a></li>
    <?php endif; ?>

    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i></a></li>
  </ul>
</div>


        </div>
    </div>
</header>

<main>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const logo = document.getElementById('logo-click');

    if (logo) {
        logo.style.cursor = 'pointer';
        logo.addEventListener('click', function () {
            Swal.fire({
                title: "Do you like our website? 💬",
                showDenyButton: true,
                confirmButtonText: 'Yes 😁!',
                denyButtonText: 'No 🙄',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didRender: () => {
                    const denyButton = document.querySelector('.swal2-deny');
                    if (denyButton) {
                        denyButton.style.position = 'relative';
                        denyButton.addEventListener('mouseenter', () => {
                            const maxX = 200, maxY = 100;
                            const randomX = Math.floor(Math.random() * maxX - maxX / 2);
                            const randomY = Math.floor(Math.random() * maxY - maxY / 2);
                            denyButton.style.transform = `translate(${randomX}px, ${randomY}px)`;
                        });
                        denyButton.addEventListener('click', (e) => {
                            e.preventDefault();
                            denyButton.dispatchEvent(new Event('mouseenter'));
                        });
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('Thanks! 😊', 'We’re glad you like it!', 'success');
                }
            });
        });
    }
});

function toggleRadialMenu() {
  const menu = document.getElementById("radial-wheel");
  menu.style.display = (menu.style.display === "flex") ? "none" : "flex";

  if (menu.style.display === "flex") {
    const items = menu.querySelectorAll("li");
    const total = items.length;
    const angleStep = 360 / total;

    items.forEach((item, index) => {
      const angle = index * angleStep - 90; // Start top
      const x = 80 * Math.cos(angle * Math.PI / 180);
      const y = 80 * Math.sin(angle * Math.PI / 180);
      item.style.transform = `translate(${x}px, ${y}px)`;
    });
  }
}

</script>
