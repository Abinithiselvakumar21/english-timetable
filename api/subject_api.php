<?php
// ========================================================
// Subject Management Backend API (Major & Foundation)
// File: api/subject_api.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['ADMIN']);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json');

try {
    // ----------------------------------------------------
    // 1. CREATE SUBJECT
    // ----------------------------------------------------
    if ($action === 'create') {
        $subject_code = strtoupper(trim($_POST['subject_code'] ?? ''));
        $subject_name = trim($_POST['subject_name'] ?? '');
        $category     = trim($_POST['category'] ?? 'MAJOR');
        $semester     = intval($_POST['semester'] ?? 1);
        $weekly_hours = intval($_POST['weekly_hours'] ?? 4);
        $description  = trim($_POST['description'] ?? '');

        // Color coding: Blue for Major, Green for Foundation
        $color_code   = ($category === 'MAJOR') ? '#0d6efd' : '#198754';

        if (empty($subject_code) || empty($subject_name)) {
            echo json_encode(['success' => false, 'message' => 'Subject Code and Subject Name are required.']);
            exit();
        }

        // Check duplicate code
        $chk = $pdo->prepare("SELECT id FROM subjects WHERE subject_code = ?");
        $chk->execute([$subject_code]);
        if ($chk->fetch()) {
            echo json_encode(['success' => false, 'message' => "Subject Code '$subject_code' is already in use."]);
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name, category, semester, weekly_hours, color_code, description) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$subject_code, $subject_name, $category, $semester, $weekly_hours, $color_code, $description]);

        echo json_encode(['success' => true, 'message' => ucfirst(strtolower($category)) . ' subject created successfully.']);
        exit();
    }

    // ----------------------------------------------------
    // 2. GET SINGLE SUBJECT
    // ----------------------------------------------------
    elseif ($action === 'get') {
        $id = intval($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
        $stmt->execute([$id]);
        $sub = $stmt->fetch();
        if ($sub) {
            echo json_encode(['success' => true, 'data' => $sub]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Subject not found.']);
        }
        exit();
    }

    // ----------------------------------------------------
    // 3. UPDATE SUBJECT
    // ----------------------------------------------------
    elseif ($action === 'update') {
        $id           = intval($_POST['id'] ?? 0);
        $subject_name = trim($_POST['subject_name'] ?? '');
        $category     = trim($_POST['category'] ?? 'MAJOR');
        $semester     = intval($_POST['semester'] ?? 1);
        $weekly_hours = intval($_POST['weekly_hours'] ?? 4);
        $description  = trim($_POST['description'] ?? '');
        $color_code   = ($category === 'MAJOR') ? '#0d6efd' : '#198754';

        if (!$id || empty($subject_name)) {
            echo json_encode(['success' => false, 'message' => 'Invalid subject details provided.']);
            exit();
        }

        $stmt = $pdo->prepare("UPDATE subjects SET subject_name=?, category=?, semester=?, weekly_hours=?, color_code=?, description=? WHERE id=?");
        $stmt->execute([$subject_name, $category, $semester, $weekly_hours, $color_code, $description, $id]);

        echo json_encode(['success' => true, 'message' => 'Subject updated successfully.']);
        exit();
    }

    // ----------------------------------------------------
    // 4. DELETE SUBJECT
    // ----------------------------------------------------
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid subject ID.']);
            exit();
        }

        $del = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $del->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Subject removed from system.']);
        exit();
    }

    else {
        echo json_encode(['success' => false, 'message' => 'Unknown API action.']);
        exit();
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
    exit();
}
