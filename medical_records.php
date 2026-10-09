<?php
require_once __DIR__ . '/medical_record_common.php';

// Join existing tables to display meaningful names, including unassigned doctors.
$stmt = $pdo->query(
    'SELECT r.record_id, r.record_date, r.diagnosis,
            p.full_name AS patient_name, d.full_name AS doctor_name
     FROM medical_records AS r
     INNER JOIN patients AS p ON p.patient_id = r.patient_id
     LEFT JOIN doctors AS d ON d.doctor_id = r.doctor_id
     ORDER BY r.record_date DESC, r.record_id DESC'
);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
$success = $_SESSION['medical_record_success'] ?? '';
unset($_SESSION['medical_record_success']);

mrPageTop('Medical Records', 'View and manage medical records for registered patients.');
?>
<?php if (in_array($success, [
    'Medical record added successfully!',
    'Medical record updated successfully!',
    'Medical record deleted successfully!'
], true)): ?>
    <div class="alert alert-success" role="status">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo mrEscape($success); ?>
    </div>
<?php endif; ?>

<section class="panel-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h5 class="panel-title mb-1">Medical Record Directory</h5>
            <p class="text-muted mb-0">Total records: <?php echo count($records); ?></p>
        </div>
        <a href="add_medical_record.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Medical Record
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Patient</th>
                    <th scope="col">Doctor</th>
                    <th scope="col">Record Date</th>
                    <th scope="col">Diagnosis</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$records): ?>
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <i class="bi bi-file-earmark-medical text-secondary fs-1"></i>
                        <h6 class="mt-3">No medical records available yet</h6>
                        <p class="text-muted mb-0">New records will appear here once you add them.</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <td><?php echo (int) $record['record_id']; ?></td>
                        <td><?php echo mrEscape($record['patient_name']); ?></td>
                        <td><?php echo mrEscape($record['doctor_name'] ?? 'Not assigned'); ?></td>
                        <td><?php echo mrEscape($record['record_date']); ?></td>
                        <td style="max-width: 240px; overflow-wrap: anywhere;">
                            <?php echo mrEscape($record['diagnosis']); ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2 flex-wrap">
                                <a class="btn btn-outline-secondary btn-sm"
                                   href="view_medical_record.php?id=<?php echo (int) $record['record_id']; ?>">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                                <a class="btn btn-outline-primary btn-sm"
                                   href="edit_medical_record.php?id=<?php echo (int) $record['record_id']; ?>">
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </a>
                                <a class="btn btn-outline-danger btn-sm"
                                   href="delete_medical_record.php?id=<?php echo (int) $record['record_id']; ?>">
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
<?php mrPageBottom(); ?>
