<?php
// Central authentication/session setup shared by MediCore protected pages.
require_once __DIR__ . '/includes/auth_guard.php';

require_once __DIR__ . "/config/database.local.php";

$pageTitle = "Edit Doctor";
$error = "";

function escapeHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

// The selected doctor ID comes from the URL, e.g. edit_doctor.php?id=1.
$doctorId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT, [
    "options" => ["min_range" => 1]
]);

if ($doctorId === false || $doctorId === null) {
    http_response_code(400);
    exit('Invalid doctor ID. <a href="doctors.php">Return to Doctors</a>');
}

// Load existing values from MySQL (not from editable browser fields).
$statement = $pdo->prepare(
    "SELECT doctor_id, full_name, specialization, phone, email
     FROM doctors
     WHERE doctor_id = :doctor_id
     LIMIT 1"
);
$statement->execute(["doctor_id" => $doctorId]);
$doctor = $statement->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    http_response_code(404);
    exit('Doctor not found. <a href="doctors.php">Return to Doctors</a>');
}

if (empty($_SESSION["logout_token"])) {
    $_SESSION["logout_token"] = bin2hex(random_bytes(32));
}

// Separate token for the Edit Doctor form.
if (empty($_SESSION["doctor_edit_token"])) {
    $_SESSION["doctor_edit_token"] = bin2hex(random_bytes(32));
}

// Pre-fill the form; keep entered values when validation fails.
$form = [
    "full_name" => (string) $doctor["full_name"],
    "specialization" => (string) $doctor["specialization"],
    "phone" => (string) ($doctor["phone"] ?? ""),
    "email" => (string) ($doctor["email"] ?? "")
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    foreach (array_keys($form) as $field) {
        $submitted = $_POST[$field] ?? "";
        $form[$field] = is_string($submitted) ? trim($submitted) : "";
    }

    $token = $_POST["doctor_edit_token"] ?? "";

    if (
        !is_string($token) ||
        !isset($_SESSION["doctor_edit_token"]) ||
        !hash_equals($_SESSION["doctor_edit_token"], $token)
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
            // UPDATE only this doctor; do not create a new doctor record.
            $update = $pdo->prepare(
                "UPDATE doctors
                 SET full_name = :full_name,
                     specialization = :specialization,
                     phone = :phone,
                     email = :email
                 WHERE doctor_id = :doctor_id"
            );

            $update->execute([
                "full_name" => $form["full_name"],
                "specialization" => $form["specialization"],
                "phone" => $form["phone"] !== "" ? $form["phone"] : null,
                "email" => $form["email"] !== "" ? $form["email"] : null,
                "doctor_id" => $doctorId
            ]);

            // One-time success notice; redirect to avoid resubmission.
            $_SESSION["doctor_success"] = "Doctor updated successfully!";
            unset($_SESSION["doctor_edit_token"]);
            header("Location: doctors.php");
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Unable to update doctor. Please try again.";
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
    <!-- Reuse the existing MediCore form styling. -->
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
                <h2 class="page-title">Edit Doctor</h2>
                <p class="page-subtitle mb-0">Update an existing doctor's information.</p>
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
                    <h5 class="panel-title mb-1">Edit Doctor Information</h5>
                    <p class="text-muted mb-0">
                        Doctor ID: <?php echo (int) $doctorId; ?> &middot; Fields marked * are required.
                    </p>
                </div>
            </div>

            <hr class="mb-4">

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php echo escapeHtml($error); ?>
                </div>
            <?php endif; ?>

            <form id="doctorForm" action="edit_doctor.php?id=<?php echo (int) $doctorId; ?>" method="POST" autocomplete="off">
                <input
                    type="hidden"
                    name="doctor_edit_token"
                    value="<?php echo escapeHtml($_SESSION["doctor_edit_token"]); ?>"
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
                        <i class="bi bi-floppy me-1"></i> Update Doctor
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
</body>
</html>
