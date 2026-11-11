<?php
// ========================================================
// Premium Modern Login Portal - College English Department
// File: index.php
// ========================================================

require_once __DIR__ . '/config/db.php';

$error_msg = $_GET['error'] ?? '';
$success_msg = $_GET['success'] ?? '';

// Handle Direct POST Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username      = trim($_POST['username'] ?? '');
    $password      = trim($_POST['password'] ?? '');
    $selected_role = trim($_POST['role'] ?? '');

    if (empty($username) || empty($password)) {
        $error_msg = 'Please enter both username and password.';
    } else {
        try {
            // Using positional parameters to avoid SQLSTATE[HY093] parameter binding issues
            $query = "SELECT u.*, s.name as staff_name, s.id as staff_id 
                      FROM users u 
                      LEFT JOIN staff s ON u.id = s.user_id 
                      WHERE (u.username = ? OR u.email = ?) AND u.status = 'ACTIVE' 
                      LIMIT 1";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if (!$user) {
                $error_msg = 'Invalid username or email address.';
            } elseif (!password_verify($password, $user['password'])) {
                $error_msg = 'Incorrect password entered.';
            } elseif (!empty($selected_role) && $user['role'] !== $selected_role) {
                $error_msg = "Account role mismatch. You are registered as {$user['role']}.";
            } else {
                // Successful Login - Initialize Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['staff_name'] ?? ucfirst($user['username']);
                $_SESSION['staff_id'] = $user['staff_id'] ?? null;
                $_SESSION['logged_in_time'] = time();

                // Redirect based on authorized role
                if ($user['role'] === 'ADMIN') {
                    header("Location: admin/dashboard.php");
                    exit();
                } elseif ($user['role'] === 'HOD') {
                    header("Location: hod/dashboard.php");
                    exit();
                } else {
                    header("Location: staff/dashboard.php");
                    exit();
                }
            }
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

// Redirect if already logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    if ($_SESSION['role'] === 'ADMIN') {
        header("Location: admin/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'HOD') {
        header("Location: hod/dashboard.php");
        exit();
    } else {
        header("Location: staff/dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - English Department Timetable Management System</title>
  
  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <!-- FontAwesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <!-- Google Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }

    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      overflow-x: hidden;
    }

    /* Ambient glowing background */
    body::before {
      content: '';
      position: absolute;
      width: 450px;
      height: 450px;
      background: radial-gradient(circle, rgba(99, 102, 241, 0.35) 0%, rgba(99, 102, 241, 0) 70%);
      top: -50px;
      left: -50px;
      border-radius: 50%;
      z-index: 1;
    }

    body::after {
      content: '';
      position: absolute;
      width: 450px;
      height: 450px;
      background: radial-gradient(circle, rgba(16, 185, 129, 0.25) 0%, rgba(16, 185, 129, 0) 70%);
      bottom: -50px;
      right: -50px;
      border-radius: 50%;
      z-index: 1;
    }

    .main-login-box {
      background: #ffffff;
      border-radius: 28px;
      box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.2);
      overflow: hidden;
      width: 100%;
      max-width: 1000px;
      position: relative;
      z-index: 10;
    }

    .brand-side {
      background: linear-gradient(145deg, #3b82f6 0%, #1d4ed8 40%, #1e1b4b 100%);
      color: #ffffff;
      padding: 50px 40px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
    }

    .brand-content {
      position: relative;
      z-index: 2;
    }

    .feature-item {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 24px;
    }

    .feature-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(8px);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.15rem;
      color: #ffffff;
      flex-shrink: 0;
    }

    .form-side {
      padding: 50px 45px;
      background: #ffffff;
    }

    /* Role Selection Buttons */
    .role-btn-group .btn-check:checked + .btn {
      background: #2563eb !important;
      color: #ffffff !important;
      border-color: #2563eb !important;
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .role-btn-group .btn {
      border: 1.5px solid #e2e8f0;
      color: #475569;
      font-weight: 600;
      padding: 10px;
      border-radius: 12px;
      transition: all 0.2s ease;
    }

    .role-btn-group .btn:hover {
      background: #f8fafc;
      border-color: #cbd5e1;
    }

    .form-control {
      padding: 12px 16px;
      border-radius: 12px;
      border: 1.5px solid #e2e8f0;
      font-size: 0.95rem;
      transition: all 0.2s ease;
    }

    .form-control:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
    }

    .input-group-text {
      border-radius: 12px 0 0 12px;
      border: 1.5px solid #e2e8f0;
      background: #f8fafc;
      color: #64748b;
      padding-left: 16px;
      padding-right: 16px;
    }

    .btn-login-submit {
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      color: white;
      border: none;
      padding: 14px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 1rem;
      box-shadow: 0 8px 20px -4px rgba(37, 99, 235, 0.4);
      transition: all 0.25s ease;
    }

    .btn-login-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 24px -4px rgba(37, 99, 235, 0.5);
      color: white;
    }
  </style>
</head>
<body>

