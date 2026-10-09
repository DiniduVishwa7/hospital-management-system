<?php

// Use the same secure session settings as the MediCore dashboard.
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax"
]);

session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");

// Doctors are visible only to signed-in administrators.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/config/database.local.php";

$pageTitle = "Doctors";

if (empty($_SESSION["logout_token"])) {
    $_SESSION["logout_token"] = bin2hex(random_bytes(32));
}

// Load doctor records using the existing doctors table.
$statement = $pdo->query(
    "SELECT doctor_id, full_name, specialization, phone, email
     FROM doctors
     ORDER BY doctor_id DESC"
);
$doctors = $statement->fetchAll(PDO::FETCH_ASSOC);
$totalDoctors = count($doctors);

// Show a one-time confirmation after creating, updating or deleting a doctor.
$success = $_SESSION["doctor_success"] ?? "";
unset($_SESSION["doctor_success"]);

function escapeHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeHtml($pageTitle); ?> | MediCore</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Existing MediCore stylesheet; no new CSS required -->
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
            <li><a href="index.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a></li>
            <li><a href="patients.php"><i class="bi bi-people-fill"></i> Patients</a></li>
            <li><a href="doctors.php" class="active"><i class="bi bi-person-badge-fill"></i> Doctors</a></li>
            <li><a href="appointments.php"><i class="bi bi-calendar2-check-fill"></i> Appointments</a></li>
            <li><a href="medical_records.php"><i class="bi bi-file-earmark-medical-fill"></i> Medical Records</a></li>
            <li>
                <form action="logout.php" method="POST">
                    <input
                        type="hidden"
                        name="logout_token"
                        value="<?php echo escapeHtml($_SESSION["logout_token"]); ?>"
                    >
                    <button type="submit" class="logout-button">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </li>
        </ul>
    </aside>

    <!-- Main content -->
    <div class="content-area">
        <header class="topbar">
            <div>
                <h2 class="page-title">Doctors</h2>
                <p class="page-subtitle mb-0">View and manage hospital doctor information.</p>
            </div>
            <div class="admin-badge">
                <i class="bi bi-person-circle"></i>
                <span>Admin</span>
            </div>
        </header>

        <?php if (in_array($success, ["Doctor added successfully!", "Doctor updated successfully!", "Doctor deleted successfully!"], true)): ?>
            <div class="alert alert-success" role="status">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo escapeHtml($success); ?>
            </div>
        <?php endif; ?>

        <section class="panel-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h5 class="panel-title mb-1">Doctor Directory</h5>
                    <p class="text-muted mb-0">
                        Total doctors: <?php echo (int) $totalDoctors; ?>
                    </p>
                </div>

                <!-- Open the Add New Doctor form. -->
                <a href="add_doctor.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Doctor
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Specialization</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($doctors)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="bi bi-person-badge text-secondary fs-1"></i>
                                    <h6 class="mt-3">No doctors registered yet</h6>
                                    <p class="text-muted mb-0">
                                        New doctors will appear here once they are added.
                                    </p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($doctors as $doctor): ?>
                                <tr>
                                    <td><?php echo (int) $doctor["doctor_id"]; ?></td>
                                    <td><?php echo escapeHtml($doctor["full_name"]); ?></td>
                                    <td><?php echo escapeHtml($doctor["specialization"]); ?></td>
                                    <td><?php echo escapeHtml($doctor["phone"] ?: "-"); ?></td>
                                    <td style="overflow-wrap: anywhere;">
                                        <?php echo escapeHtml($doctor["email"] ?: "-"); ?>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="edit_doctor.php?id=<?php echo (int) $doctor["doctor_id"]; ?>"
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                            </a>
                                            <a href="delete_doctor.php?id=<?php echo (int) $doctor["doctor_id"]; ?>"
                                               class="btn btn-outline-danger btn-sm">
                                                <i class="bi bi-trash me-1"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
</body>
</html>
