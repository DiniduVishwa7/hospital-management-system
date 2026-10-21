# MediCore — Local Setup Guide

This guide is for the **local educational demonstration** only. Use fictional data and never commit real credentials or exported database records.

## 1. Place the project in XAMPP

Use PHP 8.2 with `pdo_mysql` and `mbstring`, Apache, and MySQL/MariaDB. XAMPP provides the local server environment; check that both PHP extensions are enabled in the PHP configuration Apache uses. The current project has no Composer or npm dependency installation step.

Example Windows installation:

```text
D:\Projects\xampp\htdocs\hospital-management-system
```

Start **Apache** and **MySQL** using the XAMPP Control Panel.

Apache must honor the existing `.htaccess` files in `config/`, `database/`, `includes/`, and `modules/`. The access rules require `mod_authz_core` and an `AllowOverride` setting that permits authorization directives, such as `AuthConfig` or `All`. Do not remove or weaken these rules to resolve a setup problem.

## 2. Create and import the database

1. Open `http://localhost/phpmyadmin/`.
2. Create the database `hospital_management` (choose a UTF-8 / utf8mb4 collation).
3. Select the new database and choose **Import**.
4. Select `database/hospital_management.sql` from the project and click **Go**.
5. Verify the tables: `users`, `patients`, `doctors`, `appointments`, `medical_records`.

The bundled SQL is intended as a **schema-only** export. Check the file before importing into any database that already contains records; do not overwrite your working data.

## 3. Configure the local PDO connection

Create this file **locally**, not in GitHub:

```text
config/database.local.php
```

For a default, local-only XAMPP setup, a minimal example is:

```php
<?php
$host = '127.0.0.1';
$dbname = 'hospital_management';
$username = 'root';
$password = ''; // Replace with your own local MySQL password, if configured.

$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
```

This is a **development-only example**. Use a dedicated, least-privilege database user and a strong password for any environment outside local testing. Make sure `config/database.local.php` remains excluded by `.gitignore`.

## 4. Create the first administrator account

The project does not ship with an administrator account, password, or public registration form.

Create your initial account **locally** with a one-time setup method, using PHP's `password_hash($password, PASSWORD_DEFAULT)` and a unique username/email. Store the hash in the `users.password_hash` column, **never** store a plaintext password. Remove any temporary web-accessible setup script immediately after using it and do not commit it.

If you are unsure about creating an initial account, ask for a local-only one-time setup procedure rather than using an unprotected public sign-up page.

## 5. Open MediCore

Go to:

```text
http://localhost/hospital-management-system/login.php
```

Sign in with the administrator account you created locally. The dashboard should then display live counts from MySQL.

The folder restructuring does not change this URL or the root CRUD URLs such as `patients.php`, `doctors.php`, `appointments.php`, and `medical_records.php`. Their root compatibility wrappers execute the implementations in `modules/` without redirecting the request. Open the root pages through Apache; internal module files and shared `includes/` helpers are intentionally blocked over HTTP.

All file paths in the setup examples above are relative to the project root unless shown as absolute paths. See the [README architecture](../README.md#application-architecture) and [Security Review](SECURITY_REVIEW.md) for the current layout and limitations.

## 6. Troubleshooting

- **Can't connect to database:** confirm both XAMPP services are running and local connection settings match your installation.
- **Login doesn't work:** verify that the `users` record contains a password hash created with PHP `password_hash()`.
- **403 for project URLs under `/config`, `/database`, `/includes`, or `/modules`:** this is expected when Apache folder protection is enabled. Use root entry URLs such as `patients.php` instead of `modules/patients/patients.php`.
- **Internal PHP files are accessible over HTTP, or an access-rule error appears:** check Apache's authorization module, `AllowOverride` setting, and error log. Keep the existing `.htaccess` files intact.
- **Page looks unstyled:** check local CSS paths and refresh your browser cache.
- **MariaDB startup trouble:** do not remove database files or perform a recovery without a backup. Inspect the MySQL error log first.

## Privacy and backups

- Keep private database exports outside the Git repository.
- Don't commit `config/database.local.php`, `.env` secrets, temporary admin-creation scripts, or actual patient/medical records.
- The demo is intended for fictional sample data only.
