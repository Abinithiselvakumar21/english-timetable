<?php
// ========================================================
// Staff / Faculty Dashboard
// File: staff/dashboard.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['STAFF']);

$page_title = 'Faculty Schedule Portal';
$staff_id = $_SESSION['staff_id'];

// Get Staff profile info
$staff_stmt = $pdo->prepare("SELECT * FROM staff WHERE id = :id");
$staff_stmt->execute([':id' => $staff_id]);
$staff = $staff_stmt->fetch();

// Today's day name (e.g. Monday)
$today = date('l');

// Check published timetable version
$pub_ver = $pdo->query("SELECT id, version_code FROM timetable_versions WHERE status = 'PUBLISHED' ORDER BY id DESC LIMIT 1")->fetch();

$today_classes = [];
$total_classes_week = 0;

if ($pub_ver && $staff_id) {
    // Fetch today's classes
    $t_stmt = $pdo->prepare("SELECT t.*, s.subject_name, s.subject_code, s.category, c.class_name, c.room_no, p.period_name, p.start_time, p.end_time 
                             FROM timetable t
                             JOIN subjects s ON t.subject_id = s.id
                             JOIN classes c ON t.class_id = c.id
                             JOIN periods p ON t.period_id = p.id
                             WHERE t.version_id = :ver_id AND t.staff_id = :staff_id AND t.day_name = :today
                             ORDER BY p.period_number ASC");
    $t_stmt->execute([
        ':ver_id'   => $pub_ver['id'],
        ':staff_id' => $staff_id,
        ':today'    => $today
    ]);
    $today_classes = $t_stmt->fetchAll();

    // Total weekly assigned count
    $w_stmt = $pdo->prepare("SELECT count(*) FROM timetable WHERE version_id = :ver_id AND staff_id = :staff_id");
    $w_stmt->execute([':ver_id' => $pub_ver['id'], ':staff_id' => $staff_id]);
    $total_classes_week = $w_stmt->fetchColumn();
}

// Fetch Assigned Courses
$assigned_courses = [];
if ($staff_id) {
    $c_stmt = $pdo->prepare("SELECT s.subject_code, s.subject_name, s.category, c.class_name 
                             FROM staff_subjects ss
                             JOIN subjects s ON ss.subject_id = s.id
                             JOIN classes c ON ss.class_id = c.id
                             WHERE ss.staff_id = :staff_id");
    $c_stmt->execute([':staff_id' => $staff_id]);
    $assigned_courses = $c_stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- BANNER -->
<div class="card border-0 rounded-4 p-4 text-white mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <span class="badge bg-white bg-opacity-15 px-3 py-1 mb-2 fw-semibold">
        <i class="fa-solid fa-calendar-day me-1"></i> Today is <?php echo date('l, F j, Y'); ?>
      </span>
      <h3 class="fw-bold mb-1">Hello, <?php echo htmlspecialchars($staff['name'] ?? $_SESSION['name']); ?>!</h3>
      <p class="text-white-50 mb-0">Specialization: <?php echo htmlspecialchars($staff['specialization'] ?? 'English Studies'); ?></p>
    </div>
    <div>
      <a href="<?php echo BASE_URL; ?>staff/my_schedule.php" class="btn btn-primary fw-bold px-4 py-2 rounded-3 shadow">
        <i class="fa-solid fa-calendar-week me-1"></i> Full Weekly Timetable
      </a>
    </div>
  </div>
</div>

<!-- STATS -->
<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-4">
    <div class="stat-card">
      <div class="stat-icon primary"><i class="fa-solid fa-chalkboard"></i></div>
      <div>
        <div class="stat-value"><?php echo count($today_classes); ?></div>
        <div class="stat-label">Classes Scheduled Today</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="stat-card">
      <div class="stat-icon success"><i class="fa-solid fa-clock-rotate-left"></i></div>
      <div>
        <div class="stat-value text-success"><?php echo $total_classes_week; ?> / <?php echo $staff['max_weekly_workload'] ?? 16; ?></div>
        <div class="stat-label">Assigned Hours This Week</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="stat-card">
      <div class="stat-icon warning"><i class="fa-solid fa-book-bookmark"></i></div>
      <div>
        <div class="stat-value text-warning"><?php echo count($assigned_courses); ?></div>
        <div class="stat-label">Assigned Subjects</div>
      </div>
    </div>
  </div>
</div>

<!-- TODAY'S TIMETABLE SCHEDULE -->
<div class="row g-4">
  <div class="col-lg-8">
    <div class="table-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark mb-0">Today's Class Schedule (<?php echo $today; ?>)</h5>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1">
          <i class="fa-solid fa-bell me-1"></i> Live Schedule
        </span>
      </div>

      <?php if (!empty($today_classes)): ?>
        <div class="table-responsive">
          <table class="table custom-table">
            <thead>
              <tr>
                <th>Timing</th>
                <th>Subject</th>
                <th>Category</th>
                <th>Class</th>
                <th>Room</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($today_classes as $cls): ?>
              <tr>
                <td class="fw-bold text-dark">
                  <i class="fa-regular fa-clock text-muted me-1"></i>
                  <?php echo date('h:i A', strtotime($cls['start_time'])); ?> - <?php echo date('h:i A', strtotime($cls['end_time'])); ?>
                </td>
                <td class="fw-bold"><?php echo htmlspecialchars($cls['subject_name']); ?></td>
                <td>
                  <?php if ($cls['category'] === 'MAJOR'): ?>
                    <span class="badge-major"><i class="fa-solid fa-star"></i> Major</span>
                  <?php else: ?>
                    <span class="badge-foundation"><i class="fa-solid fa-seedling"></i> Foundation</span>
                  <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($cls['class_name']); ?></td>
                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($cls['room_no']); ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-center py-5 text-muted">
          <i class="fa-solid fa-mug-hot fa-3x mb-2 text-secondary opacity-50"></i>
          <h6>No Teaching Classes Scheduled for Today</h6>
          <p class="small">Use this free time for research, literature review, or student counseling.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ASSIGNED COURSES CARD -->
  <div class="col-lg-4">
    <div class="table-card p-4 h-100">
      <h5 class="fw-bold text-dark mb-3">My Assigned Courses</h5>
      <?php if (!empty($assigned_courses)): ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($assigned_courses as $c): ?>
            <div class="p-3 rounded-3 border bg-light">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <strong class="text-dark"><?php echo htmlspecialchars($c['subject_name']); ?></strong>
                <?php if ($c['category'] === 'MAJOR'): ?>
                  <span class="badge-major">Major</span>
                <?php else: ?>
                  <span class="badge-foundation">Foundation</span>
                <?php endif; ?>
              </div>
              <small class="text-muted d-block"><?php echo htmlspecialchars($c['class_name']); ?> (<?php echo htmlspecialchars($c['subject_code']); ?>)</small>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-muted small">No courses allocated yet.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
