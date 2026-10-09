<?php

// Match the session settings used by the rest of MediCore.
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax"
]);

session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");

// Prevent anonymous access.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/config/database.local.php";

$pageTitle = "Patients";

if (empty($_SESSION["logout_token"])) {
    $_SESSION["logout_token"] = bin2hex(random_bytes(32));
}

// Display the success message once after adding a patient.
$success = $_SESSION["patient_success"] ?? "";
unset($_SESSION["patient_success"]);

// Load actual patient records, newest first.
$statement = $pdo->query(
    "SELECT patient_id, full_name, date_of_birth, gender, phone, address
     FROM patients
     ORDER BY patient_id DESC"
);
$patients = $statement->fetchAll(PDO::FETCH_ASSOC);
$totalPatients = count($patients);

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
    <title>Patients | MediCore</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Existing MediCore styles -->
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
            <li><a href="patients.php" class="active"><i class="bi bi-people-fill"></i> Patients</a></li>
            <li><a href="doctors.php"><i class="bi bi-person-badge-fill"></i> Doctors</a></li>
            <li><a href="#"><i class="bi bi-calendar2-check-fill"></i> Appointments</a></li>
            <li><a href="#"><i class="bi bi-file-earmark-medical-fill"></i> Medical Records</a></li>
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

    <!-- Main Content -->
    <div class="content-area">
        <header class="topbar">
            <div>
                <h2 class="page-title">Patients</h2>
                <p class="page-subtitle mb-0">View and manage hospital patient information.</p>
            </div>
            <div class="admin-badge">
                <i class="bi bi-person-circle"></i>
                <span>Admin</span>
            </div>
        </header>

        <?php if ($success !== ""): ?>
            <div class="alert alert-success" role="status">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo escapeHtml($success); ?>
            </div>
        <?php endif; ?>

        <!-- Patient Directory -->
        <section class="panel-card">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h5 class="panel-title mb-1">Patient Directory</h5>
                    <p class="text-muted mb-0">
                        Total patients: <?php echo (int) $totalPatients; ?>
                    </p>
                </div>
                <a href="add_patient.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add Patient
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Date of Birth</th>
                            <th>Gender</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($patients)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-people text-secondary fs-1"></i>
                                    <h6 class="mt-3">No patients registered yet</h6>
                                    <p class="text-muted mb-0">
                                        New patients will appear here once they are added.
                                    </p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td><?php echo (int) $patient["patient_id"]; ?></td>
                                    <td><?php echo escapeHtml($patient["full_name"]); ?></td>
                                    <td><?php echo escapeHtml($patient["date_of_birth"]); ?></td>
                                    <td><?php echo escapeHtml($patient["gender"]); ?></td>
                                    <td><?php echo escapeHtml($patient["phone"] ?: "-"); ?></td>
                                    <td style="min-width: 170px; max-width: 300px; overflow-wrap: anywhere;">
                                        <?php echo escapeHtml(
                                            trim((string) ($patient["address"] ?? "")) !== ""
                                                ? $patient["address"]
                                                : "-"
                                        ); ?>
                                    </td>
                                    <td>
                                        <a
                                            href="edit_patient.php?id=<?php echo (int) $patient["patient_id"]; ?>"
                                            class="btn btn-sm btn-outline-primary"
                                            aria-label="Edit <?php echo escapeHtml($patient["full_name"]); ?>"
                                        >
                                            <i class="bi bi-pencil-square me-1"></i> Edit
                                        </a>
                                        <a
                                            href="delete_patient.php?id=<?php echo (int) $patient["patient_id"]; ?>"
                                            class="btn btn-sm btn-outline-danger ms-1"
                                            aria-label="Delete <?php echo escapeHtml($patient["full_name"]); ?>"
                                        >
                                            <i class="bi bi-trash me-1"></i> Delete
                                        </a>
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
