<?php
// ========================================================
// Integrated Master Timetable (Major & Foundation Blended)
// File: admin/master_timetable.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_auth(); // Available for Admin, HOD, and Staff

$page_title = 'Integrated Master Timetable';

// Determine version to view
$selected_ver_id = intval($_GET['version_id'] ?? 0);
if (!$selected_ver_id) {
    // Pick the latest version (prefer published or draft)
    $ver_stmt = $pdo->query("SELECT id FROM timetable_versions ORDER BY (status = 'PUBLISHED') DESC, id DESC LIMIT 1");
    $selected_ver_id = $ver_stmt->fetchColumn() ?: 0;
}

// Fetch version details
$current_ver = null;
if ($selected_ver_id) {
    $v_stmt = $pdo->prepare("SELECT * FROM timetable_versions WHERE id = ?");
    $v_stmt->execute([$selected_ver_id]);
    $current_ver = $v_stmt->fetch();
}

// Fetch all versions for dropdown selector
$all_versions = $pdo->query("SELECT id, version_code, status, created_at FROM timetable_versions ORDER BY id DESC")->fetchAll();

// Fetch department classes
$classes = $pdo->query("SELECT * FROM classes ORDER BY year ASC, semester ASC")->fetchAll();

// Fetch all teaching staff
$staff_list = $pdo->query("SELECT id, name, staff_code, specialization FROM staff WHERE status = 'ACTIVE' ORDER BY name ASC")->fetchAll();

// Fetch working days
$working_days = $pdo->query("SELECT * FROM working_days WHERE is_working = 1 ORDER BY day_order ASC")->fetchAll();

// Fetch periods (teaching & breaks)
$periods = $pdo->query("SELECT * FROM periods ORDER BY period_number ASC")->fetchAll();

// Fetch all timetable slots for current version
$slots_data = [];
if ($selected_ver_id) {
    $q = "SELECT t.*, 
          sub.subject_code, sub.subject_name, sub.category, sub.color_code,
          st.name as staff_name, st.staff_code,
          c.class_name, c.room_no
          FROM timetable t
          JOIN subjects sub ON t.subject_id = sub.id
          JOIN staff st ON t.staff_id = st.id
          JOIN classes c ON t.class_id = c.id
          WHERE t.version_id = ?
          ORDER BY t.day_name ASC, t.period_id ASC";
    $stmt = $pdo->prepare($q);
    $stmt->execute([$selected_ver_id]);
    $slots_data = $stmt->fetchAll();
}

// Group slots by: [class_id][day_name][period_id]
$classGrid = [];
// Group slots by: [staff_id][day_name][period_id]
$staffGrid = [];

