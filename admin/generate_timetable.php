<?php
// ========================================================
// AI Timetable Generation Control Center
// File: admin/generate_timetable.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['ADMIN']);

$page_title = 'AI Timetable Generator';

// Pre-flight check metrics
$total_classes = $pdo->query("SELECT count(*) FROM classes")->fetchColumn();
$major_count = $pdo->query("SELECT count(*) FROM subjects WHERE category = 'MAJOR'")->fetchColumn();
$foundation_count = $pdo->query("SELECT count(*) FROM subjects WHERE category = 'FOUNDATION'")->fetchColumn();
$staff_count = $pdo->query("SELECT count(*) FROM staff WHERE status = 'ACTIVE'")->fetchColumn();
$alloc_count = $pdo->query("SELECT count(*) FROM staff_subjects")->fetchColumn();
$period_count = $pdo->query("SELECT count(*) FROM periods WHERE is_break = 0")->fetchColumn();
$day_count = $pdo->query("SELECT count(*) FROM working_days WHERE is_working = 1")->fetchColumn();

// Fetch previous generated versions
$versions = $pdo->query("SELECT tv.*, u.username as generator_name,
                         (SELECT COUNT(*) FROM timetable WHERE version_id = tv.id) as total_scheduled_slots
                         FROM timetable_versions tv
                         LEFT JOIN users u ON tv.generated_by = u.id
                         ORDER BY tv.id DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- BANNER -->
<div class="card border-0 rounded-4 p-4 text-white mb-4" style="background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <span class="badge bg-white bg-opacity-25 px-3 py-1 mb-2 fw-semibold">
        <i class="fa-solid fa-microchip me-1"></i> Constraint Satisfaction Engine (Node.js AI)
      </span>
      <h3 class="fw-bold mb-1">AI Timetable Generation Engine</h3>
      <p class="text-white-50 mb-0">Automated clash-free scheduling integrating Major Literature courses with Foundation Language courses.</p>
    </div>
    <div>
      <a href="<?php echo BASE_URL; ?>admin/master_timetable.php" class="btn btn-light fw-bold px-4 py-2 rounded-3 shadow-sm">
        <i class="fa-solid fa-calendar-days me-1"></i> View Master Timetable
      </a>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <!-- PRE-FLIGHT VALIDATION CHECKLIST -->
  <div class="col-lg-5">
    <div class="table-card p-4 h-100">
      <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-check text-primary me-2"></i> Pre-Generation Checklist</h5>
      
      <div class="d-flex flex-column gap-3 mb-4">
        <!-- Classes -->
        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid <?php echo ($total_classes > 0) ? 'fa-circle-check text-success' : 'fa-circle-xmark text-danger'; ?>"></i>
            <span class="small fw-semibold">Department Classes</span>
          </div>
          <span class="badge bg-white text-dark border"><?php echo $total_classes; ?> Classes</span>
        </div>

        <!-- Major Subjects -->
        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid <?php echo ($major_count > 0) ? 'fa-circle-check text-success' : 'fa-circle-xmark text-danger'; ?>"></i>
            <span class="small fw-semibold">Major Subjects (Literature)</span>
          </div>
          <span class="badge-major"><?php echo $major_count; ?> Courses</span>
        </div>

        <!-- Foundation Subjects -->
        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid <?php echo ($foundation_count > 0) ? 'fa-circle-check text-success' : 'fa-circle-xmark text-danger'; ?>"></i>
            <span class="small fw-semibold">Foundation Subjects</span>
          </div>
          <span class="badge-foundation"><?php echo $foundation_count; ?> Courses</span>
        </div>

        <!-- Faculty Allocations -->
        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid <?php echo ($alloc_count > 0) ? 'fa-circle-check text-success' : 'fa-circle-xmark text-danger'; ?>"></i>
            <span class="small fw-semibold">Staff-Subject Allocations</span>
          </div>
          <span class="badge bg-white text-dark border"><?php echo $alloc_count; ?> Links</span>
        </div>

        <!-- Working Days & Periods -->
        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid <?php echo ($period_count > 0 && $day_count > 0) ? 'fa-circle-check text-success' : 'fa-circle-xmark text-danger'; ?>"></i>
            <span class="small fw-semibold">Days &amp; Teaching Periods</span>
          </div>
          <span class="badge bg-white text-dark border"><?php echo $day_count; ?> Days &bull; <?php echo $period_count; ?> Periods</span>
        </div>
      </div>

      <!-- TRIGGER GENERATION BUTTON -->
      <button class="btn btn-warning w-100 py-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2 text-dark" id="btnRunAI" onclick="triggerAIGeneration()">
        <i class="fa-solid fa-wand-magic-sparkles fa-lg"></i>
        <span>Trigger AI Timetable Engine</span>
      </button>
    </div>
  </div>

  <!-- REAL-TIME AI ENGINE LOGS -->
  <div class="col-lg-7">
    <div class="table-card p-4 h-100 bg-dark text-white rounded-4 shadow">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0 text-white-50"><i class="fa-solid fa-terminal text-success me-2"></i> AI Solver Console</h6>
        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2 py-1 small">
          <i class="fa-solid fa-circle text-success" style="font-size: 0.5rem;"></i> Port 5000 Active
        </span>
      </div>

      <div class="bg-black bg-opacity-50 p-3 rounded-3 font-monospace small" id="aiLogsConsole" style="height: 260px; overflow-y: auto; line-height: 1.6; color: #a7f3d0;">
        [System] English Department Timetable Microservice ready.<br>
        [System] CSP Backtracking Solver initialized.<br>
        [Status] Awaiting "Trigger AI Timetable Engine" command...<br>
      </div>

      <div class="progress mt-3 d-none" id="aiProgressBarContainer" style="height: 8px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" id="aiProgressBar" style="width: 100%;"></div>
      </div>
    </div>
  </div>
</div>

<!-- GENERATED TIMETABLE VERSIONS HISTORY -->
<div class="table-card p-4">
  <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Timetable Version History</h5>
  
  <div class="table-responsive">
    <table class="table custom-table">
      <thead>
        <tr>
          <th>Version Code</th>
          <th>Session</th>
          <th>Generated By</th>
          <th>Total Slots</th>
          <th>Status</th>
          <th>Approval / Remarks</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($versions)): ?>
          <?php foreach ($versions as $v): ?>
          <tr>
            <td>
              <strong class="text-dark"><?php echo htmlspecialchars($v['version_code']); ?></strong>
              <div class="text-muted" style="font-size: 0.72rem;"><?php echo date('M d, Y h:i A', strtotime($v['created_at'])); ?></div>
            </td>
            <td><?php echo htmlspecialchars($v['academic_year']); ?></td>
            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($v['generator_name'] ?? 'Admin'); ?></span></td>
            <td><strong><?php echo $v['total_scheduled_slots']; ?></strong> periods</td>
            <td>
              <?php
              $badgeClass = match($v['status']) {
                'PUBLISHED' => 'bg-success',
                'APPROVED'  => 'bg-primary',
                'DRAFT'     => 'bg-secondary',
                'REJECTED'  => 'bg-danger',
                default     => 'bg-warning text-dark'
              };
              ?>
              <span class="badge <?php echo $badgeClass; ?> px-2 py-1"><?php echo $v['status']; ?></span>
            </td>
            <td>
              <small class="text-muted"><?php echo htmlspecialchars($v['hod_remarks'] ?? 'Awaiting HOD review'); ?></small>
            </td>
            <td class="text-end">
              <a href="<?php echo BASE_URL; ?>admin/master_timetable.php?version_id=<?php echo $v['id']; ?>" class="btn btn-sm btn-outline-primary fw-semibold">
                <i class="fa-solid fa-eye me-1"></i> View Master
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center py-4 text-muted">No timetable versions generated yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  async function triggerAIGeneration() {
    const btn = document.getElementById('btnRunAI');
    const logs = document.getElementById('aiLogsConsole');
    const pbar = document.getElementById('aiProgressBarContainer');

    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Running Backtracking Algorithm...`;
    pbar.classList.remove('d-none');

    logs.innerHTML += `<br>[AI Engine] Collecting department classes, subjects, and professor constraints...`;
    logs.scrollTop = logs.scrollHeight;

    try {
      const res = await fetch('<?php echo BASE_URL; ?>api/timetable_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=generate'
      });

      const data = await res.json();
      pbar.classList.add('d-none');
      btn.disabled = false;
      btn.innerHTML = `<i class="fa-solid fa-wand-magic-sparkles fa-lg me-2"></i> Trigger AI Timetable Engine`;

      if (data.success) {
        logs.innerHTML += `<br><span style="color: #6ee7b7;">[SUCCESS] ${data.message}</span>`;
        logs.innerHTML += `<br><span style="color: #6ee7b7;">[Saved] Version Created: ${data.version_code} (${data.total_slots} slots allocated without clashes).</span>`;
        logs.scrollTop = logs.scrollHeight;
        alert(data.message);
        window.location.reload();
      } else {
        logs.innerHTML += `<br><span style="color: #fca5a5;">[CONFLICT ERROR] ${data.message}</span>`;
        logs.scrollTop = logs.scrollHeight;
        alert('AI Generation Conflict: ' + data.message);
      }

    } catch(err) {
      pbar.classList.add('d-none');
      btn.disabled = false;
      btn.innerHTML = `<i class="fa-solid fa-wand-magic-sparkles fa-lg me-2"></i> Trigger AI Timetable Engine`;
      logs.innerHTML += `<br><span style="color: #fca5a5;">[FATAL ERROR] Communication failed with backend. Ensure Node.js and Apache are active.</span>`;
      alert('Network communication error.');
    }
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
