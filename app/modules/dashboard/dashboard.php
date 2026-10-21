<?php
// Central authentication/session setup shared by MediCore protected pages.
require_once MEDICORE_APP_PATH . '/shared/auth_guard.php';

require_once MEDICORE_ROOT . "/config/database.local.php";

$pageTitle = "Dashboard";
$totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$totalDoctors = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$totalAppointments = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$todayAppointments = $pdo->query(
    "SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()"
)->fetchColumn();

// Show the five appointments most recently added to MediCore.
// Patient and doctor names come directly from the database.
$recentStatement = $pdo->query(
    "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status,
            p.full_name AS patient_name,
            d.full_name AS doctor_name
     FROM appointments AS a
     INNER JOIN patients AS p ON p.patient_id = a.patient_id
     INNER JOIN doctors AS d ON d.doctor_id = a.doctor_id
     ORDER BY a.created_at DESC, a.appointment_id DESC
     LIMIT 5"
);
$recentAppointments = $recentStatement->fetchAll(PDO::FETCH_ASSOC);

// Escape all data printed into HTML, including database-sourced names.
function dashboardEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Match the appointment statuses used by the booking module.
function dashboardStatusClass(string $status): string
{
    return match ($status) {
        'Scheduled' => 'text-bg-primary',
        'Completed' => 'text-bg-success',
        'Cancelled' => 'text-bg-secondary',
        default => 'text-bg-secondary',
    };
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, "UTF-8"); ?> | Hospital Management System</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- MediCore styles -->
    <link rel="stylesheet" href="<?php echo dashboardEscape(medicore_asset('css/style.css') . '?v=3'); ?>">