foreach ($slots_data as $slot) {
    $cid = $slot['class_id'];
    $sid = $slot['staff_id'];
    $day = $slot['day_name'];
    $pid = $slot['period_id'];

    if (!isset($classGrid[$cid])) $classGrid[$cid] = [];
    if (!isset($classGrid[$cid][$day])) $classGrid[$cid][$day] = [];
    $classGrid[$cid][$day][$pid] = $slot;

    if (!isset($staffGrid[$sid])) $staffGrid[$sid] = [];
    if (!isset($staffGrid[$sid][$day])) $staffGrid[$sid][$day] = [];
    $staffGrid[$sid][$day][$pid] = $slot;
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- TIMETABLE TOP CONTROLS & FILTER BAR -->
<div class="card border-0 rounded-4 shadow-sm p-4 mb-4 bg-white">
  <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h4 class="fw-bold text-dark mb-0">English Department Master Timetable</h4>
        <?php if ($current_ver): ?>
          <span class="badge bg-<?php echo ($current_ver['status'] == 'PUBLISHED') ? 'success' : 'warning text-dark'; ?> px-3 py-1">
            <?php echo $current_ver['status']; ?>
          </span>
        <?php endif; ?>
      </div>
      <p class="text-muted small mb-0">
        Unified matrix merging <strong>Major Literature</strong> and <strong>Foundation Communication</strong> courses.
      </p>
    </div>

    <!-- Right Controls: Version Selector & Print -->
    <div class="d-flex flex-wrap align-items-center gap-2">
      <!-- Version Selector -->
      <form method="GET" class="d-flex align-items-center gap-2">
        <select name="version_id" class="form-select form-select-sm fw-semibold" onchange="this.form.submit()">
          <?php foreach ($all_versions as $v): ?>
            <option value="<?php echo $v['id']; ?>" <?php echo ($v['id'] == $selected_ver_id) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($v['version_code']); ?> (<?php echo $v['status']; ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </form>

      <!-- Print Button -->
      <button class="btn btn-outline-secondary btn-sm fw-semibold d-flex align-items-center gap-1" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Print / PDF
      </button>
    </div>
  </div>

  <hr class="my-3 text-muted">

  <!-- DYNAMIC VIEW & CATEGORY FILTERS -->
  <div class="row g-3 align-items-center">
    <!-- View Switcher -->
    <div class="col-md-4">
      <label class="form-label small fw-bold text-muted mb-1">Display Perspective</label>
      <div class="btn-group w-100" role="group">
        <input type="radio" class="btn-check" name="viewPerspective" id="viewClass" value="class" checked onchange="switchPerspective('class')">
        <label class="btn btn-outline-primary btn-sm py-2 fw-semibold" for="viewClass">
          <i class="fa-solid fa-school me-1"></i> Class-wise View
        </label>
        
        <input type="radio" class="btn-check" name="viewPerspective" id="viewStaff" value="staff" onchange="switchPerspective('staff')">
        <label class="btn btn-outline-primary btn-sm py-2 fw-semibold" for="viewStaff">
          <i class="fa-solid fa-chalkboard-user me-1"></i> Faculty-wise View
        </label>
      </div>
    </div>

    <!-- Category Filter (All vs Major Only vs Foundation Only) -->
    <div class="col-md-5">
      <label class="form-label small fw-bold text-muted mb-1">Course Category Filter</label>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-dark fw-bold px-3 filter-cat-btn active" data-filter="ALL" onclick="filterCategory('ALL', this)">
          All Courses
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-3 filter-cat-btn" data-filter="MAJOR" onclick="filterCategory('MAJOR', this)">
          <i class="fa-solid fa-star me-1"></i> Major Only (Literature)
        </button>
        <button type="button" class="btn btn-sm btn-outline-success fw-bold px-3 filter-cat-btn" data-filter="FOUNDATION" onclick="filterCategory('FOUNDATION', this)">
          <i class="fa-solid fa-seedling me-1"></i> Foundation Only (Skills)
        </button>
      </div>
    </div>

    <!-- Category Legend Indicators -->
    <div class="col-md-3 text-md-end">
      <label class="form-label small fw-bold text-muted mb-1 d-block">Color Coding Legend</label>
      <div class="d-inline-flex gap-2">
        <span class="badge-major"><i class="fa-solid fa-square text-primary"></i> Major (Blue)</span>
        <span class="badge-foundation"><i class="fa-solid fa-square text-success"></i> Foundation (Green)</span>
      </div>
    </div>
  </div>
</div>

<!-- ============================================== -->
<!-- 1. CLASS-WISE TIMETABLE CONTAINER -->
<!-- ============================================== -->
<div id="classWiseContainer">
  <?php if (!empty($classes) && $selected_ver_id): ?>
    <?php foreach ($classes as $c): ?>
      <div class="table-card p-4 mb-4 class-table-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary px-3 py-2 fw-bold" style="font-size: 0.9rem;">
              <?php echo htmlspecialchars($c['class_name']); ?>
            </span>
            <span class="badge bg-light text-dark border">Semester <?php echo $c['semester']; ?></span>
            <span class="badge bg-light text-secondary border">Classroom: <?php echo htmlspecialchars($c['room_no']); ?></span>
          </div>
          <small class="text-muted fw-semibold">Academic Year: <?php echo htmlspecialchars($c['academic_year']); ?></small>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered text-center align-middle" style="min-width: 850px;">
            <thead class="bg-light">
              <tr>
                <th style="width: 120px;" class="py-3">Day / Timing</th>
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
                      $slot = $classGrid[$c['id']][$day['day_name']][$p['id']] ?? null;
                    ?>
                      <td class="p-2" style="width: 16%;">
                        <?php if ($slot): 
                          $isMajor = ($slot['category'] === 'MAJOR');
                        ?>
                          <div class="timetable-slot <?php echo $isMajor ? 'major-slot' : 'foundation-slot'; ?>" data-category="<?php echo $slot['category']; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                              <span class="badge <?php echo $isMajor ? 'bg-primary' : 'bg-success'; ?> text-white" style="font-size: 0.65rem;">
                                <?php echo $slot['category']; ?>
                              </span>
                              <span class="badge bg-white text-dark border" style="font-size: 0.65rem;">
                                <?php echo htmlspecialchars($slot['subject_code']); ?>
                              </span>
                            </div>
                            
                            <div class="fw-bold text-dark my-1 text-truncate" title="<?php echo htmlspecialchars($slot['subject_name']); ?>" style="font-size: 0.82rem;">
                              <?php echo htmlspecialchars($slot['subject_name']); ?>
                            </div>

                            <div class="text-muted d-flex align-items-center justify-content-center gap-1" style="font-size: 0.75rem;">
                              <i class="fa-solid fa-chalkboard-user"></i>
                              <span class="fw-semibold text-truncate" style="max-width: 120px;"><?php echo htmlspecialchars($slot['staff_name']); ?></span>
                            </div>
                          </div>
                        <?php else: ?>
                          <div class="timetable-slot free-slot">
                            <span>Free</span>
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
    <?php endforeach; ?>
  <?php else: ?>
    <div class="table-card p-5 text-center text-muted">
      <i class="fa-solid fa-calendar-xmark fa-3x mb-3 text-secondary opacity-50"></i>
      <h5>No Timetable Generated Yet</h5>
      <p>Go to the AI Timetable Generator and click "Trigger AI Timetable Engine".</p>
      <a href="<?php echo BASE_URL; ?>admin/generate_timetable.php" class="btn btn-primary-custom btn-sm">Generate Timetable</a>
    </div>
  <?php endif; ?>
</div>

<!-- ============================================== -->
<!-- 2. FACULTY-WISE TIMETABLE CONTAINER (HIDDEN INITIALLY) -->
<!-- ============================================== -->
<div id="staffWiseContainer" class="d-none">
  <?php if (!empty($staff_list) && $selected_ver_id): ?>
    <?php foreach ($staff_list as $st): ?>
      <div class="table-card p-4 mb-4 staff-table-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-dark px-3 py-2 fw-bold" style="font-size: 0.9rem;">
              <i class="fa-solid fa-user-tie me-1"></i> <?php echo htmlspecialchars($st['name']); ?>
            </span>
            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($st['staff_code']); ?></span>
            <span class="badge bg-secondary-subtle text-secondary border"><?php echo htmlspecialchars($st['specialization']); ?></span>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered text-center align-middle" style="min-width: 850px;">
            <thead class="bg-light">
              <tr>
                <th style="width: 120px;" class="py-3">Day / Timing</th>
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
                      $slot = $staffGrid[$st['id']][$day['day_name']][$p['id']] ?? null;
                    ?>
                      <td class="p-2" style="width: 16%;">
                        <?php if ($slot): 
                          $isMajor = ($slot['category'] === 'MAJOR');
                        ?>
                          <div class="timetable-slot <?php echo $isMajor ? 'major-slot' : 'foundation-slot'; ?>" data-category="<?php echo $slot['category']; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                              <span class="badge <?php echo $isMajor ? 'bg-primary' : 'bg-success'; ?> text-white" style="font-size: 0.65rem;">
                                <?php echo $slot['category']; ?>
                              </span>
                              <span class="badge bg-white text-dark border" style="font-size: 0.65rem;">
                                <?php echo htmlspecialchars($slot['room_no']); ?>
                              </span>
                            </div>
                            
                            <div class="fw-bold text-dark my-1 text-truncate" title="<?php echo htmlspecialchars($slot['subject_name']); ?>" style="font-size: 0.82rem;">
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
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
  // Switch between Class-wise and Staff-wise perspective
  function switchPerspective(type) {
    const classContainer = document.getElementById('classWiseContainer');
    const staffContainer = document.getElementById('staffWiseContainer');
    if (type === 'class') {
      classContainer.classList.remove('d-none');
      staffContainer.classList.add('d-none');
    } else {
      classContainer.classList.add('d-none');
      staffContainer.classList.remove('d-none');
    }
  }

  // Filter slots by category (ALL, MAJOR, FOUNDATION)
  function filterCategory(category, btn) {
    document.querySelectorAll('.filter-cat-btn').forEach(b => b.classList.remove('active', 'btn-dark'));
    document.querySelectorAll('.filter-cat-btn').forEach(b => b.classList.add('btn-outline-primary', 'btn-outline-success'));
    btn.classList.add('active', 'btn-dark');

    const slots = document.querySelectorAll('.timetable-slot[data-category]');
    slots.forEach(slot => {
      const cat = slot.getAttribute('data-category');
      if (category === 'ALL' || cat === category) {
        slot.style.display = 'flex';
      } else {
        slot.style.display = 'none';
      }
    });
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
