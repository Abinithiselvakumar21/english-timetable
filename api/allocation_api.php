<?php
// ========================================================
// Staff Subject Allocation Backend API
// File: api/allocation_api.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['ADMIN']);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json');

try {
    // ----------------------------------------------------
    // 1. ALLOCATE STAFF TO SUBJECT & CLASS
    // ----------------------------------------------------
    if ($action === 'allocate') {
        $staff_id   = intval($_POST['staff_id'] ?? 0);
        $subject_id = intval($_POST['subject_id'] ?? 0);
        $class_id   = intval($_POST['class_id'] ?? 0);

        if (!$staff_id || !$subject_id || !$class_id) {
            echo json_encode(['success' => false, 'message' => 'Staff, Subject, and Class are all required.']);
            exit();
        }

        // 1. Check if this subject is already allocated for this class
        $dup = $pdo->prepare("SELECT id FROM staff_subjects WHERE subject_id = ? AND class_id = ?");
        $dup->execute([$subject_id, $class_id]);
        if ($dup->fetch()) {
            echo json_encode(['success' => false, 'message' => 'This subject is already allocated for this class. Delete existing allocation first.']);
            exit();
        }

        // 2. Check staff workload capacity
        // Get staff max workload
        $st_stmt = $pdo->prepare("SELECT name, max_weekly_workload FROM staff WHERE id = ?");
        $st_stmt->execute([$staff_id]);
        $staff = $st_stmt->fetch();

        // Get subject weekly hours
        $sub_stmt = $pdo->prepare("SELECT weekly_hours, subject_name FROM subjects WHERE id = ?");
        $sub_stmt->execute([$subject_id]);
        $sub = $sub_stmt->fetch();

        // Calculate current total allocated hours for this staff
        $tot_stmt = $pdo->prepare("SELECT SUM(s.weekly_hours) FROM staff_subjects ss 
                                   JOIN subjects s ON ss.subject_id = s.id 
                                   WHERE ss.staff_id = ?");
        $tot_stmt->execute([$staff_id]);
        $current_load = intval($tot_stmt->fetchColumn() ?: 0);

        if (($current_load + $sub['weekly_hours']) > $staff['max_weekly_workload']) {
            echo json_encode([
                'success' => false, 
                'message' => "Workload Exceeded! {$staff['name']} has max workload of {$staff['max_weekly_workload']} hrs/wk. Current: {$current_load} hrs, Adding this requires {$sub['weekly_hours']} hrs (Total: " . ($current_load + $sub['weekly_hours']) . " hrs)."
            ]);
            exit();
        }

        // 3. Insert allocation
        $stmt = $pdo->prepare("INSERT INTO staff_subjects (staff_id, subject_id, class_id, is_primary) VALUES (?, ?, ?, 1)");
        $stmt->execute([$staff_id, $subject_id, $class_id]);

        // 4. Ensure subject_requirements record exists
        $req = $pdo->prepare("INSERT INTO subject_requirements (class_id, subject_id, required_hours, assigned_hours) 
                              VALUES (?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE required_hours = ?");
        $req->execute([$class_id, $subject_id, $sub['weekly_hours'], 0, $sub['weekly_hours']]);

        echo json_encode(['success' => true, 'message' => "Successfully allocated {$sub['subject_name']} to {$staff['name']}!"]);
        exit();
    }

    // ----------------------------------------------------
    // 2. DELETE ALLOCATION
    // ----------------------------------------------------
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid allocation ID.']);
            exit();
        }

        $stmt = $pdo->prepare("DELETE FROM staff_subjects WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Allocation removed successfully.']);
        exit();
    }

    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        exit();
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit();
}
