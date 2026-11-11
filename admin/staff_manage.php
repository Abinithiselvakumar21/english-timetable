<?php
// ========================================================
// Staff Management Interface
// File: admin/staff_manage.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['ADMIN']);

$page_title = 'Faculty & Staff Directory';

// Fetch all staff members
$stmt = $pdo->query("SELECT s.*, u.username, u.role,
                     (SELECT COUNT(*) FROM staff_subjects WHERE staff_id = s.id) as assigned_courses_count
                     FROM staff s 
                     LEFT JOIN users u ON s.user_id = u.id 
                     ORDER BY s.id ASC");
$staff_list = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ACTION BAR -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h4 class="fw-bold text-dark mb-1">English Department Faculty Directory</h4>
    <p class="text-muted small mb-0">Manage teaching staff, workload capacity, subject specializations and system access roles.</p>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-primary-custom d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addStaffModal">
      <i class="fa-solid fa-user-plus"></i>
      <span>Add New Faculty</span>
    </button>
  </div>
</div>

<!-- SEARCH & SUMMARY TILES -->
<div class="row g-3 mb-4">
  <div class="col-md-8">
    <div class="input-group bg-white rounded-3 shadow-sm border p-1">
      <span class="input-group-text bg-transparent border-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
      <input type="text" id="staffSearchInput" class="form-control border-0 shadow-none" placeholder="Search by professor name, staff code, or specialization...">
    </div>
  </div>
  <div class="col-md-4 text-md-end d-flex align-items-center justify-content-md-end">
    <span class="badge bg-light text-dark border px-3 py-2 fw-semibold">
      Total Faculty: <span class="text-primary fw-bold" id="staffCountDisplay"><?php echo count($staff_list); ?></span>
    </span>
  </div>
</div>

<!-- FACULTY TABLE CARD -->
<div class="table-card p-4">
  <div class="table-responsive">
    <table class="table custom-table" id="staffTable">
      <thead>
        <tr>
          <th>Staff Code</th>
          <th>Faculty Details</th>
          <th>Role / Designation</th>
          <th>Specialization</th>
          <th>Max Workload</th>
          <th>Assigned Courses</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($staff_list)): ?>
          <?php foreach ($staff_list as $st): ?>
          <tr class="staff-row">
            <td>
              <span class="badge bg-light text-dark border fw-bold px-2 py-1"><?php echo htmlspecialchars($st['staff_code']); ?></span>
            </td>
            <td>
              <div class="fw-bold text-dark"><?php echo htmlspecialchars($st['name']); ?></div>
              <small class="text-muted"><i class="fa-regular fa-envelope me-1"></i><?php echo htmlspecialchars($st['email']); ?></small>
              <?php if (!empty($st['phone'])): ?>
                <br><small class="text-muted"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($st['phone']); ?></small>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-semibold text-secondary small"><?php echo htmlspecialchars($st['designation']); ?></div>
              <span class="badge bg-<?php echo ($st['role'] == 'HOD') ? 'warning text-dark' : 'primary-subtle text-primary border border-primary-subtle'; ?> mt-1">
                <?php echo $st['role'] ?? 'STAFF'; ?>
              </span>
            </td>
            <td>
              <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 text-wrap" style="max-width: 180px;">
                <i class="fa-solid fa-award me-1 text-primary"></i><?php echo htmlspecialchars($st['specialization']); ?>
              </span>
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <strong><?php echo $st['max_weekly_workload']; ?></strong> <span class="text-muted small">hrs/wk</span>
              </div>
            </td>
            <td>
              <span class="badge bg-light text-dark border px-2 py-1 fw-bold">
                <?php echo $st['assigned_courses_count']; ?> Courses
              </span>
            </td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditStaff(<?php echo $st['id']; ?>)" title="Edit Faculty">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteStaff(<?php echo $st['id']; ?>, '<?php echo addslashes($st['name']); ?>')" title="Delete">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">No faculty members found. Click "Add New Faculty" to register.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ========================================== -->
