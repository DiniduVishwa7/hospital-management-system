<?php
// Central authentication/session setup shared by MediCore protected pages.
require_once __DIR__ . '/includes/auth_guard.php';

require_once __DIR__ . "/config/database.local.php";

function escapeHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

// GET shows the confirmation page. Only a POST may delete anything.
$method = $_SERVER["REQUEST_METHOD"];
if (!in_array($method, ["GET", "POST"], true)) {
    header("Allow: GET, POST");
    http_response_code(405);
    exit("Method not allowed.");
}

$doctorId = $method === "GET"
    ? filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])
    : filter_input(INPUT_POST, "doctor_id", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

if (!is_int($doctorId)) {
    http_response_code(400);
    exit('Invalid doctor ID. <a href="doctors.php">Return to Doctors</a>');
}

// Get the name/details from MySQL, never from untrusted form text.
$getDoctor = $pdo->prepare(
    "SELECT doctor_id, full_name, specialization, phone, email
     FROM doctors
     WHERE doctor_id = :doctor_id
     LIMIT 1"
);
$getDoctor->execute(["doctor_id" => $doctorId]);
$doctor = $getDoctor->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    http_response_code(404);
    exit('Doctor not found. <a href="doctors.php">Return to Doctors</a>');
}

if (empty($_SESSION["logout_token"])) {
    $_SESSION["logout_token"] = bin2hex(random_bytes(32));
}

if (empty($_SESSION["delete_doctor_csrf_token"])) {
    $_SESSION["delete_doctor_csrf_token"] = bin2hex(random_bytes(32));
}

// Refuse deletion if linked appointments or medical records exist.
// In the current schema, deleting a doctor could otherwise set the
// doctor_id of existing medical records to NULL. Protect those links.
$checkRelated = $pdo->prepare(
    "SELECT
        (SELECT COUNT(*) FROM appointments WHERE doctor_id = :appointment_doctor_id) AS appointments_count,
        (SELECT COUNT(*) FROM medical_records WHERE doctor_id = :record_doctor_id) AS records_count"
);
$checkRelated->execute([
    "appointment_doctor_id" => $doctorId,
    "record_doctor_id" => $doctorId
]);
$related = $checkRelated->fetch(PDO::FETCH_ASSOC);
$hasRelatedRecords = (int) $related["appointments_count"] > 0
    || (int) $related["records_count"] > 0;

$error = "";

