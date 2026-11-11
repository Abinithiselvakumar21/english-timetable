<?php
// ========================================================
// Authentication & Role-Based Access Control Guard
// File: includes/auth_check.php
// ========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if user is logged in
 */
function check_auth() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "index.php?error=Please login to continue");
        exit();
    }
}

/**
 * Checks if user has specific allowed role(s)
 * e.g. check_role(['ADMIN']) or check_role(['ADMIN', 'HOD'])
 */
function check_role(array $allowed_roles) {
    check_auth();
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // Redirect to their respective dashboard
        if ($_SESSION['role'] === 'ADMIN') {
            header("Location: " . BASE_URL . "admin/dashboard.php?error=Access Denied");
        } elseif ($_SESSION['role'] === 'HOD') {
            header("Location: " . BASE_URL . "hod/dashboard.php?error=Access Denied");
        } else {
            header("Location: " . BASE_URL . "staff/dashboard.php?error=Access Denied");
        }
        exit();
    }
}
?>
