<?php
// ========================================================
// Safe Logout Handler
// File: api/logout.php
// ========================================================

require_once __DIR__ . '/../config/db.php';

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();
header("Location: " . BASE_URL . "index.php?success=You have successfully logged out");
exit();
?>
