<?php
// ========================================================
// Subject Management Interface (Major & Foundation)
// File: admin/subject_manage.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
check_role(['ADMIN']);

$page_title = 'Department Subjects Management';

// Fetch all subjects with allocated primary staff details
$query = "SELECT s.*, 
          GROUP_CONCAT(CONCAT(st.name, ' (', c.class_name, ')') SEPARATOR '<br>') as staff_assigned
          FROM subjects s
          LEFT JOIN staff_subjects ss ON s.id = ss.subject_id
          LEFT JOIN staff st ON ss.staff_id = st.id
          LEFT JOIN classes c ON ss.class_id = c.id
          GROUP BY s.id
          ORDER BY s.semester ASC, s.category ASC, s.id ASC";
$subjects = $pdo->query($query)->fetchAll();

$major_list = array_filter($subjects, fn($s) => $s['category'] === 'MAJOR');
$foundation_list = array_filter($subjects, fn($s) => $s['category'] === 'FOUNDATION');

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ACTION BAR -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
  <div>
    <h4 class="fw-bold text-dark mb-1">Subject Curriculum Management</h4>
    <p class="text-muted small mb-0">Independently manage Major Literature subjects and Foundation Communication subjects.</p>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-outline-primary fw-semibold d-flex align-items-center gap-2" onclick="openAddModal('MAJOR')">
      <i class="fa-solid fa-plus"></i>
      <span>Add Major Subject</span>
    </button>
    <button type="button" class="btn btn-success fw-semibold d-flex align-items-center gap-2" onclick="openAddModal('FOUNDATION')">
      <i class="fa-solid fa-plus"></i>
      <span>Add Foundation Subject</span>
    </button>
  </div>
</div>

<!-- CATEGORY SUMMARY BADGES -->
<div class="row g-4 mb-4">
  <div class="col-md-6">
    <div class="card border-0 rounded-4 p-4 shadow-sm" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 6px solid #2563eb !important;">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="badge bg-primary text-white mb-2 px-3 py-1 fw-bold">MAJOR SUBJECTS</span>
          <h3 class="fw-bold text-dark mb-1"><?php echo count($major_list); ?> Courses</h3>
          <p class="text-muted small mb-0">Core English Literature, Shakespeare, American &amp; Indian Writing.</p>
        </div>
        <div class="rounded-circle bg-white text-primary p-3 shadow-sm">
          <i class="fa-solid fa-book-bookmark fa-2x"></i>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card border-0 rounded-4 p-4 shadow-sm" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-left: 6px solid #059669 !important;">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="badge bg-success text-white mb-2 px-3 py-1 fw-bold">FOUNDATION SUBJECTS</span>
          <h3 class="fw-bold text-dark mb-1"><?php echo count($foundation_list); ?> Courses</h3>
          <p class="text-muted small mb-0">Communicative English, Basic Grammar, Writing Skills &amp; Phonetics.</p>
        </div>
        <div class="rounded-circle bg-white text-success p-3 shadow-sm">
          <i class="fa-solid fa-seedling fa-2x"></i>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- TABS NAVIGATION -->
<div class="table-card p-4">
  <ul class="nav nav-pills mb-4 gap-2" id="subjectTabs" role="tablist">
    <li class="nav-item">
      <button class="nav-link active px-4 py-2 fw-bold" id="all-tab" data-bs-toggle="pill" data-bs-target="#tab-all">
        <i class="fa-solid fa-list-check me-2"></i> All Subjects (<?php echo count($subjects); ?>)
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link px-4 py-2 fw-bold" id="major-tab" data-bs-toggle="pill" data-bs-target="#tab-major">
        <i class="fa-solid fa-star text-primary me-2"></i> Major Subjects (<?php echo count($major_list); ?>)
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link px-4 py-2 fw-bold" id="foundation-tab" data-bs-toggle="pill" data-bs-target="#tab-foundation">
        <i class="fa-solid fa-seedling text-success me-2"></i> Foundation Subjects (<?php echo count($foundation_list); ?>)
      </button>
    </li>
  </ul>

  <!-- TAB CONTENT -->
  <div class="tab-content">
    
    <!-- 1. ALL SUBJECTS -->
    <div class="tab-pane fade show active" id="tab-all">
      <?php renderSubjectTable($subjects); ?>
    </div>

    <!-- 2. MAJOR ONLY -->
    <div class="tab-pane fade" id="tab-major">
      <?php renderSubjectTable($major_list); ?>
    </div>

    <!-- 3. FOUNDATION ONLY -->
    <div class="tab-pane fade" id="tab-foundation">
      <?php renderSubjectTable($foundation_list); ?>
    </div>

  </div>
</div>

<?php
/**
 * Reusable helper function to render subjects table
 */
