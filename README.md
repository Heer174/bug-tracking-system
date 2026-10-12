# Bug Tracking System (BugTrace)
### Web-Based Software Engineering Mini-Project

A responsive, secure, full-stack Bug Tracking System built with **HTML5, CSS3, JavaScript, PHP (PDO), and MySQL** for managing software defects, assignments, workflow transitions, and analytics.

---

## 🌟 Key Features

- **Multi-Role Authentication & Dashboards:** Dedicated experiences for **Administrators**, **Developers**, and **Testers / QA Reporters**.
- **Defect Lifecycle State Machine:** Enforced workflow (`New` &rarr; `Assigned` &rarr; `In Progress` &rarr; `Fixed` &rarr; `Verified` &rarr; `Reopened` &rarr; `Closed`).
- **Comprehensive Audit Trail (`bug_history`):** Complete chronological tracking of who modified ticket statuses and when.
- **Evidence Attachments:** Secure image upload support (JPG, PNG, GIF, WebP) with client-side preview and modal lightbox zoom.
- **Threaded Discussions:** Real-time comments with user role badges and relative time stamps.
- **Advanced Search & Filtering:** Filter by project, category, priority, severity, assignee, and search keywords with pagination.
- **Visual Analytics:** Zero-dependency Canvas charts for defect status distribution, priority spread, and developer workload rankings.
- **Enterprise-Grade Security:**
  - Prepared statements with PDO for 100% SQL injection prevention.
  - CSRF token verification across all POST actions.
  - XSS protection via `htmlspecialchars()` sanitization.
  - Bcrypt password hashing using PHP `password_hash()`.
  - Secure uploads folder protected against script execution.

---

## 📂 Project Folder Structure

```
e:\SE\
├── assets/
│   ├── css/
│   │   └── style.css            # Dark-navy and electric-blue modern design system
│   └── js/
│       ├── main.js              # UI interactivity, modals, alerts, image lightbox
│       └── charts.js            # Standalone zero-dependency Canvas chart engine
├── config/
│   ├── config.php               # System constants, session settings, base URL
│   └── database.php             # PDO database connection manager & diagnostic screen
├── database/
│   └── bug_tracking_system.sql  # MySQL schema and rich demonstration seed data
├── includes/
│   ├── auth.php                 # Authentication helpers & role verification
│   ├── functions.php            # Security utilities (CSRF, XSS, upload validation)
│   ├── header.php               # Shared layout header & topbar
│   ├── sidebar.php              # Role-aware sidebar navigation
│   └── footer.php               # Shared layout footer & lightbox modals
├── uploads/                     # Storage folder for uploaded bug screenshot evidence
│   └── .htaccess                # Security policy blocking direct script execution
├── index.php                    # Public landing page explaining features & roles
├── login.php                    # Secure sign-in with 1-click Demo quick-fill pills
├── register.php                 # Tester / QA self-registration
├── logout.php                   # Secure session destruction
├── dashboard.php                # Dynamic role-tailored dashboard with cards & charts
├── bugs.php                     # Bug listing with search, sorting, filtering, & pagination
├── bug_create.php               # Defect reporting form with validation & image upload
├── bug_view.php                 # Defect details, timeline, discussion, & status update
├── bug_edit.php                 # Edit defect details and assignments
├── users.php                    # Admin user management (status, roles, password reset)
├── projects.php                 # Project catalog with bug count metrics
├── profile.php                  # User profile and password change
├── reports.php                  # Visual charts, metrics, and developer workload rankings
├── DOCUMENTATION.md             # Complete Software Engineering Mini-Project Report & SRS
└── README.md                    # Setup, installation, and demonstration guide
```

---

## 🚀 Installation & Setup Guide (XAMPP & VS Code)

### Prerequisites
1. **XAMPP** installed on your system (Apache and MySQL).
2. **Visual Studio Code** (or any code editor).
3. Modern web browser (Chrome, Firefox, Edge).

---

### Step 1: Open the Project in VS Code
1. Open Visual Studio Code.
2. Click **File &rarr; Open Folder...** and select `E:\SE`.

---

### Step 2: Configure in XAMPP
You have **two easy methods** to run the project with XAMPP:

#### Method A: Symlink / Virtual Directory in XAMPP `htdocs` (Recommended)
Open PowerShell as Administrator and run:
```powershell
New-Item -ItemType SymbolicLink -Path "D:\xampp\htdocs\bugtrace" -Target "E:\SE"
```
*(Adjust `D:\xampp` or `C:\xampp` based on where XAMPP is installed).*

