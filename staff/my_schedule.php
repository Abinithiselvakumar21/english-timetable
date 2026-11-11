<?php
// ========================================================
// Staff Personal Weekly Schedule View
// File: staff/my_schedule.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['STAFF', 'HOD']);

$page_title = 'My Weekly Teaching Schedule';
$staff_id = $_SESSION['staff_id'];

// Get active published or latest approved timetable version
$pub_ver = $pdo->query("SELECT * FROM timetable_versions WHERE status = 'PUBLISHED' ORDER BY id DESC LIMIT 1")->fetch();
if (!$pub_ver) {
    $pub_ver = $pdo->query("SELECT * FROM timetable_versions ORDER BY id DESC LIMIT 1")->fetch();
}

$working_days = $pdo->query("SELECT * FROM working_days WHERE is_working = 1 ORDER BY day_order ASC")->fetchAll();
$periods = $pdo->query("SELECT * FROM periods ORDER BY period_number ASC")->fetchAll();

$my_slots = [];
if ($pub_ver && $staff_id) {
    $stmt = $pdo->prepare("SELECT t.*, s.subject_name, s.subject_code, s.category, c.class_name, c.room_no
                           FROM timetable t
                           JOIN subjects s ON t.subject_id = s.id
                           JOIN classes c ON t.class_id = c.id
                           WHERE t.version_id = ? AND t.staff_id = ?");
    $stmt->execute([$pub_ver['id'], $staff_id]);
    $list = $stmt->fetchAll();

    foreach ($list as $slot) {
        $my_slots[$slot['day_name']][$slot['period_id']] = $slot;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- BANNER -->
<div class="card border-0 rounded-4 p-4 text-white mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <span class="badge bg-white bg-opacity-15 px-3 py-1 mb-2 fw-semibold">
        <i class="fa-solid fa-calendar-week me-1"></i> Academic Session 2026-2027
      </span>
      <h3 class="fw-bold mb-1">My Personal Weekly Schedule</h3>
      <p class="text-white-50 mb-0">Faculty schedule view for <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong> (English Dept).</p>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-light fw-semibold" onclick="window.print()">
        <i class="fa-solid fa-print me-1"></i> Print My Schedule
      </button>
      <a href="<?php echo BASE_URL; ?>admin/master_timetable.php" class="btn btn-primary fw-bold shadow">
        <i class="fa-solid fa-eye me-1"></i> Master Timetable
      </a>
    </div>
  </div>
</div>

<!-- WEEKLY SCHEDULE GRID -->
<div class="table-card p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h5 class="fw-bold text-dark mb-0">Weekly Class Distribution Matrix</h5>
      <small class="text-muted">Active Timetable Version: <strong><?php echo htmlspecialchars($pub_ver['version_code'] ?? 'N/A'); ?></strong> (<?php echo $pub_ver['status'] ?? 'Draft'; ?>)</small>
    </div>
    <div class="d-flex gap-2">
      <span class="badge-major"><i class="fa-solid fa-star"></i> Major Subject</span>
      <span class="badge-foundation"><i class="fa-solid fa-seedling"></i> Foundation Subject</span>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered text-center align-middle" style="min-width: 800px;">
      <thead class="bg-light">
        <tr>
          <th style="width: 130px;" class="py-3">Day / Timing</th>
          <?php foreach ($periods as $p): ?>
            <th class="<?php echo $p['is_break'] ? 'table-warning bg-opacity-25' : ''; ?> py-3">
              <div class="fw-bold text-dark"><?php echo htmlspecialchars($p['period_name']); ?></div>
              <small class="text-muted fw-normal" style="font-size: 0.72rem;">
                <?php echo date('h:i', strtotime($p['start_time'])); ?> - <?php echo date('h:i A', strtotime($p['end_time'])); ?>
              </small>
            </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($working_days as $day): ?>
          <tr>
            <td class="fw-bold text-dark bg-light"><?php echo $day['day_name']; ?></td>
            <?php foreach ($periods as $p): ?>
              <?php if ($p['is_break']): ?>
                <td class="bg-warning bg-opacity-10 text-muted small fw-bold">
                  <i class="fa-solid fa-mug-hot d-block mb-1"></i> BREAK
                </td>
              <?php else: 
                $slot = $my_slots[$day['day_name']][$p['id']] ?? null;
              ?>
                <td class="p-2" style="width: 16%;">
                  <?php if ($slot): 
                    $isMajor = ($slot['category'] === 'MAJOR');
                  ?>
                    <div class="timetable-slot <?php echo $isMajor ? 'major-slot' : 'foundation-slot'; ?>">
                      <div class="d-flex justify-content-between align-items-start">
                        <span class="badge <?php echo $isMajor ? 'bg-primary' : 'bg-success'; ?> text-white" style="font-size: 0.65rem;">
                          <?php echo $slot['category']; ?>
                        </span>
                        <span class="badge bg-white text-dark border" style="font-size: 0.65rem;">
                          <?php echo htmlspecialchars($slot['room_no']); ?>
                        </span>
                      </div>
                      
                      <div class="fw-bold text-dark my-1" style="font-size: 0.82rem;">
                        <?php echo htmlspecialchars($slot['subject_name']); ?>
                      </div>

                      <div class="text-primary fw-semibold" style="font-size: 0.75rem;">
                        <i class="fa-solid fa-graduation-cap me-1"></i> <?php echo htmlspecialchars($slot['class_name']); ?>
                      </div>
                    </div>
                  <?php else: ?>
                    <div class="timetable-slot free-slot">
                      <span>Free Period</span>
                    </div>
                  <?php endif; ?>
                </td>
              <?php endif; ?>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
