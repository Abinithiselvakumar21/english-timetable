<?php
// ========================================================
// Admin Main Dashboard
// File: admin/dashboard.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['ADMIN']);

$page_title = 'Administrator Dashboard';

// Fetch Statistics
$total_staff = $pdo->query("SELECT count(*) FROM staff WHERE status = 'ACTIVE'")->fetchColumn();
$major_subjects = $pdo->query("SELECT count(*) FROM subjects WHERE category = 'MAJOR'")->fetchColumn();
$foundation_subjects = $pdo->query("SELECT count(*) FROM subjects WHERE category = 'FOUNDATION'")->fetchColumn();
$total_classes = $pdo->query("SELECT count(*) FROM classes")->fetchColumn();

// Fetch Latest Timetable Version
$latest_ver = $pdo->query("SELECT * FROM timetable_versions ORDER BY id DESC LIMIT 1")->fetch();

// Fetch Allocated Staff Count
$allocated_count = $pdo->query("SELECT count(DISTINCT staff_id) FROM staff_subjects")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- WELCOME BANNER -->
<div class="card border-0 rounded-4 p-4 text-white mb-4" style="background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <span class="badge bg-white bg-opacity-25 px-3 py-1 mb-2 fw-semibold">
        <i class="fa-solid fa-graduation-cap me-1"></i> Academic Session 2026-2027
      </span>
      <h3 class="fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h3>
      <p class="text-white-50 mb-0">English Department Staff Timetable Management &amp; AI Scheduling Control Panel.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?php echo BASE_URL; ?>admin/generate_timetable.php" class="btn btn-warning fw-bold px-4 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2">
        <i class="fa-solid fa-wand-magic-sparkles"></i>
        <span>Run AI Generator</span>
      </a>
    </div>
  </div>
</div>

<!-- KEY METRIC CARDS -->
<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon primary">
        <i class="fa-solid fa-chalkboard-user"></i>
      </div>
      <div>
        <div class="stat-value"><?php echo $total_staff; ?></div>
        <div class="stat-label">Active Professors</div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon primary">
        <i class="fa-solid fa-book-bookmark"></i>
      </div>
      <div>
        <div class="stat-value text-primary"><?php echo $major_subjects; ?></div>
        <div class="stat-label">Major Subjects</div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon success">
        <i class="fa-solid fa-book-open"></i>
      </div>
      <div>
        <div class="stat-value text-success"><?php echo $foundation_subjects; ?></div>
        <div class="stat-label">Foundation Subjects</div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon warning">
        <i class="fa-solid fa-school"></i>
      </div>
      <div>
        <div class="stat-value text-warning"><?php echo $total_classes; ?></div>
        <div class="stat-label">Classes (UG/PG)</div>
      </div>
    </div>
  </div>
</div>

