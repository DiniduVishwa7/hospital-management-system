<?php
require_once __DIR__ . '/appointment_common.php';

$appointmentId = mcPositiveInt($_GET['id'] ?? null);
if ($appointmentId === null) {
    http_response_code(400);
    exit('Invalid appointment ID. <a href="appointments.php">Return to Appointments</a>');
}

// Only load an existing appointment; never trust names or statuses from a URL.
$getAppointment = $pdo->prepare(
    'SELECT appointment_id, patient_id, doctor_id, appointment_date,
            appointment_time, status, notes
     FROM appointments WHERE appointment_id = :id LIMIT 1'
);
$getAppointment->execute(['id' => $appointmentId]);
$appointment = $getAppointment->fetch(PDO::FETCH_ASSOC);
if (!$appointment) {
    http_response_code(404);
    exit('Appointment not found. <a href="appointments.php">Return to Appointments</a>');
}

$form = [
    'patient_id' => (string) $appointment['patient_id'],
    'doctor_id' => (string) $appointment['doctor_id'],
    'appointment_date' => (string) $appointment['appointment_date'],
    'appointment_time' => substr((string) $appointment['appointment_time'], 0, 5),
    'status' => (string) $appointment['status'],
    'notes' => (string) ($appointment['notes'] ?? '')
];
$error = '';
$tokenName = 'appointment_edit_token';
mcToken($tokenName);

$patients = $pdo->query(
    'SELECT patient_id, full_name FROM patients ORDER BY full_name ASC, patient_id ASC'
)->fetchAll(PDO::FETCH_ASSOC);
$doctors = $pdo->query(
    'SELECT doctor_id, full_name, specialization FROM doctors ORDER BY full_name ASC, doctor_id ASC'
)->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = mcReadAppointmentForm($_POST);

    if (!mcCheckToken($tokenName, $_POST[$tokenName] ?? null)) {
        http_response_code(403);
        $error = 'Invalid form request. Please refresh and try again.';
    } else {
        $error = mcSaveAppointment($pdo, $form, $appointmentId);
        if ($error === '') {
            unset($_SESSION[$tokenName]);
            $_SESSION['appointment_success'] = 'Appointment updated successfully!';
            header('Location: appointments.php');
            exit;
        }
    }
}

mcPageTop('Edit Appointment', 'Update the schedule, doctor, patient or appointment status.');
?>
<section class="panel-card patient-form-card">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="patient-form-icon"><i class="bi bi-pencil-square"></i></div>
        <div>
            <h5 class="panel-title mb-1">Edit Appointment Information</h5>
            <p class="text-muted mb-0">
                Appointment ID: <?php echo (int) $appointmentId; ?> &middot; Fields marked * are required.
            </p>
        </div>
    </div>
    <hr class="mb-4">

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo mcEscape($error); ?>
        </div>
    <?php endif; ?>

    <?php mcAppointmentFields(
        $form, $patients, $doctors, $tokenName, 'Update Appointment',
        'edit_appointment.php?id=' . $appointmentId
    ); ?>
</section>
<?php mcPageBottom(); ?>
