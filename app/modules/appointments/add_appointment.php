<?php
require_once __DIR__ . '/appointment_common.php';

$error = '';
$form = mcEmptyAppointmentForm();
$tokenName = 'appointment_create_token';
mcToken($tokenName);

// Lists are drawn directly from the real Patient and Doctor tables.
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
        $error = mcSaveAppointment($pdo, $form);
        if ($error === '') {
            unset($_SESSION[$tokenName]);
            $_SESSION['appointment_success'] = 'Appointment added successfully!';
            medicore_redirect('appointments');
            exit;
        }
    }
}

mcPageTop('Add New Appointment', 'Schedule a patient visit with a registered doctor.');
?>
<section class="panel-card patient-form-card">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="patient-form-icon"><i class="bi bi-calendar-plus"></i></div>
        <div>
            <h5 class="panel-title mb-1">Appointment Information</h5>
            <p class="text-muted mb-0">Fields marked * are required.</p>
        </div>
    </div>
    <hr class="mb-4">

    <?php if (!$patients || !$doctors): ?>
        <div class="alert alert-warning" role="alert">
            Before booking an appointment, you need at least one
            <a href="<?php echo mcEscape(medicore_url('patients')); ?>" class="alert-link">registered patient</a>
            and one <a href="<?php echo mcEscape(medicore_url('doctors')); ?>" class="alert-link">registered doctor</a>.
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo mcEscape($error); ?>
        </div>
    <?php endif; ?>

    <?php mcAppointmentFields($form, $patients, $doctors, $tokenName, 'Save Appointment', medicore_url('appointments/create')); ?>
</section>
<?php mcPageBottom(); ?>