<!-- TIMETABLE STATUS & QUICK ACTIONS -->
<div class="row g-4 mb-4">
  <!-- Status Widget -->
  <div class="col-lg-6">
    <div class="card border-0 rounded-4 shadow-sm p-4 h-100 bg-white">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark mb-0">Active Timetable Status</h5>
        <?php if ($latest_ver): ?>
          <span class="badge bg-<?php echo ($latest_ver['status'] == 'APPROVED' || $latest_ver['status'] == 'PUBLISHED') ? 'success' : 'warning'; ?> px-3 py-2">
            <?php echo $latest_ver['status']; ?>
          </span>
        <?php else: ?>
          <span class="badge bg-secondary px-3 py-2">NO TIMETABLE YET</span>
        <?php endif; ?>
      </div>

      <?php if ($latest_ver): ?>
        <p class="text-muted small">Current active schedule version generated for English Department classes.</p>
        <div class="bg-light p-3 rounded-3 mb-3">
          <div class="row text-center">
            <div class="col-4">
              <small class="text-muted d-block">Version Code</small>
              <strong class="text-dark"><?php echo htmlspecialchars($latest_ver['version_code']); ?></strong>
            </div>
            <div class="col-4">
              <small class="text-muted d-block">Academic Year</small>
              <strong class="text-dark"><?php echo htmlspecialchars($latest_ver['academic_year']); ?></strong>
            </div>
            <div class="col-4">
              <small class="text-muted d-block">Generated At</small>
              <strong class="text-dark"><?php echo date('M d, Y', strtotime($latest_ver['created_at'])); ?></strong>
            </div>
          </div>
        </div>
        <div class="d-flex gap-2">
          <a href="<?php echo BASE_URL; ?>admin/master_timetable.php" class="btn btn-outline-primary w-100 fw-semibold">
            <i class="fa-solid fa-eye me-1"></i> View Master Timetable
          </a>
        </div>
      <?php else: ?>
        <div class="text-center py-4 text-muted">
          <i class="fa-solid fa-calendar-xmark fa-3x mb-2 text-secondary opacity-50"></i>
          <h6>No Timetable Generated Yet</h6>
          <p class="small">Configure subjects, classes, and staff allocations to run the AI engine.</p>
          <a href="<?php echo BASE_URL; ?>admin/generate_timetable.php" class="btn btn-primary-custom btn-sm">
            Generate Now
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Quick Action Hub -->
  <div class="col-lg-6">
    <div class="card border-0 rounded-4 shadow-sm p-4 h-100 bg-white">
      <h5 class="fw-bold text-dark mb-3">Quick Navigation Hub</h5>
      <div class="row g-2">
        <div class="col-6">
          <a href="<?php echo BASE_URL; ?>admin/staff_manage.php" class="btn btn-light border w-100 py-3 text-start rounded-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-2">
              <i class="fa-solid fa-user-plus"></i>
            </div>
            <div>
              <div class="fw-bold text-dark small">Manage Staff</div>
              <small class="text-muted" style="font-size: 0.75rem;">View &amp; add faculties</small>
            </div>
          </a>
        </div>

        <div class="col-6">
          <a href="<?php echo BASE_URL; ?>admin/subject_manage.php" class="btn btn-light border w-100 py-3 text-start rounded-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-success bg-opacity-10 text-success p-2">
              <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
              <div class="fw-bold text-dark small">Subjects Category</div>
              <small class="text-muted" style="font-size: 0.75rem;">Major &amp; Foundation</small>
            </div>
          </a>
        </div>

        <div class="col-6">
          <a href="<?php echo BASE_URL; ?>admin/allocation.php" class="btn btn-light border w-100 py-3 text-start rounded-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-info bg-opacity-10 text-info p-2">
              <i class="fa-solid fa-user-check"></i>
            </div>
            <div>
              <div class="fw-bold text-dark small">Staff Allocation</div>
              <small class="text-muted" style="font-size: 0.75rem;">Assign subjects</small>
            </div>
          </a>
        </div>

        <div class="col-6">
          <a href="<?php echo BASE_URL; ?>admin/class_manage.php" class="btn btn-light border w-100 py-3 text-start rounded-3 d-flex align-items-center gap-3">
            <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-2">
              <i class="fa-solid fa-clock"></i>
            </div>
            <div>
              <div class="fw-bold text-dark small">Periods &amp; Timing</div>
              <small class="text-muted" style="font-size: 0.75rem;">Bell timings &amp; breaks</small>
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ALLOCATED STAFF OVERVIEW -->
<div class="table-card p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h5 class="fw-bold text-dark mb-0">English Department Faculty Specialization &amp; Workload</h5>
      <small class="text-muted">Staff assigned to Major literature and Foundation communication subjects</small>
    </div>
    <a href="<?php echo BASE_URL; ?>admin/staff_manage.php" class="btn btn-sm btn-outline-primary">View All Staff</a>
  </div>

  <div class="table-responsive">
    <table class="table custom-table">
      <thead>
        <tr>
          <th>Code</th>
          <th>Faculty Name</th>
          <th>Designation</th>
          <th>Specialization</th>
          <th>Max Workload</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $staff_list = $pdo->query("SELECT * FROM staff ORDER BY id ASC LIMIT 5")->fetchAll();
        foreach ($staff_list as $st):
        ?>
        <tr>
          <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($st['staff_code']); ?></span></td>
          <td class="fw-bold text-dark"><?php echo htmlspecialchars($st['name']); ?></td>
          <td><?php echo htmlspecialchars($st['designation']); ?></td>
          <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?php echo htmlspecialchars($st['specialization']); ?></span></td>
          <td><strong><?php echo $st['max_weekly_workload']; ?> hrs/wk</strong></td>
          <td><span class="badge bg-success-subtle text-success border border-success-subtle">Active</span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
