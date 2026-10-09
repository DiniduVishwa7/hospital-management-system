<?php

// Configure session cookie security.
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax"
]);

session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");

// Only allow authenticated administrators to view the dashboard.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION["logout_token"])) {
    $_SESSION["logout_token"] = bin2hex(random_bytes(32));
}

require_once __DIR__ . "/config/database.local.php";

$pageTitle = "Dashboard";
$totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$totalDoctors = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$totalAppointments = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$todayAppointments = $pdo->query(
    "SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()"
)->fetchColumn();

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
    <link rel="stylesheet" href="assets/css/style.css?v=3">
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
            <li><a href="index.php" class="active"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a></li>
            <li><a href="patients.php"><i class="bi bi-people-fill"></i> Patients</a></li>
            <li><a href="doctors.php"><i class="bi bi-person-badge-fill"></i> Doctors</a></li>
            <li><a href="#"><i class="bi bi-calendar2-check-fill"></i> Appointments</a></li>
            <li><a href="#"><i class="bi bi-file-earmark-medical-fill"></i> Medical Records</a></li>
            <li>
                <form action="logout.php" method="POST">
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
                    <a href="#" class="btn btn-light hero-btn">Explore System</a>
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

        <!-- Bottom Panels -->
        <section class="dashboard-panels mt-4">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="panel-card">
                        <h5 class="panel-title">Recent Appointments</h5>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Doctor</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <i class="bi bi-calendar-x text-secondary fs-2"></i>
                                            <p class="text-muted mt-3 mb-0">No appointments available yet.</p>
                                            <small class="text-muted">New appointments will appear here.</small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="panel-card quick-panel">
                        <h5 class="panel-title">Quick Overview</h5>
                        <div class="quick-item">
                            <i class="bi bi-heart-pulse-fill"></i>
                            <div>
                                <h6>System Status</h6>
                                <p>All core modules are operating normally.</p>
                            </div>
                        </div>
                        <div class="quick-item">
                            <i class="bi bi-hospital-fill"></i>
                            <div>
                                <h6>Hospital Info</h6>
                                <p>General administration dashboard for daily operations.</p>
                            </div>
                        </div>
                        <div class="quick-item">
                            <i class="bi bi-clipboard2-pulse-fill"></i>
                            <div>
                                <h6>Records</h6>
                                <p>Manage patient records and appointments efficiently.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
</body>
</html>