function renderSubjectTable($data) {
  ?>
  <div class="table-responsive">
    <table class="table custom-table">
      <thead>
        <tr>
          <th>Subject Code</th>
          <th>Subject Title</th>
          <th>Category</th>
          <th>Semester</th>
          <th>Weekly Required Hours</th>
          <th>Assigned Faculty</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($data)): ?>
          <?php foreach ($data as $row): ?>
          <tr>
            <td>
              <span class="badge bg-light text-dark border fw-bold px-2 py-1"><?php echo htmlspecialchars($row['subject_code']); ?></span>
            </td>
            <td>
              <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['subject_name']); ?></div>
              <?php if (!empty($row['description'])): ?>
                <small class="text-muted"><?php echo htmlspecialchars($row['description']); ?></small>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($row['category'] === 'MAJOR'): ?>
                <span class="badge-major"><i class="fa-solid fa-star"></i> MAJOR</span>
              <?php else: ?>
                <span class="badge-foundation"><i class="fa-solid fa-seedling"></i> FOUNDATION</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-secondary-subtle text-dark border">Semester <?php echo $row['semester']; ?></span>
            </td>
            <td>
              <strong class="text-dark"><?php echo $row['weekly_hours']; ?></strong> <span class="text-muted small">periods / wk</span>
            </td>
            <td>
              <?php if (!empty($row['staff_assigned'])): ?>
                <small class="fw-semibold text-dark"><?php echo $row['staff_assigned']; ?></small>
              <?php else: ?>
                <span class="badge bg-light text-muted border">Not Allocated</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal(<?php echo $row['id']; ?>)" title="Edit">
                <i class="fa-solid fa-pen-to-square"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteSubject(<?php echo $row['id']; ?>, '<?php echo addslashes($row['subject_name']); ?>')" title="Delete">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">No subjects found in this category.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php
}
?>

<!-- ========================================== -->
<!-- ADD / EDIT SUBJECT MODAL -->
<!-- ========================================== -->
<div class="modal fade" id="subjectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark" id="modalSubjectTitle">
          <i class="fa-solid fa-book-bookmark text-primary me-2"></i> Add Department Subject
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="subjectForm" onsubmit="submitSubjectForm(event)">
        <div class="modal-body p-4">
          <input type="hidden" name="action" id="sub_form_action" value="create">
          <input type="hidden" name="id" id="sub_form_id">
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-bold small">Subject Category *</label>
              <select name="category" id="sub_category" class="form-select" required>
                <option value="MAJOR">MAJOR (English Literature Core)</option>
                <option value="FOUNDATION">FOUNDATION (Communication & Grammar)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small">Subject Code *</label>
              <input type="text" name="subject_code" id="sub_code" class="form-control" placeholder="e.g. ENG103 or FND103" required>
            </div>

            <div class="col-md-8">
              <label class="form-label fw-bold small">Subject Title *</label>
              <input type="text" name="subject_name" id="sub_name" class="form-control" placeholder="e.g. History of English Language" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-bold small">Semester *</label>
              <select name="semester" id="sub_semester" class="form-select" required>
                <option value="1">Semester 1 (I Year)</option>
                <option value="2">Semester 2 (I Year)</option>
                <option value="3">Semester 3 (II Year)</option>
                <option value="4">Semester 4 (II Year)</option>
                <option value="5">Semester 5 (III Year)</option>
                <option value="6">Semester 6 (III Year)</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-bold small">Weekly Required Periods (Hours) *</label>
              <input type="number" name="weekly_hours" id="sub_hours" class="form-control" value="4" min="1" max="10" required>
              <small class="text-muted" style="font-size: 0.72rem;">Periods needed each week across working days.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small">Brief Description</label>
              <input type="text" name="description" id="sub_desc" class="form-control" placeholder="Course objectives or topics">
            </div>
          </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-custom" id="subSubmitBtn">Save Subject</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  let modalInstance = null;

  function openAddModal(category = 'MAJOR') {
    document.getElementById('subjectForm').reset();
    document.getElementById('sub_form_action').value = 'create';
    document.getElementById('sub_form_id').value = '';
    document.getElementById('sub_code').readOnly = false;
    document.getElementById('sub_category').value = category;
    document.getElementById('modalSubjectTitle').innerHTML = `<i class="fa-solid fa-plus text-primary me-2"></i> Add New ${category} Subject`;
    document.getElementById('subSubmitBtn').innerText = 'Save Subject';

    if (!modalInstance) {
      modalInstance = new bootstrap.Modal(document.getElementById('subjectModal'));
    }
    modalInstance.show();
  }

  async function openEditModal(id) {
    try {
      const res = await fetch(`<?php echo BASE_URL; ?>api/subject_api.php?action=get&id=${id}`);
      const result = await res.json();
      if (result.success) {
        const s = result.data;
        document.getElementById('sub_form_action').value = 'update';
        document.getElementById('sub_form_id').value = s.id;
        document.getElementById('sub_category').value = s.category;
        document.getElementById('sub_code').value = s.subject_code;
        document.getElementById('sub_code').readOnly = true;
        document.getElementById('sub_name').value = s.subject_name;
        document.getElementById('sub_semester').value = s.semester;
        document.getElementById('sub_hours').value = s.weekly_hours;
        document.getElementById('sub_desc').value = s.description || '';
        document.getElementById('modalSubjectTitle').innerHTML = `<i class="fa-solid fa-pen-to-square text-primary me-2"></i> Update Subject: ${s.subject_code}`;
        document.getElementById('subSubmitBtn').innerText = 'Update Changes';

        if (!modalInstance) {
          modalInstance = new bootstrap.Modal(document.getElementById('subjectModal'));
        }
        modalInstance.show();
      } else {
        alert(result.message);
      }
    } catch(e) {
      alert('Could not fetch subject details.');
    }
  }

  async function submitSubjectForm(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('subjectForm'));
    try {
      const res = await fetch('<?php echo BASE_URL; ?>api/subject_api.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        alert(data.message);
        window.location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    } catch(err) {
      alert('Network communication error.');
    }
  }

  async function deleteSubject(id, name) {
    if (!confirm(`Are you sure you want to remove the subject "${name}"?`)) {
      return;
    }
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    try {
      const res = await fetch('<?php echo BASE_URL; ?>api/subject_api.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        alert(data.message);
        window.location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    } catch(err) {
      alert('Network communication error.');
    }
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
