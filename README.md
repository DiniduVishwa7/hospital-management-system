# MediCore — Hospital Management System

**MediCore** is a responsive, PHP-based hospital administration project built to demonstrate secure sign-in, relational database design, and CRUD workflows for patients, doctors, appointments, and medical records.

> **Educational portfolio project.** MediCore is a local development demonstration, **not** production-ready hospital software. Use fictional data only; do not store real patient or medical information.

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap&logoColor=white)
![Status](https://img.shields.io/badge/Project-Educational%20Demo-2563eb)

## Overview

The application brings common hospital administration tasks into one workspace. An administrator can log in, see live dashboard statistics, and manage related records through a navy-and-blue interface designed for desktop and mobile screens.

### Main features

| Module | Features |
| --- | --- |
| **Admin authentication** | Username/password sign-in, password-hash verification, PHP sessions, logout, protected pages, and inactivity timeout |
| **Dashboard** | Live counts for patients, doctors, total appointments, and today's appointments; five recent appointments; quick navigation |
| **Patients** | Add, view, edit, and delete patients; address and contact details; search and gender filter |
| **Doctors** | Add, view, edit, and delete doctors; specialization and contact details; search and specialization filter |
| **Appointments** | Book appointments for existing patients/doctors; edit, cancel, and delete; date/status filters; checks for identical-time double bookings |
| **Medical records** | Create, view, edit, and delete records linked to patients and optionally doctors; diagnosis, treatment, notes, and dates; search/filtering |
| **Responsive interface** | Bootstrap 5 layouts, mobile-friendly navigation and tables, custom styling |


## 📸 Project Screenshots

### Dashboard
Real-time hospital statistics and recent appointments.

![MediCore Dashboard](docs/screenshots/dashboard.png)

### Administrator Login
Secure administrator login interface.

![MediCore Login](docs/screenshots/login.png)

### Patient Management
Add, view, edit, delete, and search patient records.

![Patient Management](docs/screenshots/patients.png)

### Doctor Management
Manage doctor profiles and specializations.

![Doctor Management](docs/screenshots/doctors.png)

### Appointment Management
Schedule and manage patient appointments.

![Appointment Management](docs/screenshots/appointments.png)

### Medical Records Management
Manage patient medical records and clinical information.

![Medical Records](docs/screenshots/medical-records.png)

### Mobile Responsive Design
MediCore interface optimized for smaller screens.

![MediCore Mobile View](docs/screenshots/mobile.png)


## Technology stack

- **Frontend:** HTML5, CSS3, Bootstrap 5, Bootstrap Icons, JavaScript where needed
- **Backend:** PHP 8.2 with PDO
- **Database:** MySQL / MariaDB (InnoDB tables and foreign keys)
- **Local environment:** XAMPP and phpMyAdmin
- **Development and version control:** VS Code, Git, GitHub Desktop

## Project structure

This tree is generated from the repository file inventory after the module moves. It also shows the existing local-only database configuration; `.git/` internals are omitted.

```text
hospital-management-system/
├── assets/
│   ├── css/
│   │   ├── login.css
│   │   ├── medicore-responsive.css
│   │   └── style.css
│   └── images/
│       └── hospital-banner.avif
├── config/
│   ├── .htaccess
│   └── database.local.php
├── database/
│   ├── .htaccess
│   └── hospital_management.sql
├── docs/
│   ├── screenshots/
│   │   ├── appointments.png
│   │   ├── dashboard.png
│   │   ├── doctors.png
│   │   ├── login.png
│   │   ├── medical-records.png
│   │   ├── mobile.png
│   │   └── patients.png
│   ├── LOCAL_SETUP.md
│   ├── SCREENSHOT_GUIDE.md
│   └── SECURITY_REVIEW.md
├── includes/
│   ├── .htaccess
│   ├── auth_guard.php
│   └── directory_search.php
├── modules/
│   ├── appointments/
│   │   ├── add_appointment.php
│   │   ├── appointment_common.php
│   │   ├── appointments.php
│   │   ├── delete_appointment.php
│   │   └── edit_appointment.php
│   ├── doctors/
│   │   ├── add_doctor.php
│   │   ├── delete_doctor.php
│   │   ├── doctors.php
│   │   └── edit_doctor.php
│   ├── medical-records/
│   │   ├── add_medical_record.php
│   │   ├── delete_medical_record.php
│   │   ├── edit_medical_record.php
│   │   ├── medical_record_common.php
│   │   ├── medical_records.php
│   │   └── view_medical_record.php
│   ├── patients/
│   │   ├── add_patient.php
│   │   ├── delete_patient.php
│   │   ├── edit_patient.php
│   │   └── patients.php
│   └── .htaccess
├── .gitattributes
├── .gitignore
├── README.md
├── add_appointment.php
├── add_doctor.php
├── add_medical_record.php
├── add_patient.php
├── appointment_common.php
├── appointments.php
├── auth_guard.php
├── delete_appointment.php
├── delete_doctor.php
├── delete_medical_record.php
├── delete_patient.php
├── directory_search.php
├── doctors.php
├── edit_appointment.php
├── edit_doctor.php
├── edit_medical_record.php
├── edit_patient.php
├── index.php
├── login.php
├── logout.php
├── medical_record_common.php
├── medical_records.php
├── patients.php
└── view_medical_record.php
```

`config/database.local.php` is created locally and excluded from version control. The current `assets/` folder contains CSS and an image; there are no standalone JavaScript files.

### Application architecture

- **Public entry points:** `index.php` serves the dashboard; `login.php` and `logout.php` handle sign-in and token-protected POST logout.
- **Compatibility wrappers:** root CRUD files such as `patients.php` and `edit_appointment.php` use `require __DIR__` to execute their implementations under `modules/` in the same request. Existing browser URLs, query parameters, GET/POST submissions, redirects, navigation, and asset references are preserved. Root `auth_guard.php`, `directory_search.php`, `appointment_common.php`, and `medical_record_common.php` remain `require_once` compatibility shims.
- **Shared helpers:** `includes/auth_guard.php` checks the authenticated session, applies the 30-minute inactivity timeout and security headers, and initializes `logout_token`. `includes/directory_search.php` provides shared search-input helpers. Login and logout retain their separate session startup.
- **CRUD modules:** `modules/patients/`, `modules/doctors/`, `modules/appointments/`, and `modules/medical-records/` contain the four implementations. Appointment and medical-record common helpers live alongside their pages. Module-local includes use `__DIR__`; project-level includes use `dirname(__DIR__, 2)`.
- **Database integration:** PHP uses PDO and the locally configured `$pdo` connection from `config/database.local.php` to access MySQL/MariaDB. The schema remains in `database/hospital_management.sql`. Prepared statements, validation, CSRF checks for login and mutations, HTML escaping, and existing transaction/conflict checks remain in the application.
- **HTTP restrictions:** `includes/.htaccess` and `modules/.htaccess` contain `Require all denied`; all four module folders inherit the latter rule. Existing restrictions also protect `config/` and `database/`. Apache must honor these files. PHP filesystem includes continue to work; users browse the original root URLs rather than internal implementations.

See [Local Setup](docs/LOCAL_SETUP.md), the [Screenshot Guide](docs/SCREENSHOT_GUIDE.md), and the [Security Review](docs/SECURITY_REVIEW.md).

## Run locally

**Requirements:** PHP 8.2 with `pdo_mysql` and `mbstring`, a MySQL/MariaDB server, and Apache with the project's `.htaccess` protection enabled. The steps below use XAMPP on Windows. No Composer or npm installation is required by the current project.

1. Clone or download this repository into the XAMPP web root, e.g. `D:\Projects\xampp\htdocs\hospital-management-system`.
2. Start **Apache** and **MySQL** from XAMPP.
3. Open phpMyAdmin, create a database called `hospital_management`, and import `database/hospital_management.sql` (the schema-only export).
4. Create `config/database.local.php` with your own **local** PDO connection settings; do not commit this file. See [Local Setup](docs/LOCAL_SETUP.md) for a safe example.
5. Create an administrator account locally with a `password_hash()`-generated password hash. The repository intentionally provides **no default administrator credentials**. See [Local Setup](docs/LOCAL_SETUP.md).
6. Visit `http://localhost/hospital-management-system/login.php` and sign in.

The imported schema contains the five main application tables: `users`, `patients`, `doctors`, `appointments`, and `medical_records`. No demo patients or account credentials are bundled.

## Database relationships

- An **appointment** belongs to one patient and one doctor.
- A **medical record** belongs to a patient and can optionally reference a doctor.
- Foreign keys help maintain references between these tables.

```mermaid
flowchart LR
    P[Patients] --> A[Appointments]
    D[Doctors] --> A
    P --> M[Medical Records]
    D -. optional .-> M
```

## Security considerations

The demo includes password hashing/verification, PDO prepared statements, CSRF tokens for state-changing forms, HTML output escaping, authenticated page guards, inactivity timeout, and Apache access restrictions for sensitive folders.

**Known limitations:** Persistent login-attempt throttling has **not** been installed; administrator roles and fine-grained permissions, audit logging, encryption policies, and healthcare-specific retention controls are not implemented. Some demo CRUD operations permanently delete records. Same-time appointment conflict checks are implemented at application level and are not a substitute for database-level concurrency controls. Do not expose this project to the public internet or use real medical data without a substantial security and compliance review.

## Future improvements

- Role-based access control for distinct staff responsibilities
- Login-attempt throttling and stronger account recovery controls
- Audit logs and soft deletion/retention policies for sensitive records
- Pagination, reporting, automated tests, and database-level scheduling safeguards
- Deployment hardening, HTTPS, and secure backup management

## About this project

Built as an undergraduate software development portfolio project to practice PHP, relational SQL, secure form handling, full-stack workflows, and Git-based development.

## License

No open-source license has been selected yet. Until one is added, normal copyright rules apply. Choose a license deliberately before public release.
