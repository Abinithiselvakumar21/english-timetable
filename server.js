/**
 * ========================================================
 * English Department AI Timetable System - Unified Web Engine
 * Fully compatible with Vercel Serverless & Render Cloud
 * ========================================================
 */

const express = require('express');
const path = require('path');
const cors = require('cors');
const TimetableScheduler = require('./ai_service/scheduler');

const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use('/assets', express.static(path.join(__dirname, 'assets')));

// Sample In-Memory Department Dataset (Pre-seeded with English Dept Data)
let db = {
    users: [
        { id: 1, username: 'admin', role: 'ADMIN', name: 'Administrator' },
        { id: 2, username: 'hod_english', role: 'HOD', name: 'Dr. S. Sundararajan (HOD)' },
        { id: 3, username: 'dr_kavitha', role: 'STAFF', name: 'Dr. M. Kavitha' },
        { id: 4, username: 'prof_anand', role: 'STAFF', name: 'Prof. R. Anand' },
        { id: 5, username: 'prof_meena', role: 'STAFF', name: 'Prof. P. Meena' },
        { id: 6, username: 'dr_suresh', role: 'STAFF', name: 'Dr. V. Suresh' }
    ],
    classes: [
        { id: 1, class_name: 'BA English I Year', academic_year: '2026-2027', year: 1, semester: 1, room_no: 'Room-101' },
        { id: 2, class_name: 'BA English II Year', academic_year: '2026-2027', year: 2, semester: 3, room_no: 'Room-102' },
        { id: 3, class_name: 'BA English III Year', academic_year: '2026-2027', year: 3, semester: 5, room_no: 'Room-103' }
    ],
    subjects: [
        { id: 1, subject_code: 'ENG101', subject_name: 'British Literature', category: 'MAJOR', semester: 1, weekly_hours: 4 },
        { id: 2, subject_code: 'ENG102', subject_name: 'Indian Writing in English', category: 'MAJOR', semester: 1, weekly_hours: 4 },
        { id: 3, subject_code: 'ENG301', subject_name: 'American Literature', category: 'MAJOR', semester: 3, weekly_hours: 4 },
        { id: 4, subject_code: 'ENG302', subject_name: 'Shakespeare Studies', category: 'MAJOR', semester: 3, weekly_hours: 4 },
        { id: 5, subject_code: 'ENG501', subject_name: 'Literary Criticism', category: 'MAJOR', semester: 5, weekly_hours: 4 },
        { id: 6, subject_code: 'FND101', subject_name: 'Communicative English', category: 'FOUNDATION', semester: 1, weekly_hours: 4 },
        { id: 7, subject_code: 'FND102', subject_name: 'Basic Grammar & Phonetics', category: 'FOUNDATION', semester: 1, weekly_hours: 3 },
        { id: 8, subject_code: 'FND301', subject_name: 'Writing Skills & Composition', category: 'FOUNDATION', semester: 3, weekly_hours: 4 },
        { id: 9, subject_code: 'FND501', subject_name: 'Spoken English & Presentation', category: 'FOUNDATION', semester: 5, weekly_hours: 3 }
    ],
    staff: [
        { id: 1, staff_code: 'ENG001', name: 'Dr. S. Sundararajan (HOD)', designation: 'HOD', specialization: 'Literary Criticism & Shakespeare', max_weekly_workload: 10 },
        { id: 2, staff_code: 'ENG002', name: 'Dr. M. Kavitha', designation: 'Assistant Professor', specialization: 'British Literature & Poetry', max_weekly_workload: 16 },
        { id: 3, staff_code: 'ENG003', name: 'Prof. R. Anand', designation: 'Assistant Professor', specialization: 'American Literature & Drama', max_weekly_workload: 16 },
        { id: 4, staff_code: 'ENG004', name: 'Prof. P. Meena', designation: 'Assistant Professor', specialization: 'Spoken English & Writing Skills', max_weekly_workload: 16 },
        { id: 5, staff_code: 'ENG005', name: 'Dr. V. Suresh', designation: 'Assistant Professor', specialization: 'Communicative English & Grammar', max_weekly_workload: 16 }
    ],
    allocations: [
        { staff_id: 2, subject_id: 1, class_id: 1 },
        { staff_id: 3, subject_id: 2, class_id: 1 },
        { staff_id: 5, subject_id: 6, class_id: 1 },
        { staff_id: 4, subject_id: 7, class_id: 1 },
        { staff_id: 3, subject_id: 3, class_id: 2 },
        { staff_id: 1, subject_id: 4, class_id: 2 },
        { staff_id: 4, subject_id: 8, class_id: 2 },
        { staff_id: 1, subject_id: 5, class_id: 3 },
        { staff_id: 5, subject_id: 9, class_id: 3 }
    ],
    workingDays: [
        { day_name: 'Monday', is_working: 1 },
        { day_name: 'Tuesday', is_working: 1 },
        { day_name: 'Wednesday', is_working: 1 },
        { day_name: 'Thursday', is_working: 1 },
        { day_name: 'Friday', is_working: 1 }
    ],
    periods: [
        { id: 1, period_number: 1, period_name: 'Period 1', start_time: '09:00:00', end_time: '10:00:00', is_break: 0 },
        { id: 2, period_number: 2, period_name: 'Period 2', start_time: '10:00:00', end_time: '11:00:00', is_break: 0 },
        { id: 3, period_number: 3, period_name: 'Period 3', start_time: '11:15:00', end_time: '12:15:00', is_break: 0 },
        { id: 4, period_number: 4, period_name: 'Lunch Break', start_time: '12:15:00', end_time: '01:00:00', is_break: 1 },
        { id: 5, period_number: 5, period_name: 'Period 4', start_time: '01:00:00', end_time: '02:00:00', is_break: 0 },
        { id: 6, period_number: 6, period_name: 'Period 5', start_time: '02:00:00', end_time: '03:00:00', is_break: 0 }
    ],
    timetable: [] // Generated slots stored here
};

