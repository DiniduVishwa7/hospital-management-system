<?php
require_once __DIR__ . '/medical_record_common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method not allowed.');
}

$id = mrPositiveInt($method === 'POST' ? ($_POST['record_id'] ?? null) : ($_GET['id'] ?? null));
if ($id === null) {
    http_response_code(400);
    exit('Invalid record ID. <a href="medical_records.php">Return to Medical Records</a>');
}
$record = mrLoadRecord($pdo, $id);
if (!$record) {
    http_response_code(404);
    exit('Medical record not found. <a href="medical_records.php">Return to Medical Records</a>');
}

$tokenName = 'medical_record_delete_token';
mrToken($tokenName);
$error = '';

if ($method === 'POST') {
    if (!mrCheckToken($tokenName, $_POST[$tokenName] ?? null)) {
        http_response_code(403);
        $error = 'Invalid confirmation request. Please refresh and try again.';
    } elseif (($_POST['confirm_delete'] ?? '') !== 'yes') {
        $error = 'Please tick the confirmation checkbox first.';
    } else {
        try {
            $stmt = $pdo->prepare('DELETE FROM medical_records WHERE record_id = :id');
            $stmt->execute(['id' => $id]);
            if ($stmt->rowCount() === 1) {
                unset($_SESSION[$tokenName]);
                $_SESSION['medical_record_success'] = 'Medical record deleted successfully!';
                header('Location: medical_records.php');
                exit;
            }
            $error = 'This medical record no longer exists.';
        } catch (PDOException $e) {
            error_log('MediCore medical record deletion failed: ' . $e->getMessage());
            $error = 'Unable to delete this medical record. Please try again.';
        }
    }
}

mrPageTop('Delete Medical Record', 'Confirm carefully before permanently deleting a record.');
?>
<section class="panel-card patient-form-card">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="patient-form-icon text-danger bg-danger-subtle"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div>
            <h5 class="panel-title mb-1">Confirm Medical Record Deletion</h5>
            <p class="text-muted mb-0">Review the information before continuing.</p>
        </div>
    </div>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Warning:</strong> Deletion is permanent in this educational demo.
        Real healthcare applications generally require retention rules, auditing, and archival rather than hard deletion.
    </div>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i><?php echo mrEscape($error); ?>
        </div>
    <?php endif; ?>
    <div class="border rounded-3 p-4 mb-4 bg-light">
        <dl class="row mb-0">
            <dt class="col-sm-4">Record ID</dt>
            <dd class="col-sm-8"><?php echo (int) $record['record_id']; ?></dd>
            <dt class="col-sm-4">Patient</dt>
            <dd class="col-sm-8"><?php echo mrEscape($record['patient_name']); ?></dd>
            <dt class="col-sm-4">Doctor</dt>
            <dd class="col-sm-8"><?php echo mrEscape($record['doctor_name'] ?? 'Not assigned'); ?></dd>
            <dt class="col-sm-4">Record Date</dt>
            <dd class="col-sm-8"><?php echo mrEscape($record['record_date']); ?></dd>
            <dt class="col-sm-4">Diagnosis</dt>
            <dd class="col-sm-8 mb-0" style="overflow-wrap: anywhere;"><?php echo mrEscape($record['diagnosis']); ?></dd>
        </dl>
    </div>
    <form method="POST" action="delete_medical_record.php">
        <input type="hidden" name="record_id" value="<?php echo (int) $id; ?>">
        <input type="hidden" name="<?php echo mrEscape($tokenName); ?>" value="<?php echo mrEscape($_SESSION[$tokenName]); ?>">
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="confirm_delete"
                   name="confirm_delete" value="yes" required>
            <label for="confirm_delete" class="form-check-label">
                I understand that this medical record will be permanently deleted.
            </label>
        </div>
        <div class="d-flex justify-content-end gap-3 flex-wrap">
            <a href="medical_records.php" class="btn btn-outline-secondary">Cancel — Keep Record</a>
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash me-1"></i> Yes, Delete Medical Record
            </button>
        </div>
    </form>
</section>
<?php mrPageBottom(); ?>
