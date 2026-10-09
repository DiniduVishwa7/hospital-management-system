<?php
require_once __DIR__ . '/appointment_common.php';

// Load all bookings, showing patient and doctor names (not just IDs).
$statement = $pdo->query(
    'SELECT a.appointment_id, a.appointment_date, a.appointment_time,
            a.status, a.notes,
            p.full_name AS patient_name,
            d.full_name AS doctor_name
     FROM appointments AS a
     INNER JOIN patients AS p ON p.patient_id = a.patient_id
     INNER JOIN doctors AS d ON d.doctor_id = a.doctor_id
     ORDER BY a.appointment_date DESC, a.appointment_time DESC,
              a.appointment_id DESC'
);
$appointments = $statement->fetchAll(PDO::FETCH_ASSOC);
$totalAppointments = count($appointments);

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
                        <h6 class="mt-3">No appointments scheduled yet</h6>
                        <p class="text-muted mb-0">New appointments will appear here once booked.</p>
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
