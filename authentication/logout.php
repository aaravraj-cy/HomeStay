<?php
// Logout
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    $conn->prepare('UPDATE users SET remember_token = NULL WHERE id = ?')->execute([$_SESSION['user_id']]);
}

if (isset($_COOKIE['sn_remember'])) {
    setcookie('sn_remember', '', time() - 3600, '/');
}

session_destroy();
session_start();
set_flash('success', 'You have logged out.');
redirect(BASE_URL . 'authentication/login.php');
