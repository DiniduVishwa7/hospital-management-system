<?php
require_once __DIR__ . '/medical_record_common.php';
require_once __DIR__ . '/directory_search.php';

// Search by patient/doctor/diagnosis; optional inclusive record-date range.
$search = mdSearchText($_GET, 'q');
$fromDate = mdSearchDate($_GET, 'from');
$toDate = mdSearchDate($_GET, 'to');
$dateRangeError = $fromDate !== '' && $toDate !== '' && $fromDate > $toDate;
$conditions = [];
$params = [];
if ($search !== '') {
    $pattern = mdLikePattern($search);
    $parts = [
        "p.full_name LIKE :q_patient ESCAPE '!'",
        "d.full_name LIKE :q_doctor ESCAPE '!'",
        "r.diagnosis LIKE :q_diagnosis ESCAPE '!'"];
    $params['q_patient'] = $pattern;
    $params['q_doctor'] = $pattern;
    $params['q_diagnosis'] = $pattern;
    $searchedId = mdSearchId($search);
    if ($searchedId !== null) {
        $parts[] = 'r.record_id = :q_id';
        $params['q_id'] = $searchedId;
    }
    $conditions[] = '(' . implode(' OR ', $parts) . ')';
}
if ($fromDate !== '') {
    $conditions[] = 'r.record_date >= :date_from';
    $params['date_from'] = $fromDate;
}
if ($toDate !== '') {
    $conditions[] = 'r.record_date <= :date_to';
    $params['date_to'] = $toDate;
}
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$records = [];
if (!$dateRangeError) {
    $stmt = $pdo->prepare(
        'SELECT r.record_id, r.record_date, r.diagnosis,
                p.full_name AS patient_name, d.full_name AS doctor_name
         FROM medical_records AS r
         INNER JOIN patients AS p ON p.patient_id = r.patient_id
         LEFT JOIN doctors AS d ON d.doctor_id = r.doctor_id' . $where . '
         ORDER BY r.record_date DESC, r.record_id DESC'
    );
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$totalRecords = (int) $pdo->query('SELECT COUNT(*) FROM medical_records')->fetchColumn();
$hasFilters = $search !== '' || $fromDate !== '' || $toDate !== '';
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

    <form method="GET" action="medical_records.php" role="search" class="row g-3 align-items-end mb-3">
        <div class="col-lg-5">
            <label for="medical_search" class="form-label fw-semibold">Search medical records</label>
            <input id="medical_search" name="q" type="search" class="form-control"
                   value="<?php echo mrEscape($search); ?>" maxlength="100"
                   placeholder="Patient, doctor, diagnosis or record ID">
        </div>
        <div class="col-sm-6 col-lg-2">
            <label for="record_from" class="form-label fw-semibold">From date</label>
            <input id="record_from" name="from" type="date" class="form-control"
                   value="<?php echo mrEscape($fromDate); ?>">
        </div>
        <div class="col-sm-6 col-lg-2">
            <label for="record_to" class="form-label fw-semibold">To date</label>
            <input id="record_to" name="to" type="date" class="form-control"
                   value="<?php echo mrEscape($toDate); ?>">
        </div>
        <div class="col-lg-3 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Search</button>
            <a href="medical_records.php" class="btn btn-outline-secondary">Clear</a>
        </div>
    </form>
    <?php if ($dateRangeError): ?>
        <div class="alert alert-warning" role="alert">From date must not be later than To date. Adjust the filters and search again.</div>
    <?php endif; ?>
    <p class="text-muted small mb-3" aria-live="polite">
        Showing <?php echo count($records); ?> of <?php echo $totalRecords; ?> medical records<?php echo $hasFilters ? ' (filtered)' : ''; ?>.
    </p>

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
                        <h6 class="mt-3"><?php echo $dateRangeError ? 'Invalid date range' : ($hasFilters ? 'No matching medical records found' : 'No medical records available yet'); ?></h6>
                        <p class="text-muted mb-0"><?php echo $dateRangeError ? 'Correct the dates above or click Clear.' : ($hasFilters ? 'Try another search or date range, or click Clear.' : 'New records will appear here once you add them.'); ?></p>
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
