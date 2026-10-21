<?php
require_once __DIR__ . '/medical_record_common.php';

$error = '';
$form = mrEmptyForm();
$tokenName = 'medical_record_create_token';
mrToken($tokenName);

$patients = $pdo->query(
    'SELECT patient_id, full_name FROM patients ORDER BY full_name, patient_id'
)->fetchAll(PDO::FETCH_ASSOC);
$doctors = $pdo->query(
    'SELECT doctor_id, full_name, specialization FROM doctors ORDER BY full_name, doctor_id'
)->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = mrReadForm($_POST);
    if (!mrCheckToken($tokenName, $_POST[$tokenName] ?? null)) {
        http_response_code(403);
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $error = mrSave($pdo, $form);
        if ($error === '') {
            unset($_SESSION[$tokenName]);
            $_SESSION['medical_record_success'] = 'Medical record added successfully!';
            header('Location: medical_records.php');
            exit;
        }
    }
}

mrPageTop('Add Medical Record', 'Create a record for an existing patient.');
?>
<section class="panel-card patient-form-card">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="patient-form-icon"><i class="bi bi-file-earmark-medical-fill"></i></div>
        <div>
            <h5 class="panel-title mb-1">Medical Record Information</h5>
            <p class="text-muted mb-0">Fields marked * are required. Use fictional test information.</p>
        </div>
    </div>
    <hr class="mb-4">
    <?php if (!$patients): ?>
        <div class="alert alert-warning" role="alert">
            Add a <a class="alert-link" href="patients.php">registered patient</a> before creating a medical record.
        </div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo mrEscape($error); ?>
        </div>
    <?php endif; ?>
    <?php mrFormFields($form, $patients, $doctors, $tokenName, 'Save Medical Record', 'add_medical_record.php'); ?>
</section>
<?php mrPageBottom(); ?>
