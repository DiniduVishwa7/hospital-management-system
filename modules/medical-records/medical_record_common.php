<?php
// Central authentication/session setup shared by MediCore protected pages.
require_once dirname(__DIR__, 2) . '/includes/auth_guard.php';

require_once dirname(__DIR__, 2) . '/config/database.local.php';

if (empty($_SESSION['logout_token'])) {
    $_SESSION['logout_token'] = bin2hex(random_bytes(32));
}

function mrEscape($text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function mrTextLength(string $value): int
{
    // XAMPP normally includes mbstring. Byte count is a safe fallback.
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function mrPositiveInt($value): ?int
{
    if (!is_int($value) && !is_string($value)) {
        return null;
    }
    $parsed = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
    return $parsed === false ? null : (int) $parsed;
}

function mrToken(string $name): string
{
    if (empty($_SESSION[$name])) {
        $_SESSION[$name] = bin2hex(random_bytes(32));
    }
    return $_SESSION[$name];
}

function mrCheckToken(string $name, $value): bool
{
    return is_string($value)
        && isset($_SESSION[$name])
        && is_string($_SESSION[$name])
        && hash_equals($_SESSION[$name], $value);
}

function mrEmptyForm(): array
{
    return [
        'patient_id' => '',
        'doctor_id' => '',
        'record_date' => date('Y-m-d'),
        'diagnosis' => '',
        'treatment' => '',
        'notes' => ''
    ];
}

function mrReadForm(array $post): array
{
    $values = mrEmptyForm();
    foreach (array_keys($values) as $field) {
        $input = $post[$field] ?? '';
        $values[$field] = is_string($input) ? trim($input) : '';
    }
    return $values;
}

function mrValidateForm(array $form): string
{
    if (mrPositiveInt($form['patient_id']) === null) {
        return 'Please select a valid patient.';
    }
    if ($form['doctor_id'] !== '' && mrPositiveInt($form['doctor_id']) === null) {
        return 'Please select a valid doctor or choose Not assigned.';
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $form['record_date']);
    if (!$date || $date->format('Y-m-d') !== $form['record_date'] || $form['record_date'] > date('Y-m-d')) {
        return 'Please enter a valid record date that is not in the future.';
    }
    if ($form['diagnosis'] === '' || mrTextLength($form['diagnosis']) > 255) {
        return 'Diagnosis is required and must be 255 characters or fewer.';
    }
    if (mrTextLength($form['treatment']) > 5000) {
        return 'Treatment must be 5,000 characters or fewer.';
    }
    if (mrTextLength($form['notes']) > 5000) {
        return 'Notes must be 5,000 characters or fewer.';
    }
    return '';
}

/** Save new or updated record. Returns empty string on success. */
function mrSave(PDO $pdo, array $form, ?int $recordId = null): string
{
    $error = mrValidateForm($form);
    if ($error !== '') {
        return $error;
    }

    try {
        $patientId = mrPositiveInt($form['patient_id']);
        $doctorId = $form['doctor_id'] === '' ? null : mrPositiveInt($form['doctor_id']);

        $checkPatient = $pdo->prepare('SELECT patient_id FROM patients WHERE patient_id = :id LIMIT 1');
        $checkPatient->execute(['id' => $patientId]);
        if (!$checkPatient->fetchColumn()) {
            return 'The selected patient no longer exists. Please choose a different patient.';
        }
        if ($doctorId !== null) {
            $checkDoctor = $pdo->prepare('SELECT doctor_id FROM doctors WHERE doctor_id = :id LIMIT 1');
            $checkDoctor->execute(['id' => $doctorId]);
            if (!$checkDoctor->fetchColumn()) {
                return 'The selected doctor no longer exists. Please choose another doctor.';
            }
        }

        $values = [
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'record_date' => $form['record_date'],
            'diagnosis' => $form['diagnosis'],
            'treatment' => $form['treatment'] === '' ? null : $form['treatment'],
            'notes' => $form['notes'] === '' ? null : $form['notes']
        ];

        if ($recordId === null) {
            $sql = 'INSERT INTO medical_records
                    (patient_id, doctor_id, record_date, diagnosis, treatment, notes)
                    VALUES (:patient_id, :doctor_id, :record_date, :diagnosis, :treatment, :notes)';
        } else {
            $sql = 'UPDATE medical_records SET
                        patient_id = :patient_id,
                        doctor_id = :doctor_id,
                        record_date = :record_date,
                        diagnosis = :diagnosis,
                        treatment = :treatment,
                        notes = :notes
                    WHERE record_id = :record_id';
            $values['record_id'] = $recordId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        if ($recordId !== null && $stmt->rowCount() === 0) {
            // MySQL can return zero for a no-op UPDATE; check record existence.
            $existing = $pdo->prepare('SELECT record_id FROM medical_records WHERE record_id = :id');
            $existing->execute(['id' => $recordId]);
            if (!$existing->fetchColumn()) {
                return 'This medical record no longer exists. Return to the directory.';
            }
        }
        return '';
    } catch (PDOException $e) {
        error_log('MediCore medical record save failed: ' . $e->getMessage());
        if ($e->getCode() === '23000') {
            return 'The selected patient or doctor is unavailable. Please refresh and try again.';
        }
        return 'Unable to save the medical record. Please try again.';
    }
}

function mrLoadRecord(PDO $pdo, int $recordId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT r.record_id, r.patient_id, r.doctor_id, r.record_date,
                r.diagnosis, r.treatment, r.notes,
                p.full_name AS patient_name, d.full_name AS doctor_name
         FROM medical_records AS r
         INNER JOIN patients AS p ON p.patient_id = r.patient_id
         LEFT JOIN doctors AS d ON d.doctor_id = r.doctor_id
         WHERE r.record_id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $recordId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function mrPageTop(string $title, string $subtitle): void
{
    $title = mrEscape($title);
    $subtitle = mrEscape($subtitle);
    $logoutToken = mrEscape($_SESSION['logout_token']);
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} | MediCore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=3">
</head>
<body>
<div class="main-wrapper">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon"><i class="bi bi-hospital"></i></div>
            <div><h4 class="mb-0">MediCore</h4><small>Hospital System</small></div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a></li>
            <li><a href="patients.php"><i class="bi bi-people-fill"></i> Patients</a></li>
            <li><a href="doctors.php"><i class="bi bi-person-badge-fill"></i> Doctors</a></li>
            <li><a href="appointments.php"><i class="bi bi-calendar2-check-fill"></i> Appointments</a></li>
            <li><a href="medical_records.php" class="active"><i class="bi bi-file-earmark-medical-fill"></i> Medical Records</a></li>
            <li>
                <form action="logout.php" method="POST">
                    <input type="hidden" name="logout_token" value="{$logoutToken}">
                    <button type="submit" class="logout-button"><i class="bi bi-box-arrow-right"></i> Logout</button>
                </form>
            </li>
        </ul>
    </aside>
    <div class="content-area">
        <header class="topbar">
            <div>
                <h2 class="page-title">{$title}</h2>
                <p class="page-subtitle mb-0">{$subtitle}</p>
            </div>
            <div class="admin-badge"><i class="bi bi-person-circle"></i> <span>Admin</span></div>
        </header>
HTML;
}

function mrPageBottom(): void
{
    echo "    </div>\n</div>\n</body>\n</html>\n";
}

function mrFormFields(
    array $form,
    array $patients,
    array $doctors,
    string $tokenName,
    string $submitLabel,
    string $action
): void {
    $disabled = empty($patients);
    ?>
    <form method="POST" action="<?php echo mrEscape($action); ?>" autocomplete="off">
        <input type="hidden" name="<?php echo mrEscape($tokenName); ?>"
               value="<?php echo mrEscape(mrToken($tokenName)); ?>">
        <div class="row g-4">
            <div class="col-md-6">
                <label for="patient_id" class="form-label">Patient *</label>
                <select id="patient_id" name="patient_id" class="form-select" required>
                    <option value="">Select registered patient</option>
                    <?php foreach ($patients as $patient): ?>
                        <option value="<?php echo (int) $patient['patient_id']; ?>"
                            <?php echo (string) $patient['patient_id'] === $form['patient_id'] ? 'selected' : ''; ?>>
                            <?php echo mrEscape($patient['full_name']); ?> (ID <?php echo (int) $patient['patient_id']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="doctor_id" class="form-label">Doctor (Optional)</label>
                <select id="doctor_id" name="doctor_id" class="form-select">
                    <option value="">Not assigned</option>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?php echo (int) $doctor['doctor_id']; ?>"
                            <?php echo (string) $doctor['doctor_id'] === $form['doctor_id'] ? 'selected' : ''; ?>>
                            <?php echo mrEscape($doctor['full_name']); ?> — <?php echo mrEscape($doctor['specialization']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="record_date" class="form-label">Record Date *</label>
                <input id="record_date" type="date" name="record_date" class="form-control"
                       value="<?php echo mrEscape($form['record_date']); ?>"
                       max="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="col-12">
                <label for="diagnosis" class="form-label">Diagnosis *</label>
                <input id="diagnosis" type="text" name="diagnosis" class="form-control"
                       placeholder="Enter fictional diagnosis" maxlength="255"
                       value="<?php echo mrEscape($form['diagnosis']); ?>" required>
            </div>
            <div class="col-12">
                <label for="treatment" class="form-label">Treatment (Optional)</label>
                <textarea id="treatment" name="treatment" class="form-control" rows="4" maxlength="5000"
                          placeholder="Enter fictional treatment details"><?php echo mrEscape($form['treatment']); ?></textarea>
            </div>
            <div class="col-12">
                <label for="notes" class="form-label">Notes (Optional)</label>
                <textarea id="notes" name="notes" class="form-control" rows="4" maxlength="5000"
                          placeholder="Enter fictional clinical notes"><?php echo mrEscape($form['notes']); ?></textarea>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-3 mt-4 flex-wrap">
            <a href="medical_records.php" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" <?php echo $disabled ? 'disabled' : ''; ?>>
                <i class="bi bi-floppy me-1"></i> <?php echo mrEscape($submitLabel); ?>
            </button>
        </div>
    </form>
    <?php
}
