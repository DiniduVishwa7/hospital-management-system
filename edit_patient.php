<?php

// Match the session configuration used across MediCore.
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax"
]);

session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");

// A signed-in administrator is required to edit patient data.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/config/database.local.php";

$pageTitle = "Edit Patient";
$today = date("Y-m-d");
$error = "";

function escapeHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

// Only accept a positive integer patient ID in the URL.
$patientId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT, [
    "options" => ["min_range" => 1]
]);

if ($patientId === false || $patientId === null) {
    http_response_code(400);
    exit('Invalid patient ID. <a href="patients.php">Return to Patients</a>');
}

// Load the record to edit; do not trust values supplied by the browser.
$statement = $pdo->prepare(
    "SELECT patient_id, full_name, date_of_birth, gender, phone, address
     FROM patients
     WHERE patient_id = :patient_id
     LIMIT 1"
);
$statement->execute(["patient_id" => $patientId]);
$patient = $statement->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    http_response_code(404);
    exit('Patient not found. <a href="patients.php">Return to Patients</a>');
}

if (empty($_SESSION["logout_token"])) {
    $_SESSION["logout_token"] = bin2hex(random_bytes(32));
}

if (empty($_SESSION["edit_patient_csrf_token"])) {
    $_SESSION["edit_patient_csrf_token"] = bin2hex(random_bytes(32));
}

// The form is populated with the existing patient details on first load.
$form = [
    "full_name" => (string) $patient["full_name"],
    "date_of_birth" => (string) $patient["date_of_birth"],
    "gender" => (string) $patient["gender"],
    "phone" => (string) ($patient["phone"] ?? ""),
    "address" => (string) ($patient["address"] ?? "")
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Preserve what the administrator typed if a field is invalid.
    foreach (array_keys($form) as $field) {
        $value = $_POST[$field] ?? "";
        $form[$field] = is_string($value) ? trim($value) : "";
    }

    $token = $_POST["edit_patient_csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["edit_patient_csrf_token"], $token)
    ) {
        $error = "Invalid form request. Please refresh and try again.";
    } elseif (
        $form["full_name"] === "" ||
        mb_strlen($form["full_name"], "UTF-8") > 100
    ) {
        $error = "Please enter a full name (maximum 100 characters).";
    } else {
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
            // Only UPDATE the selected patient: never INSERT a second record.
            $update = $pdo->prepare(
                "UPDATE patients
                 SET full_name = :full_name,
                     date_of_birth = :date_of_birth,
                     gender = :gender,
                     phone = :phone,
                     address = :address
                 WHERE patient_id = :patient_id"
            );

            $update->execute([
                "full_name" => $form["full_name"],
                "date_of_birth" => $form["date_of_birth"],
                "gender" => $form["gender"],
                "phone" => $form["phone"] !== "" ? $form["phone"] : null,
                "address" => $form["address"] !== "" ? $form["address"] : null,
                "patient_id" => $patientId
            ]);

            $_SESSION["patient_success"] = "Patient updated successfully!";
            unset($_SESSION["edit_patient_csrf_token"]);

            // Prevent double submissions when the browser refreshes.
            header("Location: patients.php");
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Unable to update patient. Please try again.";
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
            <li><a href="#"><i class="bi bi-person-badge-fill"></i> Doctors</a></li>
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
                <h2 class="page-title">Edit Patient</h2>
                <p class="page-subtitle mb-0">Update existing patient information.</p>
            </div>
            <div class="admin-badge">
                <i class="bi bi-person-circle"></i>
                <span>Admin</span>
            </div>
        </header>

        <section class="panel-card patient-form-card">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="patient-form-icon">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <div>
                    <h5 class="panel-title mb-1">Edit Patient Information</h5>
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

            <form id="patientForm" action="edit_patient.php?id=<?php echo (int) $patientId; ?>" method="POST" autocomplete="off">
                <input
                    type="hidden"
                    name="edit_patient_csrf_token"
                    value="<?php echo escapeHtml($_SESSION["edit_patient_csrf_token"]); ?>"
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
                    <a href="patients.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i> Update Patient
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
</body>
</html>
