<?php
// Central authentication/session setup shared by MediCore protected pages.
require_once dirname(__DIR__, 2) . '/includes/auth_guard.php';

require_once dirname(__DIR__, 2) . '/config/database.local.php';

if (empty($_SESSION['logout_token'])) {
    $_SESSION['logout_token'] = bin2hex(random_bytes(32));
}

function mcEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function mcToken(string $key): string
{
    if (empty($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(32));
    }
    return $_SESSION[$key];
}

function mcCheckToken(string $key, $provided): bool
{
    return is_string($provided)
        && isset($_SESSION[$key])
        && is_string($_SESSION[$key])
        && hash_equals($_SESSION[$key], $provided);
}

function mcPositiveInt($value): ?int
{
    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $parsed = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
    return $parsed === false ? null : (int) $parsed;
}

function mcStatuses(): array
{
    return ['Scheduled', 'Completed', 'Cancelled'];
}

function mcStatusBadge(string $status): string
{
    $classes = [
        'Scheduled' => 'text-bg-primary',
        'Completed' => 'text-bg-success',
        'Cancelled' => 'text-bg-danger'
    ];
    return $classes[$status] ?? 'text-bg-secondary';
}

function mcEmptyAppointmentForm(): array
{
    return [
        'patient_id' => '',
        'doctor_id' => '',
        'appointment_date' => '',
        'appointment_time' => '',
        'status' => 'Scheduled',
        'notes' => ''
    ];
}

function mcReadAppointmentForm(array $post): array
{
    $form = mcEmptyAppointmentForm();
    foreach (array_keys($form) as $name) {
        $value = $post[$name] ?? '';
        $form[$name] = is_string($value) ? trim($value) : '';
    }
    return $form;
}

function mcValidateAppointmentForm(array $form): string
{
    if (mcPositiveInt($form['patient_id']) === null) {
        return 'Please select a valid patient.';
    }
    if (mcPositiveInt($form['doctor_id']) === null) {
        return 'Please select a valid doctor.';
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $form['appointment_date']);
    if (!$date || $date->format('Y-m-d') !== $form['appointment_date']) {
        return 'Please enter a valid appointment date.';
    }

    if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $form['appointment_time'])) {
        return 'Please choose a valid appointment time.';
    }
    if (!in_array($form['status'], mcStatuses(), true)) {
        return 'Please select a valid appointment status.';
    }
    if (mb_strlen($form['notes'], 'UTF-8') > 2000) {
        return 'Notes must be 2,000 characters or fewer.';
    }
    return '';
}

/**
 * Insert or update an appointment. Returns an empty string on success,
 * or a user-friendly error on validation/conflict/database failure.
 *
 * Locking the doctor and patient rows serializes bookings made through this
 * module, preventing two active bookings for either party at the same slot.
 * This does not protect against arbitrary SQL edits outside MediCore.
 */
