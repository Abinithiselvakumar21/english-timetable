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
<div class="card border-0 rounded-4 p-4 text-white mb-4" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <span class="badge bg-warning text-dark px-3 py-1 mb-2 fw-bold">
        <i class="fa-solid fa-microchip me-1"></i> Constraint Satisfaction Engine (Live on Render Cloud)
      </span>
      <h3 class="fw-bold mb-1">AI Timetable Generation Control Center</h3>
      <p class="text-white-50 mb-0">Automated clash-free scheduling integrating Major Literature courses with Foundation Language courses.</p>
    </div>
    <div>
      <a href="<?php echo BASE_URL; ?>admin/master_timetable.php" class="btn btn-primary fw-bold px-4 py-2 rounded-3 shadow">
        <i class="fa-solid fa-calendar-days me-1"></i> View Master Timetable
      </a>
    </div>
  </div>
</div>

<!-- MAIN GENERATOR CONTROLS -->
<div class="row g-4 mb-4">
  
  <!-- LEFT: CHECKLIST & PROMINENT GENERATE BUTTON -->
  <div class="col-lg-6">
    <div class="card border-0 rounded-4 shadow-sm p-4 h-100 bg-white">
      <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-check text-primary me-2"></i> System Readiness Checklist</h5>
      
      <div class="d-flex flex-column gap-2 mb-4">
        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
          <span class="small fw-semibold"><i class="fa-solid fa-circle-check text-success me-2"></i>Department Classes</span>
          <span class="badge bg-primary text-white"><?php echo $total_classes; ?> Classes</span>
        </div>

        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
          <span class="small fw-semibold"><i class="fa-solid fa-circle-check text-success me-2"></i>Major Subjects (Literature)</span>
          <span class="badge bg-primary text-white"><?php echo $major_count; ?> Courses</span>
        </div>

        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
          <span class="small fw-semibold"><i class="fa-solid fa-circle-check text-success me-2"></i>Foundation Subjects (Skills)</span>
          <span class="badge bg-success text-white"><?php echo $foundation_count; ?> Courses</span>
        </div>

        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
          <span class="small fw-semibold"><i class="fa-solid fa-circle-check text-success me-2"></i>Staff Allocations</span>
          <span class="badge bg-dark text-white"><?php echo $alloc_count; ?> Allocated</span>
        </div>

        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
          <span class="small fw-semibold"><i class="fa-solid fa-circle-check text-success me-2"></i>Teaching Periods &amp; Days</span>
          <span class="badge bg-secondary text-white"><?php echo $day_count; ?> Days &bull; <?php echo $period_count; ?> Periods</span>
        </div>
      </div>

      <!-- BIG PROMINENT ACTION BUTTON -->
      <div class="p-3 rounded-4 bg-warning bg-opacity-10 border border-warning">
        <label class="form-label fw-bold text-dark d-block mb-2">Execute AI Generation</label>
        <button type="button" class="btn btn-warning w-100 py-3 fw-bold fs-5 shadow d-flex align-items-center justify-content-center gap-2 text-dark" id="btnRunAI" onclick="triggerAIGeneration()">
          <i class="fa-solid fa-wand-magic-sparkles fa-lg"></i>
          <span>Trigger AI Timetable Engine</span>
        </button>
        <small class="text-muted d-block text-center mt-2" style="font-size: 0.78rem;">
          Connects via REST API to Render Cloud Microservice &amp; writes schedule to MySQL.
        </small>
      </div>

    </div>
  </div>

  <!-- RIGHT: REAL-TIME CONSOLE LOGS -->
  <div class="col-lg-6">
    <div class="card border-0 rounded-4 shadow-sm p-4 h-100 bg-dark text-white">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0 text-white-50"><i class="fa-solid fa-terminal text-success me-2"></i> AI Solver Execution Terminal</h6>
        <span class="badge bg-success text-white px-2 py-1 small">
          <i class="fa-solid fa-satellite-dish me-1"></i> Cloud Active
        </span>
      </div>

      <div class="bg-black p-3 rounded-3 font-monospace small" id="aiLogsConsole" style="height: 280px; overflow-y: auto; line-height: 1.6; color: #34d399;">
        [System] English Department Timetable Microservice ready.<br>
        [Cloud Node] Connected to: <?php echo NODE_AI_URL; ?><br>
        [Status] Ready to generate. Click the "Trigger AI Timetable Engine" button.<br>
      </div>

      <div class="progress mt-3 d-none" id="aiProgressBarContainer" style="height: 8px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" id="aiProgressBar" style="width: 100%;"></div>
      </div>
    </div>
  </div>

</div>

<!-- TIMETABLE VERSIONS HISTORY TABLE -->
<div class="table-card p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Generated Timetable History</h5>
    <span class="badge bg-light text-dark border"><?php echo count($versions); ?> Versions</span>
  </div>

  <div class="table-responsive">
    <table class="table custom-table">
      <thead>
        <tr>
          <th>Version Code</th>
          <th>Session</th>
          <th>Generated By</th>
          <th>Total Slots</th>
          <th>Status</th>
          <th>HOD Remarks</th>
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
            <td><strong class="text-primary"><?php echo $v['total_scheduled_slots']; ?></strong> slots</td>
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
              <small class="text-muted"><?php echo htmlspecialchars($v['hod_remarks'] ?? 'Verified and Approved'); ?></small>
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
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Contacting Render Cloud AI Engine...`;
    pbar.classList.remove('d-none');

    logs.innerHTML += `<br>[AI Engine] Packing courses, teachers, and workload constraints...`;
    logs.innerHTML += `<br>[Render Cloud] Requesting solve from https://english-timetable.onrender.com...`;
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
      logs.innerHTML += `<br><span style="color: #fca5a5;">[FATAL ERROR] Communication failed with backend.</span>`;
      alert('Network communication error: ' + err.message);
    }
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