// Auto-generate initial timetable on startup
function runInitialScheduler() {
    const scheduler = new TimetableScheduler({
        classes: db.classes,
        subjects: db.subjects,
        staff: db.staff,
        allocations: db.allocations,
        periods: db.periods,
        workingDays: db.workingDays
    });
    const res = scheduler.generate();
    if (res.success) {
        db.timetable = res.slots;
        console.log(`[Cloud Server] Pre-computed ${db.timetable.length} clash-free slots.`);
    }
}
runInitialScheduler();

// ==========================================
// 1. MAIN UI ROUTES
// ==========================================

// Login Page
app.get('/', (req, res) => {
    res.send(renderLoginPage());
});

// Admin Master Timetable View
app.get('/master_timetable', (req, res) => {
    res.send(renderMasterTimetablePage(db.timetable));
});

// Admin AI Generator Control Center
app.get('/generator', (req, res) => {
    res.send(renderGeneratorPage());
});

// Trigger AI Generation API
app.post('/api/generate', (req, res) => {
    const scheduler = new TimetableScheduler({
        classes: db.classes,
        subjects: db.subjects,
        staff: db.staff,
        allocations: db.allocations,
        periods: db.periods,
        workingDays: db.workingDays
    });
    const result = scheduler.generate();
    if (result.success) {
        db.timetable = result.slots;
        res.json({ success: true, message: `Successfully generated ${result.slots.length} clash-free slots on Cloud!`, total_slots: result.slots.length });
    } else {
        res.status(400).json({ success: false, message: result.message });
    }
});

// System Status API
app.get('/api/status', (req, res) => {
    res.json({ status: "ONLINE", service: "English Dept Live Cloud Portal", slots_count: db.timetable.length });
});

// ==========================================
// 2. HTML UI RENDERING TEMPLATES
// ==========================================

