<?php
// ========================================================
// Staff Subject Allocation Management Interface
// File: admin/allocation.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['ADMIN']);

$page_title = 'Faculty Subject Allocation';

// Fetch classes
$classes = $pdo->query("SELECT * FROM classes ORDER BY year ASC, semester ASC")->fetchAll();

// Fetch subjects
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY category ASC, semester ASC, subject_name ASC")->fetchAll();

// Fetch staff
$staff_list = $pdo->query("SELECT s.*, 
                           COALESCE((SELECT SUM(sub.weekly_hours) FROM staff_subjects ss JOIN subjects sub ON ss.subject_id = sub.id WHERE ss.staff_id = s.id), 0) as current_hours
                           FROM staff s WHERE s.status = 'ACTIVE' ORDER BY s.name ASC")->fetchAll();

// Fetch existing allocations
$alloc_query = "SELECT ss.id as alloc_id, ss.created_at,
                s.name as staff_name, s.staff_code, s.specialization,
                sub.id as subject_id, sub.subject_code, sub.subject_name, sub.category, sub.weekly_hours,
                c.id as class_id, c.class_name, c.room_no
                FROM staff_subjects ss
                JOIN staff s ON ss.staff_id = s.id
                JOIN subjects sub ON ss.subject_id = sub.id
                JOIN classes c ON ss.class_id = c.id
                ORDER BY c.year ASC, sub.category ASC, sub.subject_name ASC";
$allocations = $pdo->query($alloc_query)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ACTION BAR -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h4 class="fw-bold text-dark mb-1">Faculty Subject Allocation (Workload Mapping)</h4>
    <p class="text-muted small mb-0">Assign qualified English professors to Major and Foundation courses with automated workload limit checks.</p>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-primary-custom d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#allocModal">
      <i class="fa-solid fa-plus"></i>
      <span>New Subject Allocation</span>
    </button>
  </div>
</div>

<!-- WORKLOAD CAPACITIES ROW -->
<div class="card border-0 rounded-4 shadow-sm p-4 mb-4 bg-white">
  <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-gauge-high text-primary me-2"></i> Faculty Workload Utilization Overview</h6>
  <div class="row g-3">
    <?php foreach ($staff_list as $st): 
      $pct = ($st['max_weekly_workload'] > 0) ? round(($st['current_hours'] / $st['max_weekly_workload']) * 100) : 0;
      $bar_color = ($pct >= 100) ? 'bg-danger' : (($pct >= 75) ? 'bg-warning' : 'bg-primary');
    ?>
    <div class="col-md-4 col-lg-3">
      <div class="p-3 border rounded-3 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <strong class="text-dark small text-truncate" style="max-width: 140px;"><?php echo htmlspecialchars($st['name']); ?></strong>
          <span class="badge bg-white text-dark border"><?php echo $st['current_hours']; ?> / <?php echo $st['max_weekly_workload']; ?>h</span>
        </div>
        <div class="progress" style="height: 6px;">
          <div class="progress-bar <?php echo $bar_color; ?>" style="width: <?php echo min(100, $pct); ?>%;"></div>
        </div>
        <small class="text-muted mt-1 d-block" style="font-size: 0.72rem;"><?php echo htmlspecialchars($st['specialization']); ?></small>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ALLOCATIONS TABLE -->
<div class="table-card p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h5 class="fw-bold text-dark mb-0">Active Course Allocations</h5>
      <small class="text-muted">Total allocated: <?php echo count($allocations); ?> course-teacher links</small>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table custom-table">
      <thead>
        <tr>
          <th>Class / Section</th>
          <th>Subject Title &amp; Code</th>
          <th>Category</th>
          <th>Assigned Faculty</th>
          <th>Weekly Hours</th>
          <th class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($allocations)): ?>
          <?php foreach ($allocations as $a): ?>
          <tr>
            <td>
              <strong class="text-dark"><?php echo htmlspecialchars($a['class_name']); ?></strong>
              <div class="text-muted small"><?php echo htmlspecialchars($a['room_no']); ?></div>
            </td>
            <td>
              <div class="fw-bold text-dark"><?php echo htmlspecialchars($a['subject_name']); ?></div>
              <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($a['subject_code']); ?></span>
            </td>
            <td>
              <?php if ($a['category'] === 'MAJOR'): ?>
                <span class="badge-major"><i class="fa-solid fa-star"></i> MAJOR</span>
              <?php else: ?>
                <span class="badge-foundation"><i class="fa-solid fa-seedling"></i> FOUNDATION</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-bold text-primary"><?php echo htmlspecialchars($a['staff_name']); ?></div>
              <small class="text-muted"><?php echo htmlspecialchars($a['specialization']); ?></small>
            </td>
            <td>
              <strong class="text-dark"><?php echo $a['weekly_hours']; ?></strong> <span class="text-muted small">periods / wk</span>
            </td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-danger" onclick="deleteAlloc(<?php echo $a['alloc_id']; ?>, '<?php echo addslashes($a['subject_name']); ?>')">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="text-center py-4 text-muted">No allocations found. Click "New Subject Allocation" to assign professors.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ============================================== -->
