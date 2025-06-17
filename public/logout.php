<?php
require_once __DIR__ . '/auth/auth.php';

logout_user();

// Redirect to login page after logout
header('Location: /login.php');
exit();
?>