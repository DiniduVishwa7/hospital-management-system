<?php
require_once __DIR__ . '/appointment_common.php';
require_once dirname(__DIR__, 2) . '/includes/directory_search.php';

// Search appointments by patient/doctor; filter status and appointment date.
$search = mdSearchText($_GET, 'q');
$status = mdSearchText($_GET, 'status', 20);
$appointmentDate = mdSearchDate($_GET, 'date');
if (!in_array($status, mcStatuses(), true)) {
    $status = '';
}
$conditions = [];
$params = [];
if ($search !== '') {
    $pattern = mdLikePattern($search);
    $parts = ["p.full_name LIKE :q_patient ESCAPE '!'",
              "d.full_name LIKE :q_doctor ESCAPE '!'"];
    $params['q_patient'] = $pattern;
    $params['q_doctor'] = $pattern;
    $searchedId = mdSearchId($search);
    if ($searchedId !== null) {
        $parts[] = 'a.appointment_id = :q_id';
        $params['q_id'] = $searchedId;
    }
    $conditions[] = '(' . implode(' OR ', $parts) . ')';
}
if ($status !== '') {
    $conditions[] = 'a.status = :status';
    $params['status'] = $status;
}
if ($appointmentDate !== '') {
    $conditions[] = 'a.appointment_date = :appointment_date';
    $params['appointment_date'] = $appointmentDate;
}
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$statement = $pdo->prepare(
    'SELECT a.appointment_id, a.appointment_date, a.appointment_time,
            a.status, a.notes,
            p.full_name AS patient_name, d.full_name AS doctor_name
     FROM appointments AS a
     INNER JOIN patients AS p ON p.patient_id = a.patient_id
     INNER JOIN doctors AS d ON d.doctor_id = a.doctor_id' . $where . '
     ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.appointment_id DESC'
);
$statement->execute($params);
$appointments = $statement->fetchAll(PDO::FETCH_ASSOC);
$totalAppointments = (int) $pdo->query('SELECT COUNT(*) FROM appointments')->fetchColumn();
$hasFilters = $search !== '' || $status !== '' || $appointmentDate !== '';

// One-time confirmation message after Create, Edit, or Delete.
$success = $_SESSION['appointment_success'] ?? '';
unset($_SESSION['appointment_success']);

mcPageTop('Appointments', 'View and manage hospital appointments.');
?>
<section class="panel-card">
    <?php if ($success !== ''): ?>
        <div class="alert alert-success" role="status">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo mcEscape($success); ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h5 class="panel-title mb-1">Appointment Directory</h5>
            <p class="text-muted mb-0">Total appointments: <?php echo (int) $totalAppointments; ?></p>
        </div>
        <a href="add_appointment.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Appointment
        </a>
    </div>

    <form method="GET" action="appointments.php" role="search" class="row g-3 align-items-end mb-3">
        <div class="col-lg-5">
            <label for="appointment_search" class="form-label fw-semibold">Search appointments</label>
            <input id="appointment_search" name="q" type="search" class="form-control"
                   value="<?php echo mcEscape($search); ?>" maxlength="100"
                   placeholder="Patient, doctor or appointment ID">
        </div>
        <div class="col-sm-6 col-lg-3">
            <label for="appointment_status" class="form-label fw-semibold">Status</label>
            <select id="appointment_status" name="status" class="form-select">
                <option value="">All statuses</option>
                <?php foreach (mcStatuses() as $option): ?>
                    <option value="<?php echo mcEscape($option); ?>" <?php echo $status === $option ? 'selected' : ''; ?>>
                        <?php echo mcEscape($option); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-lg-2">
            <label for="appointment_date" class="form-label fw-semibold">Date</label>
            <input id="appointment_date" name="date" type="date" class="form-control"
                   value="<?php echo mcEscape($appointmentDate); ?>">
        </div>
        <div class="col-sm-12 col-lg-2 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Search</button>
            <a href="appointments.php" class="btn btn-outline-secondary">Clear</a>
        </div>
    </form>
    <p class="text-muted small mb-3" aria-live="polite">
        Showing <?php echo count($appointments); ?> of <?php echo $totalAppointments; ?> appointments<?php echo $hasFilters ? ' (filtered)' : ''; ?>.
    </p>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Patient</th>
                    <th scope="col">Doctor</th>
                    <th scope="col">Date</th>
                    <th scope="col">Time</th>
                    <th scope="col">Status</th>
                    <th scope="col">Notes</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$appointments): ?>
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="bi bi-calendar-x text-secondary fs-1"></i>
                        <h6 class="mt-3"><?php echo $hasFilters ? 'No matching appointments found' : 'No appointments scheduled yet'; ?></h6>
                        <p class="text-muted mb-0"><?php echo $hasFilters ? 'Try different filters or click Clear.' : 'New appointments will appear here once booked.'; ?></p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?php echo (int) $appointment['appointment_id']; ?></td>
                        <td><?php echo mcEscape($appointment['patient_name']); ?></td>
                        <td><?php echo mcEscape($appointment['doctor_name']); ?></td>
                        <td><?php echo mcEscape($appointment['appointment_date']); ?></td>
                        <td><?php echo mcEscape(substr((string) $appointment['appointment_time'], 0, 5)); ?></td>
                        <td>
                            <span class="badge <?php echo mcStatusBadge((string) $appointment['status']); ?>">
                                <?php echo mcEscape($appointment['status']); ?>
                            </span>
                        </td>
                        <td style="min-width: 160px; max-width: 300px; overflow-wrap: anywhere; white-space: pre-line;"><?php
                            $notes = trim((string) ($appointment['notes'] ?? ''));
                            echo mcEscape($notes !== '' ? $notes : '-');
                        ?></td>
                        <td>
                            <div class="d-flex gap-2 flex-wrap">
                                <a class="btn btn-outline-primary btn-sm"
                                   href="edit_appointment.php?id=<?php echo (int) $appointment['appointment_id']; ?>">
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </a>
                                <a class="btn btn-outline-danger btn-sm"
                                   href="delete_appointment.php?id=<?php echo (int) $appointment['appointment_id']; ?>">
                                    <i class="bi bi-trash me-1"></i>Delete
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
<?php mcPageBottom(); ?>
