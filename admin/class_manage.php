<?php
// ========================================================
// Classes, Periods & Timing Settings Interface
// File: admin/class_manage.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['ADMIN']);

$page_title = 'Classes & College Timings';

// Fetch classes
$classes = $pdo->query("SELECT * FROM classes ORDER BY year ASC, semester ASC, section ASC")->fetchAll();

// Fetch periods
$periods = $pdo->query("SELECT * FROM periods ORDER BY period_number ASC")->fetchAll();

// Fetch working days
$days = $pdo->query("SELECT * FROM working_days ORDER BY day_order ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ACTION BAR -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h4 class="fw-bold text-dark mb-1">Academic Classes &amp; Schedule Timings</h4>
    <p class="text-muted small mb-0">Configure department classes, semester sections, teaching hours, and break schedules.</p>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-primary-custom d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addClassModal">
      <i class="fa-solid fa-plus"></i>
      <span>Add New Class</span>
    </button>
    <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addPeriodModal">
      <i class="fa-solid fa-clock"></i>
      <span>Add Period / Break</span>
    </button>
  </div>
</div>

<div class="row g-4">
  <!-- ============================================== -->
  <!-- 1. CLASSES MANAGEMENT (LEFT COLUMN) -->
  <!-- ============================================== -->
  <div class="col-lg-6">
    <div class="table-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h5 class="fw-bold text-dark mb-0">English Department Classes</h5>
          <small class="text-muted">Degree sections assigned for timetable scheduling</small>
        </div>
        <span class="badge bg-primary px-3 py-2"><?php echo count($classes); ?> Classes</span>
      </div>

      <div class="table-responsive">
        <table class="table custom-table">
          <thead>
            <tr>
              <th>Class / Degree</th>
              <th>Semester</th>
              <th>Sec</th>
              <th>Room</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($classes)): ?>
              <?php foreach ($classes as $c): ?>
              <tr>
                <td>
                  <strong class="text-dark"><?php echo htmlspecialchars($c['class_name']); ?></strong>
                  <div class="text-muted" style="font-size: 0.75rem;">Year <?php echo $c['year']; ?> &bull; <?php echo $c['academic_year']; ?></div>
                </td>
                <td><span class="badge bg-secondary-subtle text-dark border">Sem <?php echo $c['semester']; ?></span></td>
                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($c['section']); ?></span></td>
                <td><span class="badge bg-light text-primary border border-primary-subtle"><?php echo htmlspecialchars($c['room_no']); ?></span></td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-danger" onclick="deleteClass(<?php echo $c['id']; ?>, '<?php echo addslashes($c['class_name']); ?>')">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="5" class="text-center text-muted py-3">No classes registered.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 2. PERIOD TIMINGS & BREAKS (RIGHT COLUMN) -->
  <!-- ============================================== -->
  <div class="col-lg-6">
    <div class="table-card p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h5 class="fw-bold text-dark mb-0">Daily Period Timings &amp; Breaks</h5>
          <small class="text-muted">Standard daily bell timing schedule</small>
        </div>
        <span class="badge bg-secondary px-3 py-2"><?php echo count($periods); ?> Slots</span>
      </div>

      <div class="table-responsive mb-4">
        <table class="table custom-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Period Label</th>
              <th>Start - End Time</th>
              <th>Type</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($periods)): ?>
              <?php foreach ($periods as $p): ?>
              <tr class="<?php echo $p['is_break'] ? 'table-warning bg-opacity-25' : ''; ?>">
                <td><strong><?php echo $p['period_number']; ?></strong></td>
                <td>
                  <strong class="text-dark"><?php echo htmlspecialchars($p['period_name']); ?></strong>
                </td>
                <td>
                  <i class="fa-regular fa-clock text-muted me-1"></i>
                  <?php echo date('h:i A', strtotime($p['start_time'])); ?> - <?php echo date('h:i A', strtotime($p['end_time'])); ?>
                </td>
                <td>
                  <?php if ($p['is_break']): ?>
                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-mug-hot me-1"></i> Break</span>
                  <?php else: ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Teaching</span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-danger" onclick="deletePeriod(<?php echo $p['id']; ?>, '<?php echo addslashes($p['period_name']); ?>')">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="5" class="text-center text-muted py-3">No periods configured.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- WORKING DAYS TOGGLE -->
      <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-calendar-check text-primary me-2"></i> Department Working Days</h6>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($days as $d): ?>
          <div class="form-check form-switch border rounded-3 px-3 py-2 bg-light d-flex align-items-center gap-2">
            <input class="form-check-input ms-0" type="checkbox" id="day_<?php echo $d['id']; ?>" <?php echo $d['is_working'] ? 'checked' : ''; ?> onchange="toggleDay(<?php echo $d['id']; ?>, this.checked)">
            <label class="form-check-label fw-bold small text-dark" for="day_<?php echo $d['id']; ?>"><?php echo $d['day_name']; ?></label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD CLASS -->
