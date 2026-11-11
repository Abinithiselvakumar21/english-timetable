<?php
// ========================================================
// Smart Notification System API
// File: api/notification_api.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_auth();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json');

try {
    // 1. GET NOTIFICATIONS FOR LOGGED IN STAFF
    if ($action === 'list') {
        $staff_id = $_SESSION['staff_id'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE staff_id = ? ORDER BY sent_at DESC LIMIT 15");
        $stmt->execute([$staff_id]);
        $list = $stmt->fetchAll();

        $unread_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE staff_id = ? AND is_read = 0");
        $unread_stmt->execute([$staff_id]);
        $unread = $unread_stmt->fetchColumn();

        echo json_encode(['success' => true, 'notifications' => $list, 'unread_count' => $unread]);
        exit();
    }

    // 2. MARK NOTIFICATION AS READ
    elseif ($action === 'mark_read') {
        $id = intval($_POST['id'] ?? 0);
        $staff_id = $_SESSION['staff_id'] ?? 0;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND staff_id = ?");
            $stmt->execute([$id, $staff_id]);
        } else {
            // Mark all read
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE staff_id = ?");
            $stmt->execute([$staff_id]);
        }

        echo json_encode(['success' => true]);
        exit();
    }

    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}
