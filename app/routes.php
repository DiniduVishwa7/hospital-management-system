<?php
declare(strict_types=1);

/**
 * Planned routes only. Enable each route after its handler and access checks
 * have been migrated and verified. Never fall back to the original application.
 */
return [
    'dashboard' => [
        'file' => __DIR__ . '/modules/dashboard/dashboard.php',
        'methods' => ['GET'],
        'auth' => true,
        'available' => true,
    ],
    'login' => [
        'file' => __DIR__ . '/modules/auth/login.php',
        'methods' => ['GET', 'POST'],
        'auth' => false,
        'available' => true,
    ],
    'logout' => [
        'file' => __DIR__ . '/modules/auth/logout.php',
        'methods' => ['POST'],
        'auth' => false,
        'available' => true,
    ],
    'patients' => [
        'file' => __DIR__ . '/modules/patients/patients.php',
        'methods' => ['GET'],
        'auth' => true,
        'available' => false,
    ],
    'patients/create' => [
        'file' => __DIR__ . '/modules/patients/add_patient.php',
        'methods' => ['GET', 'POST'],
        'auth' => true,
        'available' => false,
    ],
    'patients/edit' => [
        'file' => __DIR__ . '/modules/patients/edit_patient.php',
        'methods' => ['GET', 'POST'],
        'auth' => true,
        'available' => false,
    ],
    'patients/delete' => [
        'file' => __DIR__ . '/modules/patients/delete_patient.php',
        'methods' => ['GET', 'POST'],
        'auth' => true,
        'available' => false,
    ],
    'doctors' => [
        'file' => __DIR__ . '/modules/doctors/doctors.php',
        'methods' => ['GET'],
        'auth' => true,
        'available' => false,
    ],
    'doctors/create' => [
        'file' => __DIR__ . '/modules/doctors/add_doctor.php',
        'methods' => ['GET', 'POST'],
        'auth' => true,
        'available' => false,
    ],
    'doctors/edit' => [
        'file' => __DIR__ . '/modules/doctors/edit_doctor.php',
        'methods' => ['GET', 'POST'],
        'auth' => true,
        'available' => false,
    ],
    'doctors/delete' => [
        'file' => __DIR__ . '/modules/doctors/delete_doctor.php',
        'methods' => ['GET', 'POST'],
        'auth' => true,
        'available' => false,
    ],
    'appointments' => [
        'file' => __DIR__ . '/modules/appointments/appointments.php',
        'methods' => ['GET'],
        'auth' => true,
        'available' => false,
    ],
    'appointments/create' => [
        'file' => __DIR__ . '/modules/appointments/add_appointment.php',
        'methods' => ['GET', 'POST'],
        'auth' => true,
        'available' => false,
    ],
    'appointments/edit' => [
        'file' => __DIR__ . '/modules/appointments/edit_appointment.php',
        'methods' => ['GET', 'POST'],
        'auth' => true,
        'available' => false,
    ],
    'appointments/delete' => [
        'file' => __DIR__ . '/modules/appointments/delete_appointment.php',
        'methods' => ['GET', 'POST'],
        'auth' => true,
        'available' => false,
    ],
    'medical-records' => [
        'file' => __DIR__ . '/modules/medical-records/medical_records.php',
        'methods' => ['GET'],
        'auth' => true,
        'available' => false,
    ],
    'medical-records/create' => [
        'file' => __DIR__ . '/modules/medical-records/add_medical_record.php',
        'methods' => ['GET', 'POST'],
        'auth' => true,
        'available' => false,
    ],
    'medical-records/view' => [
        'file' => __DIR__ . '/modules/medical-records/view_medical_record.php',
        'methods' => ['GET'],
        'auth' => true,
        'available' => false,
    ],
    'medical-records/edit' => [
        'file' => __DIR__ . '/modules/medical-records/edit_medical_record.php',
        'methods' => ['GET', 'POST'],
        'auth' => true,
        'available' => false,
    ],
    'medical-records/delete' => [
        'file' => __DIR__ . '/modules/medical-records/delete_medical_record.php',
        'methods' => ['GET', 'POST'],
        'auth' => true,
        'available' => false,
    ],
];
