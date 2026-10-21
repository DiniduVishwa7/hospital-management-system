<?php
// Central authentication/session setup shared by MediCore protected pages.
require_once MEDICORE_APP_PATH . '/shared/auth_guard.php';

require_once MEDICORE_ROOT . "/config/database.local.php";

function escapeHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

if (!in_array($_SERVER["REQUEST_METHOD"], ["GET", "POST"], true)) {
    header("Allow: GET, POST");
    http_response_code(405);
    exit("Method not allowed.");
}

// GET only displays the confirmation. POST performs the actual deletion.
$source = $_SERVER["REQUEST_METHOD"] === "POST" ? INPUT_POST : INPUT_GET;
$patientId = filter_input($source, "patient_id", FILTER_VALIDATE_INT, [
    "options" => ["min_range" => 1]
]);

// The patient directory supplies ?route=patients/delete&id=123.
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $patientId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 1]
    ]);
}

if ($patientId === false || $patientId === null) {
    http_response_code(400);
    exit('Invalid patient ID. <a href="' . escapeHtml(medicore_url('patients')) . '">Return to Patients</a>');
}

// Read the patient name from the database (not from a URL/form field).
$statement = $pdo->prepare(
    "SELECT patient_id, full_name, date_of_birth, gender, phone
     FROM patients
     WHERE patient_id = :patient_id
     LIMIT 1"
);
$statement->execute(["patient_id" => $patientId]);
$patient = $statement->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    http_response_code(404);
    exit('Patient not found. <a href="' . escapeHtml(medicore_url('patients')) . '">Return to Patients</a>');
}

// Foreign keys prevent deleting a patient who has related records.
// Show this before confirmation, and also handle database restrictions below.
$relatedStatement = $pdo->prepare(
    "SELECT
        (SELECT COUNT(*) FROM appointments WHERE patient_id = :appointments_patient_id) AS appointments_count,
        (SELECT COUNT(*) FROM medical_records WHERE patient_id = :records_patient_id) AS records_count"
);
$relatedStatement->execute([
    "appointments_patient_id" => $patientId,
    "records_patient_id" => $patientId
]);
$related = $relatedStatement->fetch(PDO::FETCH_ASSOC);
$hasRelatedRecords = (int) $related["appointments_count"] > 0
    || (int) $related["records_count"] > 0;

if (empty($_SESSION["logout_token"])) {
    $_SESSION["logout_token"] = bin2hex(random_bytes(32));
}

