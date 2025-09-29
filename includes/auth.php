<?php
// includes/auth.php
require_once __DIR__ . '/config.php';

/**
 * require_login - ensure a user is logged in as either employee or admin
 * $roleAllowed: 'admin' or 'employee' or 'any'
 */
function require_login($roleAllowed = 'any') {
    if (!isset($_SESSION['user'])) {
        header('Location: /login.php');
        exit;
    }

    // user session structure:
    // $_SESSION['user'] = ['id'=>..., 'type'=>'employee'|'admin', 'fullname'=>...];
    if ($roleAllowed === 'admin' && $_SESSION['user']['type'] !== 'admin') {
        header('Location: /login.php');
        exit;
    }
    if ($roleAllowed === 'employee' && $_SESSION['user']['type'] !== 'employee') {
        header('Location: /login.php');
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['type'] === 'admin';
}
