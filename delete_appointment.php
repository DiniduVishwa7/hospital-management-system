<?php
require_once __DIR__ . '/appointment_common.php';

$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

$appointmentId = mcPositiveInt(
    $method === 'POST' ? ($_POST['appointment_id'] ?? null) : ($_GET['id'] ?? null)
);
if ($appointmentId === null) {
    http_response_code(400);
    exit('Invalid appointment ID. <a href="appointments.php">Return to Appointments</a>');
}

$getAppointment = $pdo->prepare(
    'SELECT a.appointment_id, a.appointment_date, a.appointment_time,
            a.status, a.notes, p.full_name AS patient_name,
            d.full_name AS doctor_name
     FROM appointments AS a
     INNER JOIN patients AS p ON p.patient_id = a.patient_id
     INNER JOIN doctors AS d ON d.doctor_id = a.doctor_id
     WHERE a.appointment_id = :id LIMIT 1'
);
$getAppointment->execute(['id' => $appointmentId]);
$appointment = $getAppointment->fetch(PDO::FETCH_ASSOC);
if (!$appointment) {
    http_response_code(404);
    exit('Appointment not found. <a href="appointments.php">Return to Appointments</a>');
}

$tokenName = 'appointment_delete_token';
mcToken($tokenName);
$error = '';

if ($method === 'POST') {
    if (!mcCheckToken($tokenName, $_POST[$tokenName] ?? null)) {
        http_response_code(403);
        $error = 'Invalid confirmation request. Please refresh and try again.';
    } elseif (($_POST['confirm_delete'] ?? '') !== 'yes') {
        $error = 'Please tick the confirmation checkbox before deleting.';
    } else {
        try {
            $delete = $pdo->prepare(
                'DELETE FROM appointments WHERE appointment_id = :id'
            );
            $delete->execute(['id' => $appointmentId]);

            if ($delete->rowCount() === 1) {
                unset($_SESSION[$tokenName]);
                $_SESSION['appointment_success'] = 'Appointment deleted successfully!';
                header('Location: appointments.php');
                exit;
            }
            $error = 'Appointment no longer exists. Return to the directory.';
        } catch (PDOException $e) {
            error_log('MediCore appointment delete failed: ' . $e->getMessage());
            $error = 'Unable to delete this appointment. Please try again.';
        }
    }
}

mcPageTop('Delete Appointment', 'Review the booking carefully before removing it.');
?>
<section class="panel-card patient-form-card">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="patient-form-icon text-danger bg-danger-subtle">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div>
            <h5 class="panel-title mb-1">Confirm Appointment Deletion</h5>
            <p class="text-muted mb-0">Check the patient and doctor before continuing.</p>
        </div>
    </div>

    <div class="alert alert-warning" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Warning:</strong> This permanently removes the appointment.
        For routine cancellations, use <strong>Edit → Cancelled</strong> to keep the history instead.
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-x-circle me-2"></i><?php echo mcEscape($error); ?>
        </div>
    <?php endif; ?>

    <div class="border rounded-3 p-4 mb-4 bg-light">
        <h6 class="fw-bold mb-3">Appointment to delete</h6>
        <dl class="row mb-0">
            <dt class="col-sm-4">Appointment ID</dt>
            <dd class="col-sm-8"><?php echo (int) $appointment['appointment_id']; ?></dd>
            <dt class="col-sm-4">Patient</dt>
            <dd class="col-sm-8"><?php echo mcEscape($appointment['patient_name']); ?></dd>
            <dt class="col-sm-4">Doctor</dt>
            <dd class="col-sm-8"><?php echo mcEscape($appointment['doctor_name']); ?></dd>
            <dt class="col-sm-4">Date</dt>
            <dd class="col-sm-8"><?php echo mcEscape($appointment['appointment_date']); ?></dd>
            <dt class="col-sm-4">Time</dt>
            <dd class="col-sm-8"><?php echo mcEscape(substr((string) $appointment['appointment_time'], 0, 5)); ?></dd>
            <dt class="col-sm-4">Status</dt>
            <dd class="col-sm-8 mb-0"><?php echo mcEscape($appointment['status']); ?></dd>
        </dl>
    </div>

    <form method="POST" action="delete_appointment.php">
        <input type="hidden" name="appointment_id" value="<?php echo (int) $appointmentId; ?>">
        <input type="hidden" name="<?php echo mcEscape($tokenName); ?>"
               value="<?php echo mcEscape($_SESSION[$tokenName]); ?>">

        <div class="form-check mb-4">
            <input type="checkbox" class="form-check-input" id="confirm_delete"
                   name="confirm_delete" value="yes" required>
            <label class="form-check-label" for="confirm_delete">
                I understand that this appointment will be permanently deleted.
            </label>
        </div>

        <div class="d-flex justify-content-end gap-3 flex-wrap">
            <a href="appointments.php" class="btn btn-outline-secondary">Cancel — Keep Appointment</a>
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash me-1"></i> Yes, Delete Appointment
            </button>
        </div>
    </form>
</section>
<?php mcPageBottom(); ?>
