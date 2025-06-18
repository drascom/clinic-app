<?php
require_once 'includes/db.php';
require_once 'auth/auth.php';

// If user is already logged in, redirect to calendar page
if (is_logged_in()) {
    if (is_staff()) {
        header('Location: /staff/dashboard.php');
        exit();
    } else {
        header('Location: /dashboard.php');
    }
    exit();
}else {
    header('Location: login.php');
    exit();
}
