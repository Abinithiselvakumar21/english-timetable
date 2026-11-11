<?php
// ========================================================
// Timetable Generation & Workflow API
// Connects PHP to Node.js AI Service
// File: api/timetable_api.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json');

try {
    // ----------------------------------------------------
    // 1. RUN AI GENERATOR (Admin Role)
    // ----------------------------------------------------
    if ($action === 'generate') {
        check_role(['ADMIN']);

        // Collect all data from MySQL
        $classes = $pdo->query("SELECT * FROM classes")->fetchAll();
        $subjects = $pdo->query("SELECT * FROM subjects")->fetchAll();
        $staff = $pdo->query("SELECT * FROM staff WHERE status = 'ACTIVE'")->fetchAll();
        $allocations = $pdo->query("SELECT * FROM staff_subjects")->fetchAll();
        $periods = $pdo->query("SELECT * FROM periods ORDER BY period_number ASC")->fetchAll();
        $workingDays = $pdo->query("SELECT * FROM working_days WHERE is_working = 1 ORDER BY day_order ASC")->fetchAll();
        $staffAvailability = $pdo->query("SELECT * FROM staff_availability WHERE is_available = 0")->fetchAll();

        if (empty($allocations)) {
            echo json_encode(['success' => false, 'message' => 'No staff allocations found. Please allocate staff first in the allocation menu.']);
            exit();
        }

        $payload = [
            'classes'           => $classes,
            'subjects'          => $subjects,
            'staff'             => $staff,
            'allocations'       => $allocations,
            'periods'           => $periods,
            'workingDays'       => $workingDays,
            'staffAvailability' => $staffAvailability
        ];

        // Send HTTP POST to Node.js AI Microservice
        $ch = curl_init(NODE_AI_URL . 'generate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            echo json_encode([
                'success' => false, 
                'message' => 'Node.js AI Engine is unreachable at ' . NODE_AI_URL . '. Please ensure Node.js server is running on Port 5000. (Error: ' . $curlError . ')'
            ]);
            exit();
        }

        $result = json_decode($response, true);
        if (!$result || empty($result['success'])) {
            echo json_encode([
                'success' => false, 
                'message' => $result['message'] ?? 'AI Engine failed to find a valid schedule.'
            ]);
            exit();
        }

        $slots = $result['slots'] ?? [];

        // Save generated timetable into MySQL
        $pdo->beginTransaction();

        $version_code = 'VER-' . date('Ymd-His');
        $academic_year = '2026-2027';

        // 1. Insert Version Record
        $v_stmt = $pdo->prepare("INSERT INTO timetable_versions (version_code, academic_year, generated_by, status) VALUES (?, ?, ?, 'DRAFT')");
        $v_stmt->execute([$version_code, $academic_year, $_SESSION['user_id']]);
        $version_id = $pdo->lastInsertId();

        // 2. Insert Slots
        $s_stmt = $pdo->prepare("INSERT INTO timetable (version_id, class_id, subject_id, staff_id, day_name, period_id, category, room_no) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($slots as $slot) {
            $s_stmt->execute([
                $version_id,
                $slot['class_id'],
                $slot['subject_id'],
                $slot['staff_id'],
                $slot['day_name'],
                $slot['period_id'],
                $slot['category'],
                $slot['room_no']
            ]);
        }

        $pdo->commit();

        echo json_encode([
            'success'      => true,
            'message'      => "AI Timetable generated and saved successfully! ({$result['message']})",
            'version_id'   => $version_id,
            'version_code' => $version_code,
            'total_slots'  => count($slots)
        ]);
        exit();
    }

    // ----------------------------------------------------
    // 2. HOD APPROVE / REJECT / PUBLISH
    // ----------------------------------------------------
    elseif ($action === 'update_status') {
        check_role(['HOD', 'ADMIN']);

        $version_id = intval($_POST['version_id'] ?? 0);
        $status     = trim($_POST['status'] ?? '');
        $remarks    = trim($_POST['remarks'] ?? '');

        if (!$version_id || !in_array($status, ['APPROVED', 'REJECTED', 'PUBLISHED', 'UNDER_REVIEW'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid version or status.']);
            exit();
        }

        $pdo->beginTransaction();

        if ($status === 'PUBLISHED') {
            // Unpublish previous published versions
            $pdo->query("UPDATE timetable_versions SET status = 'APPROVED' WHERE status = 'PUBLISHED'");
            
            $stmt = $pdo->prepare("UPDATE timetable_versions SET status = 'PUBLISHED', hod_remarks = ?, published_at = NOW() WHERE id = ?");
            $stmt->execute([$remarks, $version_id]);

            // Create notification alerts for all faculty
            $staffs = $pdo->query("SELECT id FROM staff WHERE status = 'ACTIVE'")->fetchAll();
            $n_stmt = $pdo->prepare("INSERT INTO notifications (staff_id, title, message, type) VALUES (?, ?, ?, 'PUBLISH_ALERT')");
            foreach ($staffs as $st) {
                $n_stmt->execute([
                    $st['id'],
                    'New Timetable Published!',
                    'The official English Department Timetable has been reviewed, approved and published by HOD.',
                ]);
            }

        } elseif ($status === 'APPROVED') {
            $stmt = $pdo->prepare("UPDATE timetable_versions SET status = 'APPROVED', hod_remarks = ?, approved_at = NOW() WHERE id = ?");
            $stmt->execute([$remarks, $version_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE timetable_versions SET status = ?, hod_remarks = ? WHERE id = ?");
            $stmt->execute([$status, $remarks, $version_id]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "Timetable status updated to $status."]);
        exit();
    }

    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        exit();
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
    exit();
}