function renderLoginPage() {
    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - English Department Timetable</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    body { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; font-family: sans-serif; }
    .card-box { background: white; border-radius: 24px; box-shadow: 0 25px 60px rgba(0,0,0,0.4); max-width: 950px; width: 100%; overflow: hidden; }
  </style>
</head>
<body>
  <div class="card-box row g-0">
    <div class="col-lg-5 p-5 text-white" style="background: linear-gradient(145deg, #2563eb, #1e1b4b);">
      <span class="badge bg-white bg-opacity-25 px-3 py-1 mb-3">AI ACADEMIC SCHEDULING</span>
      <h3 class="fw-bold">Department of English</h3>
      <p class="text-white-50 mt-3" style="line-height: 1.6;">Automated clash-free staff timetable with Major Literature &amp; Foundation courses integration.</p>
      <div class="mt-4 p-3 rounded-3 bg-black bg-opacity-25">
        <small class="text-white-50 fw-bold d-block mb-1">LIVE CLOUD SYSTEM</small>
        <span class="badge bg-success">Status: 100% Online</span>
      </div>
    </div>
    <div class="col-lg-7 p-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Sign In to Portal</h4>
        <span class="badge bg-primary-subtle text-primary border px-3 py-2">English Dept</span>
      </div>
      <form onsubmit="event.preventDefault(); window.location.href='/master_timetable';">
        <div class="mb-3">
          <label class="form-label small fw-bold">Select Role</label>
          <select class="form-select py-2">
            <option>Admin (Full Control)</option>
            <option>HOD (Approval & Review)</option>
            <option>Teaching Faculty</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-bold">Username</label>
          <input type="text" class="form-control py-2" value="admin" required>
        </div>
        <div class="mb-4">
          <label class="form-label small fw-bold">Password</label>
          <input type="password" class="form-control py-2" value="admin123" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow">
          Log In to Master Dashboard <i class="fa-solid fa-arrow-right ms-2"></i>
        </button>
      </form>
    </div>
  </div>
</body>
</html>`;
}

function renderMasterTimetablePage(slots) {
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    const teachingPeriods = db.periods.filter(p => !p.is_break);

    // Group by class
    let tablesHtml = '';
    db.classes.forEach(cls => {
        let rowsHtml = '';
        days.forEach(day => {
            let cellsHtml = '';
            teachingPeriods.forEach(p => {
                const slot = slots.find(s => s.class_id === cls.id && s.day_name === day && s.period_id === p.id);
                if (slot) {
                    const isMajor = slot.category === 'MAJOR';
                    cellsHtml += `<td class="p-2" style="width: 18%;">
                        <div class="p-2 rounded-3 text-start" style="background: ${isMajor ? '#eff6ff' : '#ecfdf5'}; border-left: 4px solid ${isMajor ? '#2563eb' : '#059669'};">
                            <span class="badge ${isMajor ? 'bg-primary' : 'bg-success'}" style="font-size: 0.65rem;">${slot.category}</span>
                            <div class="fw-bold small text-dark my-1">${slot.subject_name}</div>
                            <small class="text-muted"><i class="fa-solid fa-chalkboard-user me-1"></i>${slot.staff_name}</small>
                        </div>
                    </td>`;
                } else {
                    cellsHtml += `<td class="p-2 text-muted small bg-light">Free</td>`;
                }
            });
            rowsHtml += `<tr><td class="fw-bold bg-light">${day}</td>${cellsHtml}</tr>`;
        });

        tablesHtml += `
        <div class="card border-0 rounded-4 shadow-sm p-4 mb-4 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-primary px-3 py-2 fs-6">${cls.class_name} (${cls.room_no})</span>
                <span class="badge bg-light text-dark border">Academic Session 2026-2027</span>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered text-center align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 120px;">Day</th>
                            ${teachingPeriods.map(p => `<th>${p.period_name}<br><small class="text-muted">${p.start_time.substring(0,5)} - ${p.end_time.substring(0,5)}</small></th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>
        </div>`;
    });

    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Master Timetable - English Department</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-light p-4">
  <div class="container-fluid max-w-7xl">
    <!-- Top Action Header -->
    <div class="card border-0 rounded-4 p-4 text-white mb-4 shadow" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <span class="badge bg-warning text-dark px-3 py-1 mb-2 fw-bold"><i class="fa-solid fa-cloud me-1"></i> Live Cloud Master Timetable</span>
          <h3 class="fw-bold mb-1">College English Department Timetable</h3>
          <p class="text-white-50 mb-0">Integrated view of Major Literature (Blue) and Foundation Language (Green) courses.</p>
        </div>
        <div class="d-flex gap-2">
          <a href="/generator" class="btn btn-warning fw-bold px-3 py-2 shadow-sm"><i class="fa-solid fa-wand-magic-sparkles me-1"></i> Run AI Generator</a>
          <button onclick="window.print()" class="btn btn-outline-light px-3 py-2"><i class="fa-solid fa-print me-1"></i> Print / PDF</button>
          <a href="/" class="btn btn-light px-3 py-2 fw-bold">Sign Out</a>
        </div>
      </div>
    </div>

    <!-- Category Legend -->
    <div class="d-flex gap-2 mb-3">
      <span class="badge px-3 py-2 border fw-semibold" style="background: #eff6ff; color: #2563eb; border-color: #bfdbfe !important;">
        <i class="fa-solid fa-square me-1"></i> Major Subjects (Literature)
      </span>
      <span class="badge px-3 py-2 border fw-semibold" style="background: #ecfdf5; color: #059669; border-color: #a7f3d0 !important;">
        <i class="fa-solid fa-square me-1"></i> Foundation Subjects (Skills)
      </span>
    </div>

    <!-- Tables -->
    ${tablesHtml}
  </div>
</body>
</html>`;
}

function renderGeneratorPage() {
    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI Timetable Generator</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="bg-light p-4">
  <div class="container py-4" style="max-width: 800px;">
    <div class="card border-0 rounded-4 shadow p-4 bg-white text-center">
      <div class="rounded-circle bg-warning bg-opacity-25 p-3 d-inline-flex mx-auto mb-3 text-warning">
        <i class="fa-solid fa-wand-magic-sparkles fa-3x"></i>
      </div>
      <h3 class="fw-bold text-dark">AI Timetable Generation Control Center</h3>
      <p class="text-muted">Constraint Satisfaction Problem (CSP) Backtracking Algorithm ready on Cloud.</p>
      
      <div class="my-4">
        <button id="genBtn" onclick="runCloudGenerator()" class="btn btn-warning btn-lg fw-bold px-5 py-3 shadow">
          Trigger AI Timetable Engine Now
        </button>
      </div>

      <div id="statusBox" class="alert alert-info border-0 rounded-3 d-none"></div>

      <div class="d-flex justify-content-center gap-2 mt-3">
        <a href="/master_timetable" class="btn btn-primary fw-semibold"><i class="fa-solid fa-calendar-days me-1"></i> Back to Master Timetable</a>
      </div>
    </div>
  </div>

  <script>
    async function runCloudGenerator() {
      const btn = document.getElementById('genBtn');
      const box = document.getElementById('statusBox');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Calculating Clash-Free Schedule...';

      try {
        const res = await fetch('/api/generate', { method: 'POST' });
        const data = await res.json();
        btn.disabled = false;
        btn.innerHTML = 'Trigger AI Timetable Engine Now';
        box.classList.remove('d-none');
        if (data.success) {
          box.className = 'alert alert-success border-0 rounded-3 mt-3';
          box.innerHTML = '<i class="fa-solid fa-circle-check me-2"></i> ' + data.message;
        } else {
          box.className = 'alert alert-danger border-0 rounded-3 mt-3';
          box.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-2"></i> ' + data.message;
        }
      } catch(e) {
        btn.disabled = false;
        btn.innerHTML = 'Trigger AI Timetable Engine Now';
        alert('Network error occurred.');
      }
    }
  </script>
</body>
</html>`;
}

// Start Server
app.listen(PORT, () => {
    console.log(`🚀 Live Cloud Web Engine Running on Port ${PORT}`);
});

module.exports = app;
