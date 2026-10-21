<?php
// Central authentication/session setup shared by MediCore protected pages.
require_once MEDICORE_APP_PATH . '/shared/auth_guard.php';

require_once MEDICORE_ROOT . "/config/database.local.php";

$pageTitle = "Add New Patient";
$today = date("Y-m-d");
$error = "";

// Safely display form values inside HTML.
function escapeHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

// Separate CSRF tokens for the patient form and logout form.
if (empty($_SESSION["logout_token"])) {
    $_SESSION["logout_token"] = bin2hex(random_bytes(32));
}

if (empty($_SESSION["patient_csrf_token"])) {
    $_SESSION["patient_csrf_token"] = bin2hex(random_bytes(32));
}

// Keep entered values if validation fails.
$form = [
    "full_name" => "",
    "date_of_birth" => "",
    "gender" => "",
    "phone" => "",
    "address" => ""
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach (array_keys($form) as $field) {
        $value = $_POST[$field] ?? "";
        $form[$field] = is_string($value) ? trim($value) : "";
    }

    $token = $_POST["patient_csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["patient_csrf_token"], $token)
    ) {
        $error = "Invalid form request. Please refresh and try again.";
    } elseif (
        $form["full_name"] === "" ||
        mb_strlen($form["full_name"], "UTF-8") > 100
    ) {
        $error = "Please enter a full name (maximum 100 characters).";
    } else {
        // Checking format and calendar validity also rejects future dates.
        $dateText = $form["date_of_birth"];
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $dateText)
            ? DateTimeImmutable::createFromFormat("!Y-m-d", $dateText)
            : false;

        if (
            $date === false ||
            $date->format("Y-m-d") !== $dateText ||
            $dateText > $today
        ) {
            $error = "Please enter a valid date of birth.";
        }
    }

    if (
        $error === "" &&
        !in_array($form["gender"], ["Male", "Female", "Other"], true)
    ) {
        $error = "Please select a valid gender.";
    }

    if (
        $error === "" &&
        $form["phone"] !== "" &&
        !preg_match('/^[0-9+()\s-]{7,20}$/D', $form["phone"])
    ) {
        $error = "Please enter a valid phone number.";
    }

    if (
        $error === "" &&
        mb_strlen($form["address"], "UTF-8") > 2000
    ) {
        $error = "Address must not exceed 2000 characters.";
    }

    if ($error === "") {
        try {
            $sql = "INSERT INTO patients
                    (full_name, date_of_birth, gender, phone, address)
                    VALUES
                    (:full_name, :date_of_birth, :gender, :phone, :address)";

            $statement = $pdo->prepare($sql);
            $statement->execute([
                "full_name" => $form["full_name"],
                "date_of_birth" => $form["date_of_birth"],
                "gender" => $form["gender"],
                "phone" => $form["phone"] !== "" ? $form["phone"] : null,
                "address" => $form["address"] !== "" ? $form["address"] : null
            ]);

            // The success message can only appear after a real insert.
            $_SESSION["patient_success"] = "Patient added successfully!";
            unset($_SESSION["patient_csrf_token"]);

            // Redirecting stops the browser from resubmitting on refresh.
            medicore_redirect('patients');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Unable to save patient. Please try again.";
        }
    }
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
                <h2 class="page-title">Add New Patient</h2>
                <p class="page-subtitle mb-0">Register a patient in the hospital system.</p>
            </div>
            <div class="admin-badge">
                <i class="bi bi-person-circle"></i>
                <span>Admin</span>
            </div>
        </header>

        <section class="panel-card patient-form-card">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="patient-form-icon">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <div>
                    <h5 class="panel-title mb-1">Patient Information</h5>
                    <p class="text-muted mb-0">Fields marked * are required.</p>
                </div>
            </div>

            <hr class="mb-4">

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php echo escapeHtml($error); ?>
                </div>
            <?php endif; ?>

            <form id="patientForm" action="<?php echo escapeHtml(medicore_url('patients/create')); ?>" method="POST" autocomplete="off">
                <input
                    type="hidden"
                    name="patient_csrf_token"
                    value="<?php echo escapeHtml($_SESSION["patient_csrf_token"]); ?>"
                >

                <div class="row g-4">
                    <div class="col-12">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            class="form-control"
                            placeholder="Enter patient's full name"
                            maxlength="100"
                            value="<?php echo escapeHtml($form["full_name"]); ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label for="date_of_birth" class="form-label">Date of Birth *</label>
                        <input
                            type="date"
                            id="date_of_birth"
                            name="date_of_birth"
                            class="form-control"
                            max="<?php echo escapeHtml($today); ?>"
                            value="<?php echo escapeHtml($form["date_of_birth"]); ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label for="gender" class="form-label">Gender *</label>
                        <select id="gender" name="gender" class="form-select" required>
                            <option value="">Select gender</option>
                            <option value="Male" <?php echo $form["gender"] === "Male" ? "selected" : ""; ?>>Male</option>
                            <option value="Female" <?php echo $form["gender"] === "Female" ? "selected" : ""; ?>>Female</option>
                            <option value="Other" <?php echo $form["gender"] === "Other" ? "selected" : ""; ?>>Other</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            class="form-control"
                            placeholder="Enter contact number"
                            maxlength="20"
                            value="<?php echo escapeHtml($form["phone"]); ?>"
                        >
                    </div>

                    <div class="col-12">
                        <label for="address" class="form-label">Address</label>
                        <textarea
                            id="address"
                            name="address"
                            class="form-control"
                            rows="4"
                            maxlength="2000"
                            placeholder="Enter patient's address"
                        ><?php echo escapeHtml($form["address"]); ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-4 flex-wrap">
                    <a href="<?php echo escapeHtml(medicore_url('patients')); ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i> Save Patient
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
</body>
</html>
