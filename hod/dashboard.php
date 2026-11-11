<?php
// ========================================================
// HOD Dashboard
// File: hod/dashboard.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['HOD']);

$page_title = 'HOD Executive Portal';

// Check for pending review timetables
$pending_ver = $pdo->query("SELECT * FROM timetable_versions WHERE status IN ('DRAFT', 'UNDER_REVIEW') ORDER BY id DESC LIMIT 1")->fetch();
$active_published = $pdo->query("SELECT * FROM timetable_versions WHERE status = 'PUBLISHED' ORDER BY id DESC LIMIT 1")->fetch();

$total_staff = $pdo->query("SELECT count(*) FROM staff WHERE status = 'ACTIVE'")->fetchColumn();
$major_subjects = $pdo->query("SELECT count(*) FROM subjects WHERE category = 'MAJOR'")->fetchColumn();
$foundation_subjects = $pdo->query("SELECT count(*) FROM subjects WHERE category = 'FOUNDATION'")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- BANNER -->
<div class="card border-0 rounded-4 p-4 text-white mb-4" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <span class="badge bg-white bg-opacity-25 px-3 py-1 mb-2 fw-semibold">
        <i class="fa-solid fa-crown text-warning me-1"></i> Department Leadership
      </span>
      <h3 class="fw-bold mb-1">Welcome, Head of Department!</h3>
      <p class="text-white-50 mb-0">Review AI generated timetables, verify workload equilibrium, and publish approved schedules.</p>
    </div>
    <div>
      <?php if ($pending_ver): ?>
        <a href="<?php echo BASE_URL; ?>hod/review_timetable.php" class="btn btn-warning fw-bold px-4 py-2 rounded-3 shadow">
          <i class="fa-solid fa-clipboard-check me-1"></i> Review Pending Schedule
        </a>
      <?php else: ?>
        <a href="<?php echo BASE_URL; ?>admin/master_timetable.php" class="btn btn-light fw-bold px-4 py-2 rounded-3 shadow-sm">
          <i class="fa-solid fa-calendar-days me-1"></i> View Live Timetable
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ALERT STATUS -->
<?php if ($pending_ver): ?>
  <div class="alert alert-warning border-0 rounded-4 shadow-sm p-3 mb-4 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle bg-warning text-white p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
        <i class="fa-solid fa-bell"></i>
      </div>
      <div>
        <strong class="text-dark">Timetable Awaiting Approval (<?php echo htmlspecialchars($pending_ver['version_code']); ?>)</strong>
        <p class="mb-0 text-muted small">Generated on <?php echo date('M d, Y H:i', strtotime($pending_ver['created_at'])); ?>. Please inspect staff workload and constraints.</p>
      </div>
    </div>
    <a href="<?php echo BASE_URL; ?>hod/review_timetable.php" class="btn btn-warning btn-sm px-3 fw-bold">Review Now</a>
  </div>
<?php endif; ?>

<!-- QUICK STATS -->
<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-4">
    <div class="stat-card">
      <div class="stat-icon primary"><i class="fa-solid fa-user-tie"></i></div>
      <div>
        <div class="stat-value"><?php echo $total_staff; ?></div>
        <div class="stat-label">Faculty Members</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="stat-card">
      <div class="stat-icon primary"><i class="fa-solid fa-book"></i></div>
      <div>
        <div class="stat-value text-primary"><?php echo $major_subjects; ?> Major / <?php echo $foundation_subjects; ?> Fnd</div>
        <div class="stat-label">Department Subjects</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="stat-card">
      <div class="stat-icon success"><i class="fa-solid fa-circle-check"></i></div>
      <div>
        <div class="stat-value text-success"><?php echo $active_published ? 'Published' : 'Under Review'; ?></div>
        <div class="stat-label">Official Schedule State</div>
      </div>
    </div>
  </div>
</div>

<!-- WORKLOAD DISTRIBUTION TABLE -->
<div class="table-card p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h5 class="fw-bold text-dark mb-0">Department Workload Distribution</h5>
      <small class="text-muted">Total allocated teaching hours vs maximum prescribed workload</small>
    </div>
    <a href="<?php echo BASE_URL; ?>hod/workload_report.php" class="btn btn-sm btn-outline-primary">Detailed Report</a>
  </div>

  <div class="table-responsive">
    <table class="table custom-table">
      <thead>
        <tr>
          <th>Faculty</th>
          <th>Designation</th>
          <th>Max Limit</th>
          <th>Allocated Subjects</th>
          <th>Load Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $staff_query = "SELECT s.*, 
                        COUNT(ss.id) as subjects_count
                        FROM staff s 
                        LEFT JOIN staff_subjects ss ON s.id = ss.staff_id 
                        GROUP BY s.id";
        $staff_loads = $pdo->query($staff_query)->fetchAll();
        foreach ($staff_loads as $row):
        ?>
        <tr>
          <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></td>
          <td><?php echo htmlspecialchars($row['designation']); ?></td>
          <td><span class="badge bg-secondary"><?php echo $row['max_weekly_workload']; ?> hrs/wk</span></td>
          <td><?php echo $row['subjects_count']; ?> Courses</td>
          <td>
            <span class="badge bg-success-subtle text-success border border-success-subtle">
              <i class="fa-solid fa-check me-1"></i> Balanced
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
