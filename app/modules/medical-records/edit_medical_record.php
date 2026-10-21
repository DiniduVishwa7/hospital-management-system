<?php
require_once __DIR__ . '/medical_record_common.php';

$id = mrPositiveInt($_GET['id'] ?? null);
if ($id === null) {
    http_response_code(400);
    exit('Invalid record ID. <a href="' . mrEscape(medicore_url('medical-records')) . '">Return to Medical Records</a>');
}
$record = mrLoadRecord($pdo, $id);
if (!$record) {
    http_response_code(404);
    exit('Medical record not found. <a href="' . mrEscape(medicore_url('medical-records')) . '">Return to Medical Records</a>');
}

$form = [
    'patient_id' => (string) $record['patient_id'],
    'doctor_id' => $record['doctor_id'] === null ? '' : (string) $record['doctor_id'],
    'record_date' => (string) $record['record_date'],
    'diagnosis' => (string) $record['diagnosis'],
    'treatment' => (string) ($record['treatment'] ?? ''),
    'notes' => (string) ($record['notes'] ?? '')
];
$error = '';
$tokenName = 'medical_record_edit_token';
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
        $error = mrSave($pdo, $form, $id);
        if ($error === '') {
            unset($_SESSION[$tokenName]);
            $_SESSION['medical_record_success'] = 'Medical record updated successfully!';
            medicore_redirect('medical-records');
            exit;
        }
    }
}

mrPageTop('Edit Medical Record', 'Update an existing medical record.');
?>
<section class="panel-card patient-form-card">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="patient-form-icon"><i class="bi bi-pencil-square"></i></div>
        <div>
            <h5 class="panel-title mb-1">Edit Medical Record #<?php echo (int) $id; ?></h5>
            <p class="text-muted mb-0">Change the information below and save the record.</p>
        </div>
    </div>
    <hr class="mb-4">
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo mrEscape($error); ?>
        </div>
    <?php endif; ?>
    <?php mrFormFields(
        $form, $patients, $doctors, $tokenName,
        'Update Medical Record', medicore_url('medical-records/edit', ['id' => $id])
    ); ?>
</section>
<?php mrPageBottom(); ?>
