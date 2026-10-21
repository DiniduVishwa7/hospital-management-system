<?php
// Central authentication/session setup shared by MediCore protected pages.
require_once dirname(__DIR__, 2) . '/includes/auth_guard.php';

require_once dirname(__DIR__, 2) . "/config/database.local.php";
require_once dirname(__DIR__, 2) . "/includes/directory_search.php";

$pageTitle = "Patients";

if (empty($_SESSION["logout_token"])) {
    $_SESSION["logout_token"] = bin2hex(random_bytes(32));
}

// Display the success message once after adding a patient.
$success = $_SESSION["patient_success"] ?? "";
unset($_SESSION["patient_success"]);

// Search by name/phone/ID, with an optional gender filter.
$search = mdSearchText($_GET, 'q');
$gender = mdSearchText($_GET, 'gender', 10);
$allowedGenders = ['Male', 'Female', 'Other'];
if (!in_array($gender, $allowedGenders, true)) {
    $gender = '';
}
$conditions = [];
$params = [];
if ($search !== '') {
    $parts = ["p.full_name LIKE :q_name ESCAPE '!'", "p.phone LIKE :q_phone ESCAPE '!'"];
    $params['q_name'] = mdLikePattern($search);
    $params['q_phone'] = mdLikePattern($search);
    $searchedId = mdSearchId($search);
    if ($searchedId !== null) {
        $parts[] = 'p.patient_id = :q_id';
        $params['q_id'] = $searchedId;
    }
    $conditions[] = '(' . implode(' OR ', $parts) . ')';
}
if ($gender !== '') {
    $conditions[] = 'p.gender = :gender';
    $params['gender'] = $gender;
}
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$statement = $pdo->prepare(
    'SELECT p.patient_id, p.full_name, p.date_of_birth, p.gender, p.phone, p.address
     FROM patients AS p' . $where . ' ORDER BY p.patient_id DESC'
);
$statement->execute($params);
$patients = $statement->fetchAll(PDO::FETCH_ASSOC);
$totalPatients = (int) $pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn();
$hasFilters = $search !== '' || $gender !== '';

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

            <form method="GET" action="patients.php" role="search" class="row g-3 align-items-end mb-3">
                <div class="col-lg-6">
                    <label for="patient_search" class="form-label fw-semibold">Search patients</label>
                    <input id="patient_search" name="q" type="search" class="form-control"
                           value="<?php echo escapeHtml($search); ?>" maxlength="100"
                           placeholder="Name, phone or patient ID">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label for="patient_gender" class="form-label fw-semibold">Gender</label>
                    <select id="patient_gender" name="gender" class="form-select">
                        <option value="">All genders</option>
                        <?php foreach ($allowedGenders as $option): ?>
                            <option value="<?php echo escapeHtml($option); ?>" <?php echo $gender === $option ? 'selected' : ''; ?>>
                                <?php echo escapeHtml($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Search</button>
                    <a class="btn btn-outline-secondary" href="patients.php">Clear</a>
                </div>
            </form>
            <p class="text-muted small mb-3" aria-live="polite">
                Showing <?php echo count($patients); ?> of <?php echo $totalPatients; ?> patients<?php echo $hasFilters ? ' (filtered)' : ''; ?>.
            </p>

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
                                    <h6 class="mt-3"><?php echo $hasFilters ? 'No matching patients found' : 'No patients registered yet'; ?></h6>
                                    <p class="text-muted mb-0">
                                        <?php echo $hasFilters ? 'Try another name, phone, ID or gender, or click Clear.' : 'New patients will appear here once they are added.'; ?>
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