</head>
<body>
<div class="main-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon"><i class="bi bi-hospital"></i></div>
            <div>
                <h4 class="mb-0">MediCore</h4>
                <small>Hospital System</small>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li><a href="<?php echo dashboardEscape(medicore_url('dashboard')); ?>" class="active"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a></li>
            <li><a href="<?php echo dashboardEscape(medicore_url('patients')); ?>"><i class="bi bi-people-fill"></i> Patients</a></li>
            <li><a aria-disabled="true" role="link" tabindex="-1" title="Currently unavailable"><i class="bi bi-person-badge-fill"></i> Doctors</a></li>
            <li><a aria-disabled="true" role="link" tabindex="-1" title="Currently unavailable"><i class="bi bi-calendar2-check-fill"></i> Appointments</a></li>
            <li><a aria-disabled="true" role="link" tabindex="-1" title="Currently unavailable"><i class="bi bi-file-earmark-medical-fill"></i> Medical Records</a></li>
            <li>
                <form action="<?php echo dashboardEscape(medicore_url('logout')); ?>" method="POST">
                    <input
                        type="hidden"
                        name="logout_token"
                        value="<?php echo htmlspecialchars($_SESSION["logout_token"], ENT_QUOTES, "UTF-8"); ?>"
                    >
                    <button type="submit" class="logout-button">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </li>
        </ul>
    </aside>

    <!-- Content -->
    <div class="content-area">
        <!-- Topbar -->
        <header class="topbar">
            <div>
                <h2 class="page-title">Dashboard</h2>
                <p class="page-subtitle mb-0">Welcome back to your hospital management workspace.</p>
            </div>
            <div class="topbar-right">
                <div class="admin-badge">
                    <i class="bi bi-person-circle"></i>
                    <span>Admin</span>
                </div>
            </div>
        </header>

        <!-- Hero Banner -->
        <section class="hero-banner">
            <div class="hero-overlay">
                <div class="hero-text">
                    <h1>Modern Hospital Administration Dashboard</h1>
                    <p>
                        Manage patients, doctors, appointments, and medical records
                        from one clean and professional system.
                    </p>
                    <a aria-disabled="true" role="link" tabindex="-1" title="Currently unavailable" class="btn btn-light hero-btn">View Appointments</a>
                </div>
            </div>
        </section>

        <!-- Statistics -->
        <section class="stats-section">
            <div class="row g-4">
                <div class="col-md-6 col-xl-3">
                    <div class="stat-card stat-blue">
                        <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <h6>Total Patients</h6>
                            <h3><?php echo (int) $totalPatients; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="stat-card stat-green">
                        <div class="stat-icon"><i class="bi bi-person-badge-fill"></i></div>
                        <div>
                            <h6>Total Doctors</h6>
                            <h3><?php echo (int) $totalDoctors; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="stat-card stat-orange">
                        <div class="stat-icon"><i class="bi bi-calendar2-check-fill"></i></div>
                        <div>
                            <h6>Total Appointments</h6>
                            <h3><?php echo (int) $totalAppointments; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="stat-card stat-red">
                        <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <h6>Today's Appointments</h6>
                            <h3><?php echo (int) $todayAppointments; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Dashboard: real recent appointments and quick actions -->
        <section class="dashboard-panels mt-4">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="panel-card">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div>
                                <h5 class="panel-title mb-1">Recent Appointments</h5>
                                <p class="text-muted small mb-0">The five most recently added bookings</p>
                            </div>
                            <a aria-disabled="true" role="link" tabindex="-1" title="Currently unavailable" class="btn btn-outline-primary btn-sm">
                                View all <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">Patient</th>
                                        <th scope="col">Doctor</th>
                                        <th scope="col">Date &amp; Time</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentAppointments)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5">
                                                <i class="bi bi-calendar-x text-secondary fs-2" aria-hidden="true"></i>
                                                <p class="text-muted mt-3 mb-1">No appointments available yet.</p>
                                                <a aria-disabled="true" role="link" tabindex="-1" title="Currently unavailable" class="btn btn-primary btn-sm mt-2">
                                                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add Appointment
                                                </a>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentAppointments as $appointment): ?>
                                            <tr>
                                                <td><?php echo dashboardEscape($appointment['patient_name']); ?></td>
                                                <td><?php echo dashboardEscape($appointment['doctor_name']); ?></td>
                                                <td>
                                                    <span class="d-block text-nowrap"><?php
                                                        echo dashboardEscape(date('d M Y', strtotime($appointment['appointment_date'])));
                                                    ?></span>
                                                    <small class="text-muted"><?php
                                                        echo dashboardEscape(date('g:i A', strtotime($appointment['appointment_time'])));
                                                    ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo dashboardStatusClass((string) $appointment['status']); ?>">
                                                        <?php echo dashboardEscape($appointment['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="panel-card quick-panel">
                        <h5 class="panel-title">Quick Actions</h5>
                        <a aria-disabled="true" role="link" tabindex="-1" title="Currently unavailable" class="quick-item text-decoration-none">
                            <i class="bi bi-calendar-plus-fill" aria-hidden="true"></i>
                            <div>
                                <h6 class="text-dark">Book Appointment</h6>
                                <p>Schedule a visit for a patient.</p>
                            </div>
                        </a>
                        <a href="<?php echo dashboardEscape(medicore_url('patients/create')); ?>" class="quick-item text-decoration-none">
                            <i class="bi bi-person-plus-fill" aria-hidden="true"></i>
                            <div>
                                <h6 class="text-dark">Add Patient</h6>
                                <p>Register a new patient.</p>
                            </div>
                        </a>
                        <a aria-disabled="true" role="link" tabindex="-1" title="Currently unavailable" class="quick-item text-decoration-none">
                            <i class="bi bi-person-badge-fill" aria-hidden="true"></i>
                            <div>
                                <h6 class="text-dark">Add Doctor</h6>
                                <p>Register a new doctor.</p>
                            </div>
                        </a>
                        <a aria-disabled="true" role="link" tabindex="-1" title="Currently unavailable" class="quick-item text-decoration-none">
                            <i class="bi bi-file-earmark-medical-fill" aria-hidden="true"></i>
                            <div>
                                <h6 class="text-dark">Medical Records</h6>
                                <p>Review registered medical records.</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
</body>
</html>