if ($method === "POST") {
    $submittedToken = $_POST["delete_doctor_csrf_token"] ?? "";
    $confirmed = $_POST["confirm_delete"] ?? "";

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION["delete_doctor_csrf_token"], $submittedToken)
    ) {
        http_response_code(403);
        $error = "Invalid confirmation request. Please refresh and try again.";
    } elseif ($confirmed !== "yes") {
        $error = "Please tick the confirmation checkbox before deleting.";
    } elseif ($hasRelatedRecords) {
        $error = "This doctor cannot be deleted while appointments or medical records are linked to them.";
    } else {
        try {
            // Lock the doctor row so related records cannot be inserted
            // while we re-check dependencies and perform the deletion.
            $pdo->beginTransaction();

            $lockDoctor = $pdo->prepare(
                "SELECT doctor_id FROM doctors WHERE doctor_id = :doctor_id FOR UPDATE"
            );
            $lockDoctor->execute(["doctor_id" => $doctorId]);

            if (!$lockDoctor->fetch(PDO::FETCH_ASSOC)) {
                $pdo->rollBack();
                $error = "Doctor no longer exists. Return to the directory.";
            } else {
                $checkRelated->execute([
                    "appointment_doctor_id" => $doctorId,
                    "record_doctor_id" => $doctorId
                ]);
                $latestRelated = $checkRelated->fetch(PDO::FETCH_ASSOC);

                if (
                    (int) $latestRelated["appointments_count"] > 0 ||
                    (int) $latestRelated["records_count"] > 0
                ) {
                    $pdo->rollBack();
                    $hasRelatedRecords = true;
                    $related = $latestRelated;
                    $error = "This doctor now has linked appointments or medical records and cannot be deleted.";
                } else {
                    $delete = $pdo->prepare(
                        "DELETE FROM doctors WHERE doctor_id = :doctor_id"
                    );
                    $delete->execute(["doctor_id" => $doctorId]);

                    if ($delete->rowCount() !== 1) {
                        $pdo->rollBack();
                        $error = "Doctor was not deleted. Please try again.";
                    } else {
                        $pdo->commit();
                        unset($_SESSION["delete_doctor_csrf_token"]);
                        $_SESSION["doctor_success"] = "Doctor deleted successfully!";
                        header("Location: doctors.php");
                        exit;
                    }
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // MySQL foreign-key constraint: referenced records still exist.
            if ($e->getCode() === "23000") {
                $error = "This doctor has linked records and cannot be deleted.";
            } else {
                error_log("Doctor delete failed: " . $e->getMessage());
                $error = "Unable to delete doctor. Please try again.";
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
    <title>Delete Doctor | MediCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=3">
</head>
<body>
<div class="main-wrapper">
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
            <li><a href="#"><i class="bi bi-calendar2-check-fill"></i> Appointments</a></li>
            <li><a href="#"><i class="bi bi-file-earmark-medical-fill"></i> Medical Records</a></li>
            <li>
                <form action="logout.php" method="POST">
                    <input type="hidden" name="logout_token"
                           value="<?php echo escapeHtml($_SESSION["logout_token"]); ?>">
                    <button type="submit" class="logout-button">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </li>
        </ul>
    </aside>

    <div class="content-area">
        <header class="topbar">
            <div>
                <h2 class="page-title">Delete Doctor</h2>
                <p class="page-subtitle mb-0">Review the doctor before removing their record.</p>
            </div>
            <div class="admin-badge">
                <i class="bi bi-person-circle"></i> <span>Admin</span>
            </div>
        </header>

        <section class="panel-card patient-form-card">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="patient-form-icon text-danger bg-danger-subtle">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <h5 class="panel-title mb-1">Confirm Doctor Deletion</h5>
                    <p class="text-muted mb-0">Check the doctor information carefully before continuing.</p>
                </div>
            </div>

            <div class="alert alert-warning" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Warning:</strong> This action permanently deletes the doctor profile.
                Only delete fictional test records during development.
            </div>

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-x-circle me-2"></i>
                    <?php echo escapeHtml($error); ?>
                </div>
            <?php endif; ?>

            <div class="border rounded-3 p-4 mb-4 bg-light">
                <h6 class="fw-bold mb-3">Doctor to delete</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Doctor ID</dt>
                    <dd class="col-sm-8"><?php echo (int) $doctor["doctor_id"]; ?></dd>

                    <dt class="col-sm-4">Full Name</dt>
                    <dd class="col-sm-8"><?php echo escapeHtml($doctor["full_name"]); ?></dd>

                    <dt class="col-sm-4">Specialization</dt>
                    <dd class="col-sm-8"><?php echo escapeHtml($doctor["specialization"]); ?></dd>

                    <dt class="col-sm-4">Phone</dt>
                    <dd class="col-sm-8"><?php echo escapeHtml($doctor["phone"] ?: "-"); ?></dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8 mb-0"><?php echo escapeHtml($doctor["email"] ?: "-"); ?></dd>
                </dl>
            </div>

            <?php if ($hasRelatedRecords): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-shield-exclamation me-2"></i>
                    This doctor is linked to
                    <strong><?php echo (int) $related["appointments_count"]; ?> appointment(s)</strong> and
                    <strong><?php echo (int) $related["records_count"]; ?> medical record(s)</strong>.
                    Deletion is disabled to protect linked data.
                </div>
            <?php endif; ?>

            <form method="POST" action="delete_doctor.php">
                <input type="hidden" name="doctor_id" value="<?php echo (int) $doctorId; ?>">
                <input type="hidden" name="delete_doctor_csrf_token"
                       value="<?php echo escapeHtml($_SESSION["delete_doctor_csrf_token"]); ?>">

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox"
                           name="confirm_delete" value="yes" id="confirm_delete"
                           required <?php echo $hasRelatedRecords ? "disabled" : ""; ?>>
                    <label class="form-check-label" for="confirm_delete">
                        I understand this doctor's record will be permanently deleted.
                    </label>
                </div>

                <div class="d-flex justify-content-end gap-3 flex-wrap">
                    <a href="doctors.php" class="btn btn-outline-secondary">Cancel — Keep Doctor</a>
                    <button type="submit" class="btn btn-danger"
                            <?php echo $hasRelatedRecords ? "disabled" : ""; ?>>
                        <i class="bi bi-trash me-1"></i> Yes, Delete Doctor
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
</body>
</html>