function mcSaveAppointment(PDO $pdo, array $form, ?int $appointmentId = null): string
{
    $error = mcValidateAppointmentForm($form);
    if ($error !== '') {
        return $error;
    }

    $patientId = mcPositiveInt($form['patient_id']);
    $doctorId = mcPositiveInt($form['doctor_id']);
    $slotTime = $form['appointment_time'] . ':00';

    try {
        $pdo->beginTransaction();

        // Lock the chosen doctor and patient consistently for every booking.
        $lockDoctor = $pdo->prepare(
            'SELECT doctor_id FROM doctors WHERE doctor_id = :id FOR UPDATE'
        );
        $lockDoctor->execute(['id' => $doctorId]);
        if (!$lockDoctor->fetch(PDO::FETCH_ASSOC)) {
            $pdo->rollBack();
            return 'The selected doctor no longer exists. Please choose another doctor.';
        }

        $lockPatient = $pdo->prepare(
            'SELECT patient_id FROM patients WHERE patient_id = :id FOR UPDATE'
        );
        $lockPatient->execute(['id' => $patientId]);
        if (!$lockPatient->fetch(PDO::FETCH_ASSOC)) {
            $pdo->rollBack();
            return 'The selected patient no longer exists. Please choose another patient.';
        }

        if ($appointmentId !== null) {
            $exists = $pdo->prepare(
                'SELECT appointment_id FROM appointments WHERE appointment_id = :id FOR UPDATE'
            );
            $exists->execute(['id' => $appointmentId]);
            if (!$exists->fetch(PDO::FETCH_ASSOC)) {
                $pdo->rollBack();
                return 'This appointment no longer exists. Return to the directory.';
            }
        }

        if ($form['status'] !== 'Cancelled') {
            $checkDoctor = $pdo->prepare(
                "SELECT appointment_id FROM appointments
                 WHERE doctor_id = :doctor_id
                   AND appointment_date = :appointment_date
                   AND appointment_time = :appointment_time
                   AND status <> 'Cancelled'
                   AND appointment_id <> :exclude_id
                 LIMIT 1"
            );
            $checkDoctor->execute([
                'doctor_id' => $doctorId,
                'appointment_date' => $form['appointment_date'],
                'appointment_time' => $slotTime,
                'exclude_id' => $appointmentId ?? 0
            ]);
            if ($checkDoctor->fetch(PDO::FETCH_ASSOC)) {
                $pdo->rollBack();
                return 'This doctor already has an appointment at the selected date and time.';
            }

            $checkPatient = $pdo->prepare(
                "SELECT appointment_id FROM appointments
                 WHERE patient_id = :patient_id
                   AND appointment_date = :appointment_date
                   AND appointment_time = :appointment_time
                   AND status <> 'Cancelled'
                   AND appointment_id <> :exclude_id
                 LIMIT 1"
            );
            $checkPatient->execute([
                'patient_id' => $patientId,
                'appointment_date' => $form['appointment_date'],
                'appointment_time' => $slotTime,
                'exclude_id' => $appointmentId ?? 0
            ]);
            if ($checkPatient->fetch(PDO::FETCH_ASSOC)) {
                $pdo->rollBack();
                return 'This patient already has an appointment at the selected date and time.';
            }
        }

        $parameters = [
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'appointment_date' => $form['appointment_date'],
            'appointment_time' => $slotTime,
            'status' => $form['status'],
            'notes' => $form['notes'] === '' ? null : $form['notes']
        ];

        if ($appointmentId === null) {
            $statement = $pdo->prepare(
                'INSERT INTO appointments
                 (patient_id, doctor_id, appointment_date, appointment_time, status, notes)
                 VALUES
                 (:patient_id, :doctor_id, :appointment_date, :appointment_time, :status, :notes)'
            );
        } else {
            $statement = $pdo->prepare(
                'UPDATE appointments SET
                    patient_id = :patient_id,
                    doctor_id = :doctor_id,
                    appointment_date = :appointment_date,
                    appointment_time = :appointment_time,
                    status = :status,
                    notes = :notes
                 WHERE appointment_id = :appointment_id'
            );
            $parameters['appointment_id'] = $appointmentId;
        }

        $statement->execute($parameters);
        $pdo->commit();
        return '';
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('MediCore appointment save failed: ' . $e->getMessage());
        if ($e->getCode() === '23000') {
            return 'Unable to save: the selected patient or doctor is not available.';
        }
        return 'Unable to save the appointment. Please try again.';
    }
}

