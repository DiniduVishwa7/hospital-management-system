<?php
require_once __DIR__ . '/medical_record_common.php';

$id = mrPositiveInt($_GET['id'] ?? null);
if ($id === null) {
    http_response_code(400);
    exit('Invalid record ID. <a href="medical_records.php">Return to Medical Records</a>');
}
$record = mrLoadRecord($pdo, $id);
if (!$record) {
    http_response_code(404);
    exit('Medical record not found. <a href="medical_records.php">Return to Medical Records</a>');
}

mrPageTop('View Medical Record', 'Review the details of a saved medical record.');
?>
<section class="panel-card patient-form-card">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="patient-form-icon"><i class="bi bi-file-medical"></i></div>
        <div>
            <h5 class="panel-title mb-1">Medical Record #<?php echo (int) $record['record_id']; ?></h5>
            <p class="text-muted mb-0">Patient information and recorded clinical details.</p>
        </div>
    </div>
    <hr class="mb-4">
    <dl class="row g-3 mb-4">
        <dt class="col-sm-4">Patient</dt>
        <dd class="col-sm-8"><?php echo mrEscape($record['patient_name']); ?></dd>
        <dt class="col-sm-4">Doctor</dt>
        <dd class="col-sm-8"><?php echo mrEscape($record['doctor_name'] ?? 'Not assigned'); ?></dd>
        <dt class="col-sm-4">Record Date</dt>
        <dd class="col-sm-8"><?php echo mrEscape($record['record_date']); ?></dd>
        <dt class="col-sm-4">Diagnosis</dt>
        <dd class="col-sm-8" style="overflow-wrap: anywhere;"><?php echo mrEscape($record['diagnosis']); ?></dd>
        <dt class="col-sm-4">Treatment</dt>
        <dd class="col-sm-8" style="white-space: pre-wrap; overflow-wrap: anywhere;"><?php echo mrEscape($record['treatment'] ?: 'Not provided'); ?></dd>
        <dt class="col-sm-4">Notes</dt>
        <dd class="col-sm-8" style="white-space: pre-wrap; overflow-wrap: anywhere;"><?php echo mrEscape($record['notes'] ?: 'Not provided'); ?></dd>
    </dl>
    <div class="d-flex justify-content-end gap-2 flex-wrap">
        <a href="medical_records.php" class="btn btn-outline-secondary">Back to Records</a>
        <a href="edit_medical_record.php?id=<?php echo (int) $record['record_id']; ?>" class="btn btn-primary">
            <i class="bi bi-pencil-square me-1"></i>Edit Record
        </a>
    </div>
</section>
<?php mrPageBottom(); ?>
