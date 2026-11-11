# AI-Based Staff Timetable Management System for College English Department

## Project Overview
This project is an automated, AI-driven Staff Timetable Generation and Management System exclusively built for a **College English Department**.

It addresses the fundamental academic challenge of integrating **Core Major Literature Courses** (British Literature, Shakespeare, American Literature, Indian Writing in English, Literary Criticism) and **Foundation Communication Courses** (Communicative English, Basic Grammar, Writing Skills, Spoken English) into **ONE Unified Master Timetable** without any staff clashes or class collisions.

---

## 🛠️ Technology Stack
- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5, FontAwesome 6, Plus Jakarta Sans.
- **Backend**: PHP 8.x (Apache Server via XAMPP), PDO Prepared Statements, Session Authentication, Role-Based Access Control (RBAC).
- **AI Microservice**: Node.js, Express.js (Port 5000), Constraint Satisfaction Problem (CSP) with Backtracking Algorithm.
- **Real-time Notifications**: Socket.IO, Node-cron.
- **Database**: MySQL (`english_timetable_db`).

---

## 👥 User Roles & Credentials

All default accounts have password: **`admin123`**

| Role | Username | Email | Responsibilities |
|---|---|---|---|
| **ADMIN** | `admin` | `admin@college.edu` | Faculty management, Major & Foundation subjects, Period timings, Staff allocations, Triggering AI engine. |
| **HOD** | `hod_english` | `hod.english@college.edu` | Reviewing AI timetable drafts, checking conflicts, approving, and publishing official timetable. |
| **STAFF** | `dr_kavitha` | `kavitha@college.edu` | Viewing personal daily/weekly timetable, assigned classes, room numbers, and free periods. |
| **STAFF** | `prof_anand` | `anand@college.edu` | Viewing personal schedule (American Literature & Indian Writing). |
| **STAFF** | `prof_meena` | `meena@college.edu` | Viewing personal schedule (Grammar & Writing Skills). |
| **STAFF** | `dr_suresh` | `suresh@college.edu` | Viewing personal schedule (Communicative English & Spoken English). |

---

## 🚀 How to Run the Project

### Step 1: Start XAMPP
1. Open **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

### Step 2: Start Node.js AI Microservice (Port 5000)
Open Command Prompt or Terminal and run:
```bash
cd C:\xampp\htdocs\english_timetable\ai_service
node server.js
```
*(Server will display: `🚀 English Dept AI Scheduling Engine Running on Port 5000`)*

### Step 3: Open in Browser
Open your web browser (Google Chrome or Microsoft Edge) and navigate to:
👉 **`http://localhost/english_timetable/`**

---

## 🌟 Key Features Demonstrated to College Review Panel

1. **Dual-Category Subject Architecture**:
   - Major subjects (Blue Theme) and Foundation subjects (Green Theme) are independently managed in database tables but automatically integrated into the Master Timetable.
2. **Backtracking AI Algorithm with Zero Clashes**:
   - Zero staff collisions (no professor is double booked).
   - Zero class collisions (no section has two classes at the same period).
   - Workload capacity limit enforcement (Max 16 hrs/week).
   - Even subject distribution across weekdays (Monday through Friday).
3. **Interactive Filterable Master Timetable**:
   - Toggle between **Class-wise View** and **Faculty-wise View**.
   - Filter by **All**, **Major Literature Only**, or **Foundation Only**.
   - Built-in Print and PDF Export capabilities.
4. **HOD Approval Lifecycle**:
   - Generated timetable enters `DRAFT` state -> HOD verifies quality audit -> HOD enters remarks -> Approves and Publishes (`PUBLISHED`).
5. **Staff Portal**:
   - Professors can view their daily timeline, weekly grid, and identified research free hours.
