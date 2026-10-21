<?php
// Central authentication/session setup shared by MediCore protected pages.
require_once dirname(__DIR__, 2) . '/includes/auth_guard.php';

require_once dirname(__DIR__, 2) . "/config/database.local.php";

$pageTitle = "Add New Doctor";
$error = "";

function escapeHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

if (empty($_SESSION["logout_token"])) {
    $_SESSION["logout_token"] = bin2hex(random_bytes(32));
}

// A different token is used for the Add Doctor form.
if (empty($_SESSION["doctor_create_token"])) {
    $_SESSION["doctor_create_token"] = bin2hex(random_bytes(32));
}

// Redisplay values if validation fails.
$form = [
    "full_name" => "",
    "specialization" => "",
    "phone" => "",
    "email" => ""
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Never assume incoming form data is a string.
    foreach (array_keys($form) as $field) {
        $submitted = $_POST[$field] ?? "";
        $form[$field] = is_string($submitted) ? trim($submitted) : "";
    }

    $token = $_POST["doctor_create_token"] ?? "";

    if (
        !is_string($token) ||
        !isset($_SESSION["doctor_create_token"]) ||
        !hash_equals($_SESSION["doctor_create_token"], $token)
    ) {
        $error = "Invalid form request. Please refresh the page and try again.";
    } elseif (
        $form["full_name"] === "" ||
        mb_strlen($form["full_name"], "UTF-8") > 100
    ) {
        $error = "Please enter a doctor's full name (maximum 100 characters).";
    } elseif (
        $form["specialization"] === "" ||
        mb_strlen($form["specialization"], "UTF-8") > 100
    ) {
        $error = "Please enter a specialization (maximum 100 characters).";
    } elseif (
        $form["phone"] !== "" &&
        !preg_match('/^[0-9+()\s-]{7,20}$/D', $form["phone"])
    ) {
        $error = "Please enter a valid phone number.";
    } elseif (
        $form["email"] !== "" &&
        (
            mb_strlen($form["email"], "UTF-8") > 100 ||
            !filter_var($form["email"], FILTER_VALIDATE_EMAIL)
        )
    ) {
        $error = "Please enter a valid email address (maximum 100 characters).";
    }

    if ($error === "") {
        try {
            $statement = $pdo->prepare(
                "INSERT INTO doctors (full_name, specialization, phone, email)
                 VALUES (:full_name, :specialization, :phone, :email)"
            );

            $statement->execute([
                "full_name" => $form["full_name"],
                "specialization" => $form["specialization"],
                "phone" => $form["phone"] !== "" ? $form["phone"] : null,
                "email" => $form["email"] !== "" ? $form["email"] : null
            ]);

            // Show success once, then redirect to avoid duplicate submissions.
            $_SESSION["doctor_success"] = "Doctor added successfully!";
            unset($_SESSION["doctor_create_token"]);
            header("Location: doctors.php");
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Unable to save doctor. Please try again.";
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
    <!-- Reuse the existing MediCore form styles; no CSS change is needed. -->
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
                <h2 class="page-title">Add New Doctor</h2>
                <p class="page-subtitle mb-0">Register a doctor in the hospital system.</p>
            </div>
            <div class="admin-badge">
                <i class="bi bi-person-circle"></i>
                <span>Admin</span>
            </div>
        </header>

        <!-- The existing form card CSS also works for doctor forms. -->
        <section class="panel-card patient-form-card">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="patient-form-icon">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <div>
                    <h5 class="panel-title mb-1">Doctor Information</h5>
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

            <form id="doctorForm" action="add_doctor.php" method="POST" autocomplete="off">
                <input
                    type="hidden"
                    name="doctor_create_token"
                    value="<?php echo escapeHtml($_SESSION["doctor_create_token"]); ?>"
                >
                <div class="row g-4">
                    <div class="col-12">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            class="form-control"
                            placeholder="Enter doctor's full name"
                            maxlength="100"
                            value="<?php echo escapeHtml($form["full_name"]); ?>"
                            required
                        >
                    </div>

                    <div class="col-12">
                        <label for="specialization" class="form-label">Specialization *</label>
                        <input
                            type="text"
                            id="specialization"
                            name="specialization"
                            class="form-control"
                            placeholder="e.g. Cardiology, Pediatrics, General Medicine"
                            maxlength="100"
                            value="<?php echo escapeHtml($form["specialization"]); ?>"
                            required
                        >
                    </div>

                    <div class="col-md-6">
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

                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="doctor@example.com"
                            maxlength="100"
                            value="<?php echo escapeHtml($form["email"]); ?>"
                        >
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-4 flex-wrap">
                    <a href="doctors.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-floppy me-1"></i> Save Doctor
                    </button>
                </div>

            </form>
        </section>
    </div>
</div>
</body>
</html>
