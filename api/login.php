<?php
// ========================================================
// Authentication Login API with Password Hashing & Sessions
// File: api/login.php
// ========================================================

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "index.php?error=Invalid request method");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$selected_role = trim($_POST['role'] ?? '');

if (empty($username) || empty($password)) {
    header("Location: " . BASE_URL . "index.php?error=Username and password are required");
    exit();
}

try {
    // Check credentials in users table
    $stmt = $pdo->prepare("SELECT u.*, s.name as staff_name, s.id as staff_id 
                           FROM users u 
                           LEFT JOIN staff s ON u.id = s.user_id 
                           WHERE (u.username = :login OR u.email = :login) AND u.status = 'ACTIVE' 
                           LIMIT 1");
    $stmt->execute([':login' => $username]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: " . BASE_URL . "index.php?error=Invalid username or inactive account");
        exit();
    }

    // Verify Password
    if (!password_verify($password, $user['password'])) {
        header("Location: " . BASE_URL . "index.php?error=Incorrect password");
        exit();
    }

    // Role Match Check (if selected)
    if (!empty($selected_role) && $user['role'] !== $selected_role) {
        header("Location: " . BASE_URL . "index.php?error=Account exists but does not match selected role: $selected_role");
        exit();
    }

    // Set Session Variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['staff_name'] ?? ucfirst($user['username']);
    $_SESSION['staff_id'] = $user['staff_id'] ?? null;
    $_SESSION['logged_in_time'] = time();

    // Redirect based on role
    switch ($user['role']) {
        case 'ADMIN':
            header("Location: " . BASE_URL . "admin/dashboard.php?success=Welcome back, Administrator!");
            break;
        case 'HOD':
            header("Location: " . BASE_URL . "hod/dashboard.php?success=Welcome back, Head of Department!");
            break;
        case 'STAFF':
            header("Location: " . BASE_URL . "staff/dashboard.php?success=Welcome back, " . urlencode($_SESSION['name']));
            break;
        default:
            header("Location: " . BASE_URL . "index.php?error=Undefined user role");
            break;
    }
    exit();

} catch (PDOException $e) {
    header("Location: " . BASE_URL . "index.php?error=Database error occurred: " . urlencode($e->getMessage()));
    exit();
}
?>