if (empty($_SESSION["delete_patient_csrf_token"])) {
    $_SESSION["delete_patient_csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $submittedToken = $_POST["delete_patient_csrf_token"] ?? "";
    $confirmed = $_POST["confirm_delete"] ?? "";

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION["delete_patient_csrf_token"], $submittedToken)
    ) {
        http_response_code(403);
        $error = "Invalid confirmation request. Please refresh and try again.";
    } elseif ($confirmed !== "yes") {
        $error = "Please confirm that you understand this action cannot be undone.";
    } elseif ($hasRelatedRecords) {
        $error = "This patient cannot be deleted while appointments or medical records are linked to them.";
    } else {
        try {
            $delete = $pdo->prepare(
                "DELETE FROM patients WHERE patient_id = :patient_id"
            );
            $delete->execute(["patient_id" => $patientId]);

            if ($delete->rowCount() !== 1) {
                $error = "Patient was not deleted. Please return to the directory and try again.";
            } else {
                // One-time success message; redirect prevents form resubmission.
                $_SESSION["patient_success"] = "Patient deleted successfully!";
                unset($_SESSION["delete_patient_csrf_token"]);
                medicore_redirect('patients');
                exit;
            }
        } catch (PDOException $e) {
            // SQLSTATE 23000: referenced by appointments or medical records.
            if ($e->getCode() === "23000") {
                $error = "This patient has linked records and cannot be deleted.";
            } else {
                error_log("Patient delete failed: " . $e->getMessage());
                $error = "Unable to delete patient. Please try again.";
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Patient | MediCore</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Existing MediCore styles -->
    <link rel="stylesheet" href="<?php echo escapeHtml(medicore_asset('css/style.css') . '?v=3'); ?>">
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
            <li><a href="<?php echo escapeHtml(medicore_url('dashboard')); ?>"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a></li>
            <li><a href="<?php echo escapeHtml(medicore_url('patients')); ?>" class="active"><i class="bi bi-people-fill"></i> Patients</a></li>
            <li><a aria-disabled="true" role="link" tabindex="-1" title="Currently unavailable"><i class="bi bi-person-badge-fill"></i> Doctors</a></li>
            <li><a aria-disabled="true" role="link" tabindex="-1" title="Currently unavailable"><i class="bi bi-calendar2-check-fill"></i> Appointments</a></li>
            <li><a aria-disabled="true" role="link" tabindex="-1" title="Currently unavailable"><i class="bi bi-file-earmark-medical-fill"></i> Medical Records</a></li>
            <li>
                <form action="<?php echo escapeHtml(medicore_url('logout')); ?>" method="POST">
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
                <h2 class="page-title">Delete Patient</h2>
                <p class="page-subtitle mb-0">Review the patient before removing their record.</p>
            </div>
            <div class="admin-badge">
                <i class="bi bi-person-circle"></i>
                <span>Admin</span>
            </div>
        </header>

        <section class="panel-card patient-form-card">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="patient-form-icon text-danger bg-danger-subtle">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <h5 class="panel-title mb-1">Confirm Patient Deletion</h5>
                    <p class="text-muted mb-0">Check carefully before continuing.</p>
                </div>
            </div>

            <div class="alert alert-warning" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Warning:</strong> Deleting a patient is permanent and cannot be undone.
                Only use fictional test records during development.
            </div>

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-x-circle me-2"></i>
                    <?php echo escapeHtml($error); ?>
                </div>
            <?php endif; ?>

            <div class="border rounded-3 p-4 mb-4 bg-light">
                <h6 class="fw-bold mb-3">Patient to delete</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Patient ID</dt>
                    <dd class="col-sm-8"><?php echo (int) $patient["patient_id"]; ?></dd>

                    <dt class="col-sm-4">Full Name</dt>
                    <dd class="col-sm-8"><?php echo escapeHtml($patient["full_name"]); ?></dd>

                    <dt class="col-sm-4">Date of Birth</dt>
                    <dd class="col-sm-8"><?php echo escapeHtml($patient["date_of_birth"]); ?></dd>

                    <dt class="col-sm-4">Gender</dt>
                    <dd class="col-sm-8 mb-0"><?php echo escapeHtml($patient["gender"]); ?></dd>
                </dl>
            </div>

            <?php if ($hasRelatedRecords): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-shield-exclamation me-2"></i>
                    This patient has
                    <strong><?php echo (int) $related["appointments_count"]; ?> appointment(s)</strong> and
                    <strong><?php echo (int) $related["records_count"]; ?> medical record(s)</strong>.
                    Their profile cannot be deleted while these records exist.
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo escapeHtml(medicore_url('patients/delete', ['id' => $patientId])); ?>">
                <input type="hidden" name="patient_id" value="<?php echo (int) $patientId; ?>">
                <input
                    type="hidden"
                    name="delete_patient_csrf_token"
                    value="<?php echo escapeHtml($_SESSION["delete_patient_csrf_token"]); ?>"
                >

                <div class="form-check mb-4">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="confirm_delete"
                        value="yes"
                        id="confirm_delete"
                        required
                        <?php echo $hasRelatedRecords ? "disabled" : ""; ?>
                    >
                    <label class="form-check-label" for="confirm_delete">
                        I understand this patient's record will be permanently deleted.
                    </label>
                </div>

                <div class="d-flex justify-content-end gap-3 flex-wrap">
                    <a href="<?php echo escapeHtml(medicore_url('patients')); ?>" class="btn btn-outline-secondary">Cancel — Keep Patient</a>
                    <button
                        type="submit"
                        class="btn btn-danger"
                        <?php echo $hasRelatedRecords ? "disabled" : ""; ?>
                    >
                        <i class="bi bi-trash me-1"></i> Yes, Delete Patient
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
</body>
</html>
