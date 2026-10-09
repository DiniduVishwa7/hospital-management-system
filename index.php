<?php
$pageTitle = "Dashboard";

// Temporary stats until database connection
$totalPatients = 128;
$totalDoctors = 24;
$totalAppointments = 56;
$todayAppointments = 12;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Hospital Management System</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="main-wrapper">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">
                <i class="bi bi-hospital"></i>
            </div>
            <div>
                <h4 class="mb-0">MediCore</h4>
                <small>Hospital System</small>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li><a href="index.php" class="active"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a></li>
            <li><a href="#"><i class="bi bi-people-fill"></i> Patients</a></li>
            <li><a href="#"><i class="bi bi-person-badge-fill"></i> Doctors</a></li>
            <li><a href="#"><i class="bi bi-calendar2-check-fill"></i> Appointments</a></li>
            <li><a href="#"><i class="bi bi-file-earmark-medical-fill"></i> Medical Records</a></li>
            <li><a href="#"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
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
                        <div class="stat-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <h6>Total Patients</h6>
                            <h3><?php echo $totalPatients; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="stat-card stat-green">
                        <div class="stat-icon">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                        <div>
                            <h6>Total Doctors</h6>
                            <h3><?php echo $totalDoctors; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="stat-card stat-orange">
                        <div class="stat-icon">
                            <i class="bi bi-calendar2-check-fill"></i>
                        </div>
                        <div>
                            <h6>Total Appointments</h6>
                            <h3><?php echo $totalAppointments; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="stat-card stat-red">
                        <div class="stat-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <h6>Today's Appointments</h6>
                            <h3><?php echo $todayAppointments; ?></h3>
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
                                        <td>John Perera</td>
                                        <td>Dr. Silva</td>
                                        <td>2026-10-08</td>
                                        <td><span class="badge bg-success">Confirmed</span></td>
                                    </tr>
                                    <tr>
                                        <td>Nadeesha Fernando</td>
                                        <td>Dr. Jayasinghe</td>
                                        <td>2026-10-08</td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                    </tr>
                                    <tr>
                                        <td>Amal Peris</td>
                                        <td>Dr. Perera</td>
                                        <td>2026-10-09</td>
                                        <td><span class="badge bg-primary">Scheduled</span></td>
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