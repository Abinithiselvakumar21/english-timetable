<?php
// ========================================================
// Staff Management Backend API (CRUD)
// File: api/staff_api.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['ADMIN']);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json');

try {
    // ----------------------------------------------------
    // 1. ADD NEW STAFF
    // ----------------------------------------------------
    if ($action === 'create') {
        $staff_code  = trim($_POST['staff_code'] ?? '');
        $name        = trim($_POST['name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $designation = trim($_POST['designation'] ?? 'Assistant Professor');
        $specialization = trim($_POST['specialization'] ?? 'English Literature');
        $max_workload   = intval($_POST['max_weekly_workload'] ?? 16);
        $username    = trim($_POST['username'] ?? strtolower($staff_code));
        $password    = trim($_POST['password'] ?? 'admin123');
        $role        = trim($_POST['role'] ?? 'STAFF');

        if (empty($staff_code) || empty($name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Staff Code, Name and Email are required.']);
            exit();
        }

        $pdo->beginTransaction();

        // 1. Check if user or staff code exists
        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $chk->execute([$username, $email]);
        if ($chk->fetch()) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Username or Email already exists.']);
            exit();
        }

        // 2. Create User Account
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $u_stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, 'ACTIVE')");
        $u_stmt->execute([$username, $email, $hashed, $role]);
        $user_id = $pdo->lastInsertId();

        // 3. Create Staff Profile
        $s_stmt = $pdo->prepare("INSERT INTO staff (user_id, staff_code, name, email, phone, designation, department, specialization, max_weekly_workload, status) 
                                VALUES (?, ?, ?, ?, ?, ?, 'English', ?, ?, 'ACTIVE')");
        $s_stmt->execute([$user_id, $staff_code, $name, $email, $phone, $designation, $specialization, $max_workload]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Staff member added successfully with login access.']);
        exit();
    }

    // ----------------------------------------------------
    // 2. GET SINGLE STAFF DETAILS FOR EDIT
    // ----------------------------------------------------
    elseif ($action === 'get') {
        $id = intval($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT s.*, u.username, u.role FROM staff s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Staff not found.']);
        }
        exit();
    }

    // ----------------------------------------------------
    // 3. UPDATE STAFF
    // ----------------------------------------------------
    elseif ($action === 'update') {
        $id          = intval($_POST['id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $designation = trim($_POST['designation'] ?? 'Assistant Professor');
        $specialization = trim($_POST['specialization'] ?? '');
        $max_workload   = intval($_POST['max_weekly_workload'] ?? 16);
        $password    = trim($_POST['password'] ?? '');

        if (!$id || empty($name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
            exit();
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE staff SET name=?, email=?, phone=?, designation=?, specialization=?, max_weekly_workload=? WHERE id=?");
        $stmt->execute([$name, $email, $phone, $designation, $specialization, $max_workload, $id]);

        // If password is provided, update in users table
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $st_user = $pdo->prepare("SELECT user_id FROM staff WHERE id = ?");
            $st_user->execute([$id]);
            $uid = $st_user->fetchColumn();
            if ($uid) {
                $p_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $p_stmt->execute([$hashed, $uid]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Staff profile updated successfully.']);
        exit();
    }

    // ----------------------------------------------------
    // 4. DELETE STAFF
    // ----------------------------------------------------
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid staff ID.']);
            exit();
        }

        $pdo->beginTransaction();
        $st_user = $pdo->prepare("SELECT user_id FROM staff WHERE id = ?");
        $st_user->execute([$id]);
        $uid = $st_user->fetchColumn();

        $del_staff = $pdo->prepare("DELETE FROM staff WHERE id = ?");
        $del_staff->execute([$id]);

        if ($uid) {
            $del_user = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $del_user->execute([$uid]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Staff member deleted successfully.']);
        exit();
    }

    else {
        echo json_encode(['success' => false, 'message' => 'Unknown API action.']);
        exit();
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
    exit();
}