<!-- 1. ADD FACULTY MODAL -->
<!-- ========================================== -->
<div class="modal fade" id="addStaffModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-user-plus text-primary me-2"></i> Register New Faculty</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="addStaffForm" onsubmit="submitAddStaff(event)">
        <div class="modal-body p-4">
          <input type="hidden" name="action" value="create">
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-bold small">Staff Code *</label>
              <input type="text" name="staff_code" class="form-control" placeholder="e.g. ENG006" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small">Full Name (with Title) *</label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Dr. R. Malathi" required>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold small">Email Address *</label>
              <input type="email" name="email" class="form-control" placeholder="malathi@college.edu" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small">Mobile Phone</label>
              <input type="text" name="phone" class="form-control" placeholder="9876543215">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold small">Designation</label>
              <select name="designation" class="form-select">
                <option value="Assistant Professor" selected>Assistant Professor</option>
                <option value="Associate Professor">Associate Professor</option>
                <option value="Professor">Professor</option>
                <option value="Guest Lecturer">Guest Lecturer</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small">Max Weekly Workload (Hours) *</label>
              <input type="number" name="max_weekly_workload" class="form-control" value="16" min="4" max="30" required>
              <small class="text-muted" style="font-size: 0.72rem;">Maximum allowed periods per week.</small>
            </div>

            <div class="col-12">
              <label class="form-label fw-bold small">Specialization / Expertise *</label>
              <input type="text" name="specialization" class="form-control" placeholder="e.g. British Poetry, English Phonetics, Post-Colonial Literature" required>
            </div>

            <hr class="my-3 text-muted">

            <div class="col-md-4">
              <label class="form-label fw-bold small">Login Username</label>
              <input type="text" name="username" class="form-control" placeholder="Auto-fills as staff code">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold small">Initial Password</label>
              <input type="text" name="password" class="form-control" value="admin123" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold small">System Role</label>
              <select name="role" class="form-select">
                <option value="STAFF" selected>Teaching Staff</option>
                <option value="HOD">HOD (Department Head)</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-custom">Save Faculty</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- 2. EDIT FACULTY MODAL -->
<!-- ========================================== -->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Update Faculty Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="editStaffForm" onsubmit="submitEditStaff(event)">
        <div class="modal-body p-4">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" id="edit_staff_id">
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-bold small">Full Name *</label>
              <input type="text" name="name" id="edit_staff_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small">Email Address *</label>
              <input type="email" name="email" id="edit_staff_email" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold small">Mobile Phone</label>
              <input type="text" name="phone" id="edit_staff_phone" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small">Designation</label>
              <select name="designation" id="edit_staff_designation" class="form-select">
                <option value="Assistant Professor">Assistant Professor</option>
                <option value="Associate Professor">Associate Professor</option>
                <option value="Professor">Professor</option>
                <option value="Guest Lecturer">Guest Lecturer</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold small">Max Weekly Workload (Hours) *</label>
              <input type="number" name="max_weekly_workload" id="edit_staff_workload" class="form-control" min="4" max="30" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small">Reset Password (Leave blank to keep current)</label>
              <input type="text" name="password" class="form-control" placeholder="New password if resetting">
            </div>

            <div class="col-12">
              <label class="form-label fw-bold small">Specialization / Expertise *</label>
              <input type="text" name="specialization" id="edit_staff_specialization" class="form-control" required>
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-custom">Update Details</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Live Search
  document.getElementById('staffSearchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#staffTable tbody .staff-row');
    let visible = 0;
    rows.forEach(row => {
      let text = row.innerText.toLowerCase();
      if (text.includes(filter)) {
        row.style.display = '';
        visible++;
      } else {
        row.style.display = 'none';
      }
    });
    document.getElementById('staffCountDisplay').innerText = visible;
  });

  // Submit Add Staff
  async function submitAddStaff(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('addStaffForm'));
    try {
      const res = await fetch('<?php echo BASE_URL; ?>api/staff_api.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        alert(data.message);
        window.location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    } catch(err) {
      alert('Network error occurred.');
    }
  }

  // Open Edit Modal
  async function openEditStaff(id) {
    try {
      const res = await fetch(`<?php echo BASE_URL; ?>api/staff_api.php?action=get&id=${id}`);
      const result = await res.json();
      if (result.success) {
        const s = result.data;
        document.getElementById('edit_staff_id').value = s.id;
        document.getElementById('edit_staff_name').value = s.name;
        document.getElementById('edit_staff_email').value = s.email;
        document.getElementById('edit_staff_phone').value = s.phone || '';
        document.getElementById('edit_staff_designation').value = s.designation;
        document.getElementById('edit_staff_workload').value = s.max_weekly_workload;
        document.getElementById('edit_staff_specialization').value = s.specialization;
        new bootstrap.Modal(document.getElementById('editStaffModal')).show();
      } else {
        alert(result.message);
      }
    } catch(err) {
      alert('Failed to load faculty information.');
    }
  }

  // Submit Edit Staff
  async function submitEditStaff(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('editStaffForm'));
    try {
      const res = await fetch('<?php echo BASE_URL; ?>api/staff_api.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        alert(data.message);
        window.location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    } catch(err) {
      alert('Network error occurred.');
    }
  }

  // Delete Staff
  async function deleteStaff(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"? This will also remove their allocated subjects and user account.`)) {
      return;
    }
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    try {
      const res = await fetch('<?php echo BASE_URL; ?>api/staff_api.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        alert(data.message);
        window.location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    } catch(err) {
      alert('Network error occurred.');
    }
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