#### Method B: Copy to `htdocs`
Copy the entire `E:\SE` folder to `D:\xampp\htdocs\bugtrace` (or `C:\xampp\htdocs\bugtrace`).

#### Method C: Run Directly via PHP Built-in Server
If you prefer testing directly from the `E:\SE` directory without moving files:
Open terminal in `E:\SE` and run:
```bash
php -S localhost:8000
```
*(Ensure MySQL in XAMPP is running).*

---

### Step 3: Start MySQL & Apache in XAMPP
1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.

---

### Step 4: Import Database Schema and Seed Data
1. Open your web browser and navigate to:
   ```
   http://localhost/phpmyadmin
   ```
2. Click on the **Import** tab at the top.
3. Click **Choose File** and select:
   ```
   E:\SE\database\bug_tracking_system.sql
   ```
4. Click **Import** (or **Go**) at the bottom.
5. The database `bug_tracking_system` will be created with all 5 tables (`users`, `projects`, `bugs`, `comments`, `bug_history`) and realistic seed data pre-populated!

---

### Step 5: Launch the Application
Open your browser and navigate to:
- **XAMPP URL:** `http://localhost/bugtrace/`
- **Or Built-in Server URL:** `http://localhost:8000/`

---

## 🔑 Demonstration Accounts & Credentials

The seed data provides pre-configured accounts representing each stakeholder role.

| Role | Name | Email | Password | Primary Duties |
|---|---|---|---|---|
| **Administrator** | Eleanor Vance | `admin@bugtrace.com` | `admin123` | Assign bugs, manage users, edit projects, delete duplicates |
| **Developer 1** | Alex Rivera | `alex.dev@bugtrace.com` | `dev123` | Investigate assigned bugs, update to In Progress, mark Fixed |
| **Developer 2** | Sarah Chen | `sarah.dev@bugtrace.com` | `dev123` | Investigate assigned bugs, deploy fixes, post resolution notes |
| **Tester 1** | Michael Chang | `mike.tester@bugtrace.com` | `tester123` | Report bugs with evidence, verify fixes, reopen persistent bugs |
| **Tester 2** | Emma Watson | `emma.tester@bugtrace.com` | `tester123` | Report bugs, track issue lifecycle, participate in discussions |

> **Pro-Tip for Demo:** On the login page (`login.php`), click any of the **Demo Quick Login** buttons to auto-populate credentials instantly!

---

## 🔄 Bug Status Workflow Walkthrough

To demonstrate the full lifecycle to an examiner or professor:
1. **Report (Tester):** Log in as `mike.tester@bugtrace.com`. Click **Report Bug**. Fill in the title, select a project, specify steps to reproduce, expected/actual results, and attach a screenshot.
2. **Assign (Admin):** Log in as `admin@bugtrace.com`. Open the newly reported ticket. Select a developer (e.g., Alex Rivera) from the **Assign Developer** dropdown and save. Status automatically shifts to `Assigned`.
3. **In Progress & Fix (Developer):** Log in as `alex.dev@bugtrace.com`. View the bug in **My Active Workload**. Change status to `In Progress`. Later, update status to `Fixed` with a resolution note.
4. **Verification (Tester):** Log in back as `mike.tester@bugtrace.com`. The bug now shows in **Awaiting My Verification**. If the fix works, change status to `Verified`. If the bug persists, change to `Reopened`.
5. **Close (Admin / QA):** Once verified, change status to `Closed`.
6. **Audit Trail Inspection:** Scroll down on the bug detail page to observe the full timestamped **Status History & Audit Trail** capturing every step!

---

## 🛡️ Security Architecture

1. **SQL Injection Defense:** All queries use PDO prepared statements with bounded parameters.
2. **Cross-Site Scripting (XSS):** All dynamic variables are escaped with `htmlspecialchars($var, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` via the `e()` helper function.
3. **Cross-Site Request Forgery (CSRF):** Unique cryptographically secure random session tokens verified on every POST submission.
4. **Session Fixation Prevention:** Session IDs are regenerated upon successful authentication (`session_regenerate_id(true)`).
5. **File Upload Hardening:** Strict MIME type and extension whitelist (JPG, PNG, GIF, WebP), file size limit (5 MB), randomized filenames, and `.htaccess` protection in `uploads/` preventing executable script execution.

---

## 📄 Software Engineering Documentation
For complete IEEE-format Software Requirements Specification (SRS), UML diagrams (Mermaid), and comprehensive test case matrices, review [`DOCUMENTATION.md`](DOCUMENTATION.md).
