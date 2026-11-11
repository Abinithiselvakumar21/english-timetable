<?php
// ========================================================
// Class, Periods & College Timings Backend API
// File: api/class_api.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['ADMIN']);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json');

try {
    // ----------------------------------------------------
    // 1. ADD NEW CLASS
    // ----------------------------------------------------
    if ($action === 'create_class') {
        $class_name    = trim($_POST['class_name'] ?? '');
        $academic_year = trim($_POST['academic_year'] ?? '2026-2027');
        $year          = intval($_POST['year'] ?? 1);
        $semester      = intval($_POST['semester'] ?? 1);
        $section       = trim($_POST['section'] ?? 'A');
        $room_no       = trim($_POST['room_no'] ?? 'Room-101');

        if (empty($class_name)) {
            echo json_encode(['success' => false, 'message' => 'Class Name is required.']);
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO classes (class_name, academic_year, year, semester, section, room_no) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$class_name, $academic_year, $year, $semester, $section, $room_no]);

        echo json_encode(['success' => true, 'message' => 'Class added successfully.']);
        exit();
    }

    // ----------------------------------------------------
    // 2. DELETE CLASS
    // ----------------------------------------------------
    elseif ($action === 'delete_class') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid class ID.']);
            exit();
        }

        $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Class deleted successfully.']);
        exit();
    }

    // ----------------------------------------------------
    // 3. ADD PERIOD / TIMING
    // ----------------------------------------------------
    elseif ($action === 'create_period') {
        $period_number = intval($_POST['period_number'] ?? 1);
        $period_name   = trim($_POST['period_name'] ?? '');
        $start_time    = trim($_POST['start_time'] ?? '');
        $end_time      = trim($_POST['end_time'] ?? '');
        $is_break      = isset($_POST['is_break']) ? 1 : 0;

        if (empty($period_name) || empty($start_time) || empty($end_time)) {
            echo json_encode(['success' => false, 'message' => 'All period timing fields are required.']);
            exit();
        }

        $chk = $pdo->prepare("SELECT id FROM periods WHERE period_number = ?");
        $chk->execute([$period_number]);
        if ($chk->fetch()) {
            echo json_encode(['success' => false, 'message' => "Period Number $period_number already exists."]);
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO periods (period_number, period_name, start_time, end_time, is_break) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$period_number, $period_name, $start_time, $end_time, $is_break]);

        echo json_encode(['success' => true, 'message' => 'College period added successfully.']);
        exit();
    }

    // ----------------------------------------------------
    // 4. DELETE PERIOD
    // ----------------------------------------------------
    elseif ($action === 'delete_period') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM periods WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Period removed.']);
        exit();
    }

    // ----------------------------------------------------
    // 5. TOGGLE WORKING DAY
    // ----------------------------------------------------
    elseif ($action === 'toggle_day') {
        $id = intval($_POST['id'] ?? 0);
        $is_working = intval($_POST['is_working'] ?? 0);

        $stmt = $pdo->prepare("UPDATE working_days SET is_working = ? WHERE id = ?");
        $stmt->execute([$is_working, $id]);

        echo json_encode(['success' => true, 'message' => 'Working day status updated.']);
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
