<?php
// ========================================================
// Comprehensive Department Academic & Workload Report
// File: hod/workload_report.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['HOD', 'ADMIN']);

$page_title = 'Department Academic & Workload Report';

// Get active or latest timetable version
$pub_ver = $pdo->query("SELECT * FROM timetable_versions ORDER BY (status = 'PUBLISHED') DESC, id DESC LIMIT 1")->fetch();
$ver_id = $pub_ver['id'] ?? 0;

// 1. Staff Workload Metrics
$staff_loads = [];
if ($ver_id) {
    $q = "SELECT s.*,
          COUNT(t.id) as scheduled_hours,
          SUM(CASE WHEN t.category = 'MAJOR' THEN 1 ELSE 0 END) as major_hours,
          SUM(CASE WHEN t.category = 'FOUNDATION' THEN 1 ELSE 0 END) as foundation_hours
          FROM staff s
          LEFT JOIN timetable t ON s.id = t.staff_id AND t.version_id = ?
          WHERE s.status = 'ACTIVE'
          GROUP BY s.id
          ORDER BY scheduled_hours DESC";
    $st = $pdo->prepare($q);
    $st->execute([$ver_id]);
    $staff_loads = $st->fetchAll();
}

// 2. Subject Fulfillment Metrics
$subject_audit = [];
if ($ver_id) {
    $sq = "SELECT s.id, s.subject_code, s.subject_name, s.category, s.semester, s.weekly_hours as target_hours,
           COUNT(t.id) as fulfilled_hours
           FROM subjects s
           LEFT JOIN timetable t ON s.id = t.subject_id AND t.version_id = ?
           GROUP BY s.id
           ORDER BY s.category ASC, s.semester ASC";
    $s_stmt = $pdo->prepare($sq);
    $s_stmt->execute([$ver_id]);
    $subject_audit = $s_stmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- BANNER -->
<div class="card border-0 rounded-4 p-4 text-white mb-4" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <span class="badge bg-white bg-opacity-25 px-3 py-1 mb-2 fw-semibold">
        <i class="fa-solid fa-chart-column me-1"></i> Department Academic Audit
      </span>
      <h3 class="fw-bold mb-1">Faculty Workload &amp; Subject Fulfillment Report</h3>
      <p class="text-white-50 mb-0">Quantitative verification of workload distribution across English Department faculty.</p>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-warning fw-bold px-4 py-2 rounded-3 shadow" onclick="window.print()">
        <i class="fa-solid fa-print me-1"></i> Print / Export Report
      </button>
    </div>
  </div>
</div>

<!-- ============================================== -->
<!-- 1. FACULTY WORKLOAD BREAKDOWN -->
<!-- ============================================== -->
<div class="table-card p-4 mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h5 class="fw-bold text-dark mb-0">1. Faculty Teaching Load Analysis</h5>
      <small class="text-muted">Breakdown of Literature Major vs Foundation Communication teaching hours</small>
    </div>
    <span class="badge bg-light text-dark border fw-bold px-3 py-2">
      Timetable: <?php echo htmlspecialchars($pub_ver['version_code'] ?? 'None'); ?> (<?php echo $pub_ver['status'] ?? 'Draft'; ?>)
    </span>
  </div>

  <div class="table-responsive">
    <table class="table custom-table">
      <thead>
        <tr>
          <th>Faculty Name &amp; Code</th>
          <th>Designation &amp; Specialization</th>
          <th>Major Hours</th>
          <th>Foundation Hours</th>
          <th>Total Assigned</th>
          <th>Max Workload</th>
          <th>Load Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($staff_loads as $sl): 
          $target = $sl['max_weekly_workload'];
          $actual = $sl['scheduled_hours'];
          $diff = $target - $actual;
        ?>
        <tr>
          <td>
            <div class="fw-bold text-dark"><?php echo htmlspecialchars($sl['name']); ?></div>
            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($sl['staff_code']); ?></span>
          </td>
          <td>
            <div class="text-secondary small fw-semibold"><?php echo htmlspecialchars($sl['designation']); ?></div>
            <small class="text-muted"><?php echo htmlspecialchars($sl['specialization']); ?></small>
          </td>
          <td>
            <span class="badge-major"><?php echo $sl['major_hours']; ?> hrs</span>
          </td>
          <td>
            <span class="badge-foundation"><?php echo $sl['foundation_hours']; ?> hrs</span>
          </td>
          <td>
            <strong class="fs-6 text-dark"><?php echo $actual; ?></strong> <span class="text-muted small">periods</span>
          </td>
          <td>
            <span class="badge bg-secondary"><?php echo $target; ?> hrs/wk</span>
          </td>
          <td>
            <?php if ($actual <= $target): ?>
              <span class="badge bg-success-subtle text-success border border-success-subtle">
                <i class="fa-solid fa-circle-check me-1"></i> Normal (<?php echo $diff; ?>h Free)
              </span>
            <?php else: ?>
              <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> Overloaded (+<?php echo abs($diff); ?>h)
              </span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ============================================== -->
<!-- 2. SUBJECT AUDIT REPORT -->
<!-- ============================================== -->
<div class="table-card p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h5 class="fw-bold text-dark mb-0">2. Subject Curriculum Hours Fulfillment</h5>
      <small class="text-muted">Target required periods vs successfully scheduled periods in master timetable</small>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table custom-table">
      <thead>
        <tr>
          <th>Subject Code &amp; Title</th>
          <th>Category</th>
          <th>Semester</th>
          <th>Prescribed Target Hours</th>
          <th>Scheduled Hours</th>
          <th>Compliance Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($subject_audit as $sa): 
          $fulfilled = ($sa['fulfilled_hours'] >= $sa['target_hours']);
        ?>
        <tr>
          <td>
            <span class="badge bg-light text-dark border me-1"><?php echo htmlspecialchars($sa['subject_code']); ?></span>
            <strong class="text-dark"><?php echo htmlspecialchars($sa['subject_name']); ?></strong>
          </td>
          <td>
            <?php if ($sa['category'] === 'MAJOR'): ?>
              <span class="badge-major">MAJOR</span>
            <?php else: ?>
              <span class="badge-foundation">FOUNDATION</span>
            <?php endif; ?>
          </td>
          <td><span class="badge bg-secondary-subtle text-dark border">Sem <?php echo $sa['semester']; ?></span></td>
          <td><strong><?php echo $sa['target_hours']; ?></strong> hrs/wk</td>
          <td><strong class="text-primary"><?php echo $sa['fulfilled_hours']; ?></strong> hrs/wk</td>
          <td>
            <?php if ($fulfilled): ?>
              <span class="badge bg-success-subtle text-success border border-success-subtle">
                <i class="fa-solid fa-check-double me-1"></i> 100% Fulfilled
              </span>
            <?php else: ?>
              <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                Pending Allocation
              </span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