<!-- MODAL: NEW ALLOCATION -->
<!-- ============================================== -->
<div class="modal fade" id="allocModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-user-check text-primary me-2"></i> Assign Faculty to Subject</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="allocForm" onsubmit="submitAllocation(event)">
        <div class="modal-body p-4">
          <input type="hidden" name="action" value="allocate">
          <div class="row g-3">
            <!-- Select Class -->
            <div class="col-md-6">
              <label class="form-label fw-bold small">Target Class *</label>
              <select name="class_id" class="form-select" required>
                <option value="">-- Choose Class --</option>
                <?php foreach ($classes as $c): ?>
                  <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name']); ?> (Sem <?php echo $c['semester']; ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Select Subject -->
            <div class="col-md-6">
              <label class="form-label fw-bold small">Subject (Major / Foundation) *</label>
              <select name="subject_id" class="form-select" required>
                <option value="">-- Choose Subject --</option>
                <optgroup label="MAJOR SUBJECTS (Literature)">
                  <?php foreach ($subjects as $s): if ($s['category'] !== 'MAJOR') continue; ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['subject_name']); ?> (<?php echo $s['weekly_hours']; ?> hrs/wk)</option>
                  <?php endforeach; ?>
                </optgroup>
                <optgroup label="FOUNDATION SUBJECTS (Communication)">
                  <?php foreach ($subjects as $s): if ($s['category'] !== 'FOUNDATION') continue; ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['subject_name']); ?> (<?php echo $s['weekly_hours']; ?> hrs/wk)</option>
                  <?php endforeach; ?>
                </optgroup>
              </select>
            </div>

            <!-- Select Staff -->
            <div class="col-12">
              <label class="form-label fw-bold small">Assign Faculty Member *</label>
              <select name="staff_id" class="form-select" required>
                <option value="">-- Choose Professor --</option>
                <?php foreach ($staff_list as $st): ?>
                  <option value="<?php echo $st['id']; ?>">
                    <?php echo htmlspecialchars($st['name']); ?> &bull; Specialization: <?php echo htmlspecialchars($st['specialization']); ?> (Load: <?php echo $st['current_hours']; ?>/<?php echo $st['max_weekly_workload']; ?>h)
                  </option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted" style="font-size: 0.75rem;">System will automatically validate professor's weekly workload ceiling before accepting.</small>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-custom">Confirm Allocation</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  async function submitAllocation(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('allocForm'));
    const res = await fetch('<?php echo BASE_URL; ?>api/allocation_api.php', { method: 'POST', body: formData });
    const data = await res.json();
    alert(data.message);
    if (data.success) window.location.reload();
  }

  async function deleteAlloc(id, name) {
    if (!confirm(`Remove allocation for "${name}"?`)) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    const res = await fetch('<?php echo BASE_URL; ?>api/allocation_api.php', { method: 'POST', body: formData });
    const data = await res.json();
    alert(data.message);
    if (data.success) window.location.reload();
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