<!-- ============================================== -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-school text-primary me-2"></i> Add Class / Section</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="addClassForm" onsubmit="submitAddClass(event)">
        <div class="modal-body p-4">
          <input type="hidden" name="action" value="create_class">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-bold small">Class Name *</label>
              <input type="text" name="class_name" class="form-control" placeholder="e.g. BA English I Year" required>
            </div>
            <div class="col-6">
              <label class="form-label fw-bold small">Academic Year</label>
              <input type="text" name="academic_year" class="form-control" value="2026-2027" required>
            </div>
            <div class="col-6">
              <label class="form-label fw-bold small">Year (1, 2 or 3)</label>
              <input type="number" name="year" class="form-control" value="1" min="1" max="5" required>
            </div>
            <div class="col-6">
              <label class="form-label fw-bold small">Semester (1 to 6)</label>
              <input type="number" name="semester" class="form-control" value="1" min="1" max="10" required>
            </div>
            <div class="col-6">
              <label class="form-label fw-bold small">Section</label>
              <input type="text" name="section" class="form-control" value="A" required>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold small">Assigned Classroom No</label>
              <input type="text" name="room_no" class="form-control" value="Room-101" required>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-custom">Add Class</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============================================== -->
<!-- MODAL: ADD PERIOD -->
<!-- ============================================== -->
<div class="modal fade" id="addPeriodModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-clock text-primary me-2"></i> Add Daily Period Slot</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="addPeriodForm" onsubmit="submitAddPeriod(event)">
        <div class="modal-body p-4">
          <input type="hidden" name="action" value="create_period">
          <div class="row g-3">
            <div class="col-4">
              <label class="form-label fw-bold small">Period # *</label>
              <input type="number" name="period_number" class="form-control" value="<?php echo count($periods) + 1; ?>" required>
            </div>
            <div class="col-8">
              <label class="form-label fw-bold small">Label / Period Name *</label>
              <input type="text" name="period_name" class="form-control" placeholder="e.g. Period 6 or Tea Break" required>
            </div>
            <div class="col-6">
              <label class="form-label fw-bold small">Start Time *</label>
              <input type="time" name="start_time" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label fw-bold small">End Time *</label>
              <input type="time" name="end_time" class="form-control" required>
            </div>
            <div class="col-12">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="is_break" id="checkBreak" value="1">
                <label class="form-check-label fw-semibold text-secondary" for="checkBreak">
                  This is a Break slot (No teaching classes will be scheduled)
                </label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-custom">Save Period</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  async function submitAddClass(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('addClassForm'));
    const res = await fetch('<?php echo BASE_URL; ?>api/class_api.php', { method: 'POST', body: formData });
    const data = await res.json();
    alert(data.message);
    if (data.success) window.location.reload();
  }

  async function deleteClass(id, name) {
    if (!confirm(`Delete class "${name}"?`)) return;
    const formData = new FormData();
    formData.append('action', 'delete_class');
    formData.append('id', id);
    const res = await fetch('<?php echo BASE_URL; ?>api/class_api.php', { method: 'POST', body: formData });
    const data = await res.json();
    alert(data.message);
    if (data.success) window.location.reload();
  }

  async function submitAddPeriod(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('addPeriodForm'));
    const res = await fetch('<?php echo BASE_URL; ?>api/class_api.php', { method: 'POST', body: formData });
    const data = await res.json();
    alert(data.message);
    if (data.success) window.location.reload();
  }

  async function deletePeriod(id, name) {
    if (!confirm(`Remove slot "${name}"?`)) return;
    const formData = new FormData();
    formData.append('action', 'delete_period');
    formData.append('id', id);
    const res = await fetch('<?php echo BASE_URL; ?>api/class_api.php', { method: 'POST', body: formData });
    const data = await res.json();
    alert(data.message);
    if (data.success) window.location.reload();
  }

  async function toggleDay(id, isWorking) {
    const formData = new FormData();
    formData.append('action', 'toggle_day');
    formData.append('id', id);
    formData.append('is_working', isWorking ? 1 : 0);
    const res = await fetch('<?php echo BASE_URL; ?>api/class_api.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (!data.success) alert(data.message);
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
