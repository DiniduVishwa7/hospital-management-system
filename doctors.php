<?php
// Central authentication/session setup shared by MediCore protected pages.
require_once __DIR__ . '/includes/auth_guard.php';

require_once __DIR__ . "/config/database.local.php";
require_once __DIR__ . "/includes/directory_search.php";

$pageTitle = "Doctors";

if (empty($_SESSION["logout_token"])) {
    $_SESSION["logout_token"] = bin2hex(random_bytes(32));
}

// Search by name, specialization, email or doctor ID.
$search = mdSearchText($_GET, 'q');
$specialization = mdSearchText($_GET, 'specialization', 100);
$specializations = $pdo->query(
    "SELECT DISTINCT specialization FROM doctors ORDER BY specialization ASC"
)->fetchAll(PDO::FETCH_COLUMN);
if (!in_array($specialization, $specializations, true)) {
    $specialization = '';
}
$conditions = [];
$params = [];
if ($search !== '') {
    $parts = [
        "d.full_name LIKE :q_name ESCAPE '!'",
        "d.specialization LIKE :q_specialty ESCAPE '!'",
        "d.email LIKE :q_email ESCAPE '!'"];
    $pattern = mdLikePattern($search);
    $params['q_name'] = $pattern;
    $params['q_specialty'] = $pattern;
    $params['q_email'] = $pattern;
    $searchedId = mdSearchId($search);
    if ($searchedId !== null) {
        $parts[] = 'd.doctor_id = :q_id';
        $params['q_id'] = $searchedId;
    }
    $conditions[] = '(' . implode(' OR ', $parts) . ')';
}
if ($specialization !== '') {
    $conditions[] = 'd.specialization = :specialization';
    $params['specialization'] = $specialization;
}
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$statement = $pdo->prepare(
    'SELECT d.doctor_id, d.full_name, d.specialization, d.phone, d.email
     FROM doctors AS d' . $where . ' ORDER BY d.doctor_id DESC'
);
$statement->execute($params);
$doctors = $statement->fetchAll(PDO::FETCH_ASSOC);
$totalDoctors = (int) $pdo->query('SELECT COUNT(*) FROM doctors')->fetchColumn();
$hasFilters = $search !== '' || $specialization !== '';

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

            <form method="GET" action="doctors.php" role="search" class="row g-3 align-items-end mb-3">
                <div class="col-lg-6">
                    <label for="doctor_search" class="form-label fw-semibold">Search doctors</label>
                    <input id="doctor_search" name="q" type="search" class="form-control"
                           value="<?php echo escapeHtml($search); ?>" maxlength="100"
                           placeholder="Name, specialization, email or doctor ID">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label for="doctor_specialization" class="form-label fw-semibold">Specialization</label>
                    <select id="doctor_specialization" name="specialization" class="form-select">
                        <option value="">All specializations</option>
                        <?php foreach ($specializations as $option): ?>
                            <option value="<?php echo escapeHtml($option); ?>" <?php echo $specialization === $option ? 'selected' : ''; ?>>
                                <?php echo escapeHtml($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Search</button>
                    <a href="doctors.php" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
            <p class="text-muted small mb-3" aria-live="polite">
                Showing <?php echo count($doctors); ?> of <?php echo $totalDoctors; ?> doctors<?php echo $hasFilters ? ' (filtered)' : ''; ?>.
            </p>

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
                                    <h6 class="mt-3"><?php echo $hasFilters ? 'No matching doctors found' : 'No doctors registered yet'; ?></h6>
                                    <p class="text-muted mb-0">
                                        <?php echo $hasFilters ? 'Try another search or click Clear.' : 'New doctors will appear here once they are added.'; ?>
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
