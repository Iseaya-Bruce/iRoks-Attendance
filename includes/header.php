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
    animation: rgbGlow 5s infinite linear;
}

@keyframes rgbGlow {
  0%   { box-shadow: 0 0 15px red, 0 0 30px red, 0 0 45px red; }
  25%  { box-shadow: 0 0 15px orange, 0 0 30px orange, 0 0 45px orange; }
  50%  { box-shadow: 0 0 15px lime, 0 0 30px lime, 0 0 45px lime; }
  75%  { box-shadow: 0 0 15px cyan, 0 0 30px cyan, 0 0 45px cyan; }
  100% { box-shadow: 0 0 15px red, 0 0 30px red, 0 0 45px red; }
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

/* === HEADER CONTAINER === */
header {
  background: #1b263b;
  padding: 12px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 100%;
}

header .logo {
  color: #fff;
  font-size: 18px;
  font-weight: 600;
}

/* === RADIAL NAV === */
.radial-nav {
    position: absolute;
    top: 20px;      /* adjust depending on header height */
    right: 30px;    /* pushes it to the right side */
    z-index: 999;
}

.radial-nav .menu-toggle {
    cursor: pointer;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #333;
    color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
}

.radial-nav ul {
    list-style: none;
    margin: 0;
    padding: 0;
    position: relative;
}

.radial-nav ul li {
    margin: 5px 0;
}

.radial-nav ul li a {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: #444;
    color: white;
    transition: 0.3s;
}

.radial-nav ul li a.active {
    background: #00aaff; /* highlight active */
}

.radial-nav ul li a:hover {
    background: #666;
}

/* === FIX horizontal scroll issue on desktop === */
html, body {
  overflow-x: hidden; /* Prevent horizontal scrolling globally */
}

/* Ensure side elements don’t push beyond viewport */
.radial-nav,
.side-nav,
.chat-fab {
  right: clamp(10px, 2vw, 20px); /* Keeps inside viewport on all screen sizes */
  max-width: 100vw;
  overflow: visible;
}



/* Optional: prevent flex overflow inside header */
header {
  overflow-x: clip; /* modern replacement for hidden (more performant) */
}

/* Fix side-nav animation overshoot */
.side-nav {
  transform: translateX(110%);
}
.side-nav.open {
  transform: translateX(0);
}


/* === RESPONSIVE === */
@media (max-width: 768px) {
  header {
    flex-direction: row;
    justify-content: space-between;
  }

  .menu-toggle {
    width: 45px;
    height: 45px;
    font-size: 20px;
  }

  #radial-wheel {
    width: 160px;
    height: 160px;
    margin-left: -80px;
    margin-top: -80px;
  }

  #radial-wheel li a {
    width: 40px;
    height: 40px;
    line-height: 40px;
    font-size: 16px;
  }

}
/* Mobile header override - right-side vertical menu */
@media (max-width: 768px) {
  header .container { align-items: center; }
  nav { display: block; margin-left: auto; width: auto; }
  nav ul {
    display: none; /* hidden until toggled */
    position: fixed;
    top: 70px; /* below header */
    right: 12px;
    width: min(70vw, 260px);
    max-height: calc(100vh - 90px);
    overflow-y: auto;
    flex-direction: column;
    gap: 10px;
    background: #1b263b;
    border-radius: 10px;
    padding: 12px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.45);
    z-index: 1100;
  }
  nav ul.show { display: flex; }
  nav ul li { width: 100%; }
  nav ul li a {
    display: block;
    width: 100%;
    background: #32CD32;
    color: #111;
    border-radius: 8px;
    padding: 10px 12px;
    box-shadow: 0 0 6px rgba(50,205,50,0.25);
  }
  nav ul li a:hover { background: #28a428; box-shadow: 0 0 10px rgba(50,205,50,0.45); }
  .hamburger { display: flex; cursor: pointer; font-size: 24px; margin-left: 12px; }
}
/* Right-side vertical sidebar and hamburger */
.side-nav { position: absolute; top: 80px; right: 12px; display: flex; flex-direction: column; z-index: 1100; }
.side-nav ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
.side-nav a { display: flex; align-items: center; gap: 8px; background: #1b263b; color: #fff; border: 1px solid rgba(255,255,255,0.08); padding: 10px 12px; border-radius: 10px; text-decoration: none; box-shadow: 0 0 6px rgba(50,205,50,0.25); }
.side-nav a:hover { background: #22324d; box-shadow: 0 0 10px rgba(50,205,50,0.45); }
.side-nav a i { width: 18px; text-align: center; }
.side-nav a span { display: inline; }

.hamburger { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; padding: 6px; display: none; flex-direction: column; gap: 4px; cursor: pointer; }
.hamburger span { width: 22px; height: 2px; background: #fff; display: block; }

/* Desktop: collapsed by default, toggle with hamburger (same as mobile) */
@media (min-width: 769px) {
  .hamburger { display: inline-flex; }
  .side-nav { transform: translateX(120%); opacity: 0; pointer-events: none; transition: transform 0.25s ease, opacity 0.2s ease; }
  .side-nav.open { transform: translateX(0); opacity: 1; pointer-events: auto; }
  .side-nav a span { display: inline; }
}

/* Mobile: collapsed by default, toggle with hamburger */
@media (max-width: 768px) {
  .hamburger { display: inline-flex; }
  .side-nav { transform: translateX(120%); opacity: 0; pointer-events: none; transition: transform 0.25s ease, opacity 0.2s ease; top: 70px; }
  .side-nav.open { transform: translateX(0); opacity: 1; pointer-events: auto; }
}
/* Floating chat icon badge on the right (always visible) */
.chat-fab {
  position: absolute;
  top: 50%;
  right: 16px;
  transform: translateY(-50%);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: rgba(255,255,255,0.10);
  color: #fff;
  text-decoration: none;
  z-index: 1200;
}
.chat-fab:hover { background: rgba(255,255,255,0.18); }
.chat-fab .badge {
  position: absolute;
  top: -6px;
  right: -6px;
  background: #e63946;
  color: #fff;
  border-radius: 999px;
  font-size: 11px;
  padding: 2px 6px;
  min-width: 18px;
  line-height: 1;
  text-align: center;
  box-shadow: 0 0 4px rgba(0,0,0,0.4);
}
/* Space for chat fab so it doesn't overlap side-nav/hamburger on mobile */
@media (max-width: 768px) {
  .header-content { position: relative; padding-right: 60px; }
}
/* Ensure side-nav list is visible when panel is open on mobile */
@media (max-width: 768px) {
  nav#side-nav ul { display: flex; }
}
</style>
</head>
<body>
<header>
    <div class="container">
        <div class="header-content">
            <?php if (isLoggedIn()): ?>
                <?php $unreadCount = isAdmin() ? (int)($adminUnreadCount ?? 0) : (int)($employeeUnreadCount ?? 0); ?>
                <a href="../chat/conversation.php" class="chat-fab" aria-label="Chat">
                    <i class="fas fa-comments"></i>
                    <?php if ($unreadCount > 0): ?><span class="badge"><?= $unreadCount ?></span><?php endif; ?>
                </a>
            <?php endif; ?>
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

            <!-- Right-aligned vertical navigation -->
            <button class="hamburger" id="hamburger-btn" aria-label="Toggle menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <nav id="side-nav" class="side-nav" aria-hidden="true">
                <ul>
                    <li>
                        <a href="<?= isAdmin() ? '../admin/dashboard.php' : '../employee/dashboard.php' ?>" title="Dashboard">
                            <i class="fas fa-home"></i><span>Dashboard</span>
                        </a>
                    </li>

                    <?php if (!isAdmin()): ?>
                        <li>
                            <a href="../employee/upload_profile.php" title="My Profile">
                                <i class="fas fa-user"></i><span>My Profile</span>
                            </a>
                        </li>
                        <li>
                            <a href="../employee/attendance.php" title="My Attendance">
                                <i class="fas fa-clock"></i><span>My Attendance</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="../admin/request.php" title="Employees">
                                <i class="fas fa-users"></i><span>Request</span>
                            </a>
                        </li>
                        <li>
                            <a href="../admin/view_attendance.php" title="Attendance Records">
                                <i class="fas fa-calendar-check"></i><span>Attendance</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <li>
                        <a href="../chat/conversation.php" title="Chat">
                            <i class="fas fa-comments"></i><span>Chat</span>
                        </a>
                    </li>
                    <li>
                        <a href="../logout.php" title="Logout">
                            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
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

document.addEventListener('DOMContentLoaded', function () {
  const btn = document.getElementById('hamburger-btn');
  const side = document.getElementById('side-nav');
  if (!btn || !side) return;
  btn.addEventListener('click', function () {
    side.classList.toggle('open');
    const isOpen = side.classList.contains('open');
    btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    side.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
  });
});


</script>