function mcPageTop(string $title, string $subtitle): void
{
    $safeTitle = mcEscape($title);
    $safeSubtitle = mcEscape($subtitle);
    $logoutToken = mcEscape($_SESSION['logout_token']);
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$safeTitle} | MediCore</title>
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
            <li><a href="appointments.php" class="active"><i class="bi bi-calendar2-check-fill"></i> Appointments</a></li>
            <li><a href="medical_records.php"><i class="bi bi-file-earmark-medical-fill"></i> Medical Records</a></li>
            <li>
                <form action="logout.php" method="POST">
                    <input type="hidden" name="logout_token" value="{$logoutToken}">
                    <button type="submit" class="logout-button">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </li>
        </ul>
    </aside>
    <div class="content-area">
        <header class="topbar">
            <div>
                <h2 class="page-title">{$safeTitle}</h2>
                <p class="page-subtitle mb-0">{$safeSubtitle}</p>
            </div>
            <div class="admin-badge"><i class="bi bi-person-circle"></i> <span>Admin</span></div>
        </header>
HTML;
}

function mcPageBottom(): void
{
    echo "    </div>\n</div>\n</body>\n</html>\n";
}

/** Render the same field layout for both adding and editing. */
function mcAppointmentFields(
    array $form,
    array $patients,
    array $doctors,
    string $tokenName,
    string $submitLabel,
    string $formAction
): void {
    $csrf = mcEscape(mcToken($tokenName));
    $action = mcEscape($formAction);
    $disabled = empty($patients) || empty($doctors);
    ?>
    <form method="POST" action="<?php echo $action; ?>" autocomplete="off">
        <input type="hidden" name="<?php echo mcEscape($tokenName); ?>" value="<?php echo $csrf; ?>">
        <div class="row g-4">
            <div class="col-md-6">
                <label for="patient_id" class="form-label">Patient *</label>
                <select name="patient_id" id="patient_id" class="form-select" required>
                    <option value="">Select registered patient</option>
                    <?php foreach ($patients as $patient): ?>
                        <option value="<?php echo (int) $patient['patient_id']; ?>"
                            <?php echo (string) $patient['patient_id'] === $form['patient_id'] ? 'selected' : ''; ?>>
                            <?php echo mcEscape($patient['full_name']); ?> (ID <?php echo (int) $patient['patient_id']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="doctor_id" class="form-label">Doctor *</label>
                <select name="doctor_id" id="doctor_id" class="form-select" required>
                    <option value="">Select registered doctor</option>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?php echo (int) $doctor['doctor_id']; ?>"
                            <?php echo (string) $doctor['doctor_id'] === $form['doctor_id'] ? 'selected' : ''; ?>>
                            <?php echo mcEscape($doctor['full_name']); ?> — <?php echo mcEscape($doctor['specialization']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="appointment_date" class="form-label">Appointment Date *</label>
                <input type="date" id="appointment_date" name="appointment_date"
                       class="form-control" required
                       value="<?php echo mcEscape($form['appointment_date']); ?>">
            </div>

            <div class="col-md-6">
                <label for="appointment_time" class="form-label">Appointment Time *</label>
                <input type="time" id="appointment_time" name="appointment_time"
                       class="form-control" step="60" required
                       value="<?php echo mcEscape($form['appointment_time']); ?>">
            </div>

            <div class="col-12">
                <label for="status" class="form-label">Status *</label>
                <select name="status" id="status" class="form-select" required>
                    <?php foreach (mcStatuses() as $status): ?>
                        <option value="<?php echo mcEscape($status); ?>"
                            <?php echo $form['status'] === $status ? 'selected' : ''; ?>>
                            <?php echo mcEscape($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Cancelled bookings do not block the same date and time.</div>
            </div>

            <div class="col-12">
                <label for="notes" class="form-label">Notes (Optional)</label>
                <textarea name="notes" id="notes" rows="4" maxlength="2000"
                          class="form-control" placeholder="Optional appointment notes (avoid sensitive medical details)"><?php echo mcEscape($form['notes']); ?></textarea>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-3 mt-4 flex-wrap">
            <a href="appointments.php" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary" type="submit" <?php echo $disabled ? 'disabled' : ''; ?>>
                <i class="bi bi-floppy me-1"></i> <?php echo mcEscape($submitLabel); ?>
            </button>
        </div>
    </form>
    <?php
}
