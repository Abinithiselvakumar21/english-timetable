<?php
// ========================================================
// Main UI Header with Role-Specific Navigation Sidebar
// File: includes/header.php
// ========================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth_check.php';

$current_role = $_SESSION['role'] ?? 'GUEST';
$username = $_SESSION['username'] ?? 'User';
$name = $_SESSION['name'] ?? $username;
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $page_title ?? 'English Dept Timetable'; ?> - AI Timetable System</title>
  
  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  
  <!-- FontAwesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  
  <!-- Custom Application CSS (with cache buster) -->
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">

  <style>
    /* Inline Fallback Styling for Sidebar and Layout */
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: #f8fafc;
      color: #334155;
      min-height: 100vh;
      overflow-x: hidden;
    }
    .app-wrapper {
      display: flex;
      min-height: 100vh;
    }
    .sidebar {
      width: 270px;
      background: #ffffff;
      border-right: 1px solid #e2e8f0;
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      z-index: 100;
      display: flex;
      flex-direction: column;
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.02);
    }
    .main-content {
      margin-left: 270px;
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      background-color: #f8fafc;
    }
    .topbar {
      background: #ffffff;
      border-bottom: 1px solid #e2e8f0;
      padding: 16px 30px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 90;
    }
    .content-body {
      padding: 30px;
      flex: 1;
    }
    .nav-link-custom {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 11px 16px;
      color: #475569;
      font-weight: 500;
      font-size: 0.92rem;
      border-radius: 12px;
      text-decoration: none;
      transition: all 0.2s ease;
      margin-bottom: 4px;
    }
    .nav-link-custom:hover {
      background: #f1f5f9;
      color: #2563eb;
    }
    .nav-link-custom.active {
      background: #2563eb;
      color: #ffffff;
      font-weight: 600;
      box-shadow: 0 6px 14px rgba(37, 99, 235, 0.25);
    }
    .stat-card {
      background: #ffffff;
      border-radius: 18px;
      border: 1px solid rgba(226, 232, 240, 0.8);
      padding: 24px;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .stat-icon {
      width: 58px;
      height: 58px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
    }
    .stat-icon.primary { background: #eff6ff; color: #2563eb; }
    .stat-icon.success { background: #ecfdf5; color: #059669; }
    .stat-icon.warning { background: #fffbeb; color: #d97706; }
    .stat-value {
      font-size: 1.85rem;
      font-weight: 800;
      color: #0f172a;
      line-height: 1.2;
    }
    .stat-label {
      font-size: 0.88rem;
      color: #64748b;
      font-weight: 500;
      margin-top: 4px;
    }
    .table-card {
      background: #ffffff;
      border-radius: 18px;
      border: 1px solid rgba(226, 232, 240, 0.8);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    }
    .custom-table thead th {
      background: #f8fafc;
      color: #475569;
      font-weight: 700;
      font-size: 0.82rem;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      padding: 16px 20px;
      border-bottom: 1px solid #e2e8f0;
    }
    .custom-table tbody td {
      padding: 16px 20px;
      border-bottom: 1px solid #f1f5f9;
      font-size: 0.92rem;
    }
    .badge-major {
      background-color: #eff6ff;
      color: #2563eb;
      border: 1px solid #bfdbfe;
      font-weight: 600;
      padding: 5px 12px;
      border-radius: 8px;
      font-size: 0.8rem;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    .badge-foundation {
      background-color: #ecfdf5;
      color: #059669;
      border: 1px solid #a7f3d0;
      font-weight: 600;
      padding: 5px 12px;
      border-radius: 8px;
      font-size: 0.8rem;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
  </style>
</head>
<body>

<div class="app-wrapper">
  <!-- SIDEBAR NAVIGATION -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header" style="padding: 24px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px;">
      <div class="sidebar-logo" style="width: 44px; height: 44px; background: linear-gradient(135deg, #2563eb, #1e1b4b); color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
        <i class="fa-solid fa-graduation-cap"></i>
      </div>
      <div>
        <h6 class="mb-0 fw-bold text-dark">English Dept</h6>
        <span class="badge bg-primary text-white" style="font-size: 0.7rem;"><?php echo $current_role; ?> PORTAL</span>
      </div>
    </div>

    <div class="sidebar-menu" style="padding: 16px; flex: 1; overflow-y: auto;">
      
      <?php if ($current_role === 'ADMIN'): ?>
        <!-- ADMIN MENU ITEMS -->
        <div class="text-uppercase text-muted fw-bold mb-2 ps-2" style="font-size: 0.72rem; letter-spacing: 0.8px;">Dashboard</div>
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="nav-link-custom <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
          <i class="fa-solid fa-chart-pie me-2"></i> Overview
        </a>

        <div class="text-uppercase text-muted fw-bold mb-2 ps-2 mt-3" style="font-size: 0.72rem; letter-spacing: 0.8px;">Academics</div>
        <a href="<?php echo BASE_URL; ?>admin/staff_manage.php" class="nav-link-custom <?php echo ($current_page == 'staff_manage.php') ? 'active' : ''; ?>">
          <i class="fa-solid fa-chalkboard-user me-2"></i> Staff Directory
        </a>
        <a href="<?php echo BASE_URL; ?>admin/subject_manage.php" class="nav-link-custom <?php echo ($current_page == 'subject_manage.php') ? 'active' : ''; ?>">
          <i class="fa-solid fa-book-bookmark me-2"></i> Major &amp; Foundation
        </a>
        <a href="<?php echo BASE_URL; ?>admin/class_manage.php" class="nav-link-custom <?php echo ($current_page == 'class_manage.php') ? 'active' : ''; ?>">
          <i class="fa-solid fa-school me-2"></i> Classes &amp; Periods
        </a>
        <a href="<?php echo BASE_URL; ?>admin/allocation.php" class="nav-link-custom <?php echo ($current_page == 'allocation.php') ? 'active' : ''; ?>">
          <i class="fa-solid fa-user-check me-2"></i> Staff Allocation
        </a>

        <div class="text-uppercase text-muted fw-bold mb-2 ps-2 mt-3" style="font-size: 0.72rem; letter-spacing: 0.8px;">AI Engine</div>
        <a href="<?php echo BASE_URL; ?>admin/generate_timetable.php" class="nav-link-custom <?php echo ($current_page == 'generate_timetable.php') ? 'active' : ''; ?>">
          <i class="fa-solid fa-wand-magic-sparkles text-warning me-2"></i> AI Generator
        </a>
        <a href="<?php echo BASE_URL; ?>admin/master_timetable.php" class="nav-link-custom <?php echo ($current_page == 'master_timetable.php') ? 'active' : ''; ?>">
          <i class="fa-solid fa-calendar-days me-2"></i> Master Timetable
        </a>

      <?php elseif ($current_role === 'HOD'): ?>
        <!-- HOD MENU ITEMS -->
        <div class="text-uppercase text-muted fw-bold mb-2 ps-2" style="font-size: 0.72rem; letter-spacing: 0.8px;">HOD Controls</div>
        <a href="<?php echo BASE_URL; ?>hod/dashboard.php" class="nav-link-custom <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
          <i class="fa-solid fa-gauge-high me-2"></i> Executive View
        </a>
        <a href="<?php echo BASE_URL; ?>hod/review_timetable.php" class="nav-link-custom <?php echo ($current_page == 'review_timetable.php') ? 'active' : ''; ?>">
          <i class="fa-solid fa-clipboard-check text-success me-2"></i> Review &amp; Approve
        </a>
        <a href="<?php echo BASE_URL; ?>admin/master_timetable.php" class="nav-link-custom <?php echo ($current_page == 'master_timetable.php') ? 'active' : ''; ?>">
          <i class="fa-solid fa-calendar-days me-2"></i> Master Timetable
        </a>
        <a href="<?php echo BASE_URL; ?>hod/workload_report.php" class="nav-link-custom <?php echo ($current_page == 'workload_report.php') ? 'active' : ''; ?>">
          <i class="fa-solid fa-file-waveform me-2"></i> Workload Report
        </a>

      <?php elseif ($current_role === 'STAFF'): ?>
        <!-- STAFF MENU ITEMS -->
        <div class="text-uppercase text-muted fw-bold mb-2 ps-2" style="font-size: 0.72rem; letter-spacing: 0.8px;">My Schedule</div>
        <a href="<?php echo BASE_URL; ?>staff/dashboard.php" class="nav-link-custom <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
          <i class="fa-solid fa-house me-2"></i> My Dashboard
        </a>
        <a href="<?php echo BASE_URL; ?>staff/my_schedule.php" class="nav-link-custom <?php echo ($current_page == 'my_schedule.php') ? 'active' : ''; ?>">
          <i class="fa-solid fa-calendar-week me-2"></i> Weekly Schedule
        </a>
        <a href="<?php echo BASE_URL; ?>admin/master_timetable.php" class="nav-link-custom <?php echo ($current_page == 'master_timetable.php') ? 'active' : ''; ?>">
          <i class="fa-solid fa-eye me-2"></i> Department Timetable
        </a>
      <?php endif; ?>

    </div>

    <!-- SIDEBAR FOOTER (USER LOGOUT) -->
    <div style="padding: 16px; border-top: 1px solid #f1f5f9; background: #fafafa;">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <div style="width: 38px; height: 38px; border-radius: 50%; background: #e0e7ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-weight: 700;">
            <?php echo strtoupper(substr($name, 0, 1)); ?>
          </div>
          <div>
            <div class="fw-bold text-dark text-truncate" style="max-width: 120px; font-size: 0.88rem;">
              <?php echo htmlspecialchars($name); ?>
            </div>
            <div class="text-muted" style="font-size: 0.75rem;">
              <?php echo $current_role; ?>
            </div>
          </div>
        </div>
        <a href="<?php echo BASE_URL; ?>api/logout.php" class="btn btn-outline-danger btn-sm rounded-circle p-2" title="Sign Out">
          <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </a>
      </div>
    </div>
  </aside>

  <!-- MAIN VIEW CONTAINER -->
  <main class="main-content">
    <!-- TOPBAR -->
    <header class="topbar">
      <div class="d-flex align-items-center gap-3">
        <h5 class="mb-0 fw-bold text-dark"><?php echo $page_title ?? 'Dashboard'; ?></h5>
      </div>
      
      <div class="d-flex align-items-center gap-3">
        <span class="badge bg-light text-secondary border px-3 py-2 fw-semibold">
          <i class="fa-regular fa-clock me-1 text-primary"></i> <span id="liveClock">00:00:00</span>
        </span>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fw-bold">
          <i class="fa-solid fa-book-open-reader me-1"></i> Dept of English
        </span>
      </div>
    </header>

    <!-- PAGE CONTENT BODY -->
    <div class="content-body">
      <!-- Flash Message Alerts -->
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
          <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($_GET['success']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
          <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