<div class="main-login-box row g-0">
  
  <!-- LEFT COLUMN: BRANDING & FEATURES -->
  <div class="col-lg-5 brand-side">
    <div class="brand-content">
      <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-20 text-white small fw-bold mb-4">
        <i class="fa-solid fa-graduation-cap text-warning"></i>
        <span>ACADEMIC AI SCHEDULING</span>
      </div>

      <h2 class="fw-bold mb-2 text-white">Department of English</h2>
      <p class="text-white-50 mb-4" style="font-size: 0.95rem; line-height: 1.6;">
        AI-Based Staff Timetable Management System with dual-category integration for Major Literature and Foundation Communication courses.
      </p>

      <div class="mt-4">
        <div class="feature-item">
          <div class="feature-icon">
            <i class="fa-solid fa-microchip text-warning"></i>
          </div>
          <div>
            <div class="fw-bold text-white small">Backtracking AI Solver</div>
            <div class="text-white-50" style="font-size: 0.82rem;">Zero faculty double-booking &amp; zero class clashes</div>
          </div>
        </div>

        <div class="feature-item">
          <div class="feature-icon">
            <i class="fa-solid fa-layer-group text-info"></i>
          </div>
          <div>
            <div class="fw-bold text-white small">Unified Master Timetable</div>
            <div class="text-white-50" style="font-size: 0.82rem;">Major Literature &amp; Foundation skills combined</div>
          </div>
        </div>

        <div class="feature-item">
          <div class="feature-icon">
            <i class="fa-solid fa-bell text-success"></i>
          </div>
          <div>
            <div class="fw-bold text-white small">Smart Schedule Notifications</div>
            <div class="text-white-50" style="font-size: 0.82rem;">Automated reminders and timetable updates</div>
          </div>
        </div>
      </div>
    </div>

    <div class="brand-content mt-auto text-white-50 small">
      &copy; <?php echo date('Y'); ?> College English Department. All rights reserved.
    </div>
  </div>

  <!-- RIGHT COLUMN: LOGIN FORM -->
  <div class="col-lg-7 form-side">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold text-dark mb-1">Sign In</h3>
        <p class="text-muted small mb-0">Select your role and enter credentials to access portal.</p>
      </div>
      <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-bold">
        <i class="fa-solid fa-shield-halved me-1"></i> Secure Session
      </span>
    </div>

    <!-- Error/Success Alerts -->
    <?php if (!empty($error_msg)): ?>
      <div class="alert alert-danger py-2 px-3 border-0 small rounded-3 mb-3 d-flex align-items-center gap-2" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?php echo htmlspecialchars($error_msg); ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($success_msg)): ?>
      <div class="alert alert-success py-2 px-3 border-0 small rounded-3 mb-3 d-flex align-items-center gap-2" role="alert">
        <i class="fa-solid fa-circle-check"></i>
        <span><?php echo htmlspecialchars($success_msg); ?></span>
      </div>
    <?php endif; ?>

    <!-- Self-submitting Form -->
    <form action="" method="POST">
      <!-- 1. Role Selection -->
      <div class="mb-4">
        <label class="form-label fw-bold small text-secondary">Portal Access Role *</label>
        <div class="row g-2 role-btn-group">
          <div class="col-4">
            <input type="radio" class="btn-check" name="role" id="role_admin" value="ADMIN" checked>
            <label class="btn w-100 text-center" for="role_admin">
              <i class="fa-solid fa-user-shield d-block mb-1 fs-5"></i>
              <span class="small">Admin</span>
            </label>
          </div>
          <div class="col-4">
            <input type="radio" class="btn-check" name="role" id="role_hod" value="HOD">
            <label class="btn w-100 text-center" for="role_hod">
              <i class="fa-solid fa-user-tie d-block mb-1 fs-5"></i>
              <span class="small">HOD</span>
            </label>
          </div>
          <div class="col-4">
            <input type="radio" class="btn-check" name="role" id="role_staff" value="STAFF">
            <label class="btn w-100 text-center" for="role_staff">
              <i class="fa-solid fa-chalkboard-user d-block mb-1 fs-5"></i>
              <span class="small">Staff</span>
            </label>
          </div>
        </div>
      </div>

      <!-- 2. Username or Email -->
      <div class="mb-3">
        <label for="usernameInput" class="form-label fw-bold small text-secondary">Username or Email *</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
          <input type="text" name="username" id="usernameInput" class="form-control" placeholder="Enter your username or email" required autocomplete="username">
        </div>
      </div>

      <!-- 3. Password -->
      <div class="mb-4">
        <label for="passwordInput" class="form-label fw-bold small text-secondary">Password *</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
          <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
          <button class="btn btn-outline-secondary border" type="button" onclick="togglePassVisibility()" style="border-radius: 0 12px 12px 0;">
            <i class="fa-regular fa-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <!-- 4. Submit Button -->
      <button type="submit" class="btn btn-login-submit w-100 d-flex align-items-center justify-content-center gap-2">
        <span>Log In to Dashboard</span>
        <i class="fa-solid fa-arrow-right"></i>
      </button>
    </form>

    <div class="text-center mt-4">
      <small class="text-muted">College Major Project &bull; English Department Timetable Management</small>
    </div>
  </div>

</div>

<!-- JavaScript to Toggle Password Visibility -->
<script>
  function togglePassVisibility() {
    const input = document.getElementById('passwordInput');
    const icon = document.getElementById('eyeIcon');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
    } else {
      input.type = 'password';
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
    }
  }
</script>

</body>
</html>
