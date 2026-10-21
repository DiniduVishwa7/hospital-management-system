# MediCore — Local Setup Guide

This is an educational portfolio demonstration, not production-ready healthcare software. Use fictional data only. Never commit credentials, patient information, or database backups.

## 1. Requirements and project location

Use PHP 8.2 with `pdo_mysql` and `mbstring`, MySQL/MariaDB, and Apache 2.4 with `mod_rewrite`, `mod_authz_core`, and `mod_env` for the deployment examples. The current project requires no Composer or npm installation.

Example project path:

```text
D:/Projects/xampp/htdocs/hospital-management-system
```

Start Apache and MySQL through the XAMPP Control Panel. Apache must honor the project `.htaccess` files. Preserve the existing rules; a 403 for internal source/configuration directories is expected.

All application filesystem paths below are relative to the project root unless explicitly absolute.

## 2. Database setup

For a **new, empty local database**, use phpMyAdmin to create `hospital_management` with a suitable UTF-8/utf8mb4 collation and import [database/hospital_management.sql](../database/hospital_management.sql). Its tables are `users`, `patients`, `doctors`, `appointments`, and `medical_records`.

Do not reimport over an existing working database. Restructuring does not require schema or data changes.

Existing installations must keep `config/database.local.php` unchanged. On a new clone only, create that ignored file with your own local PDO settings and a `$pdo` connection. Example placeholders:

```php
<?php
$host = '127.0.0.1';
$dbname = 'hospital_management';
$username = 'YOUR_LOCAL_DB_USER';
$password = 'YOUR_LOCAL_DB_PASSWORD';

$pdo = new PDO(
    "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
```

Use your own settings; these placeholders are not credentials. Keep the configuration untracked, denied over HTTP, and outside public/. Never paste private configuration into an issue or screenshot.

## 3. Local administrator account

There is no bundled administrator account, default administrator password, or public registration form. Sign in using an account you created locally. Account setup must store a `password_hash()` result in `users.password_hash`, never plaintext. Remove any temporary web-accessible account-creation script after local setup and do not commit it.

## 4. Current development URL

The existing XAMPP document root remains the broader htdocs directory. No server configuration was changed by the refactor.

```text
http://127.0.0.1/hospital-management-system/public/
http://127.0.0.1/hospital-management-system/public/index.php?route=login
http://127.0.0.1/hospital-management-system/public/index.php?route=patients
```

The default trusted `MEDICORE_BASE_PATH` is `/hospital-management-system/public`. Successful login redirects to the routed dashboard. Logout is a POST form with its existing token. GET search forms include their route selector; edit URLs retain `id`.

Root PHP wrappers are removed. Old root URLs are retired, not aliases. The root access rule permits only public/ HTTP paths and blocks internal files, documentation, and Git files through this mount. Existing `app/`, `config/`, and `database/` restrictions provide additional protection.

**The server has not yet been reconfigured to expose only public/.** The options below are instructions for a separately approved deployment, not applied settings.

## 5. Public-only deployment options

Choose one option and set its trusted base path. Do not derive it from a request header.

| Deployment | Login URL | MEDICORE_BASE_PATH |
| --- | --- | --- |
| Current development | `http://127.0.0.1/hospital-management-system/public/index.php?route=login` | `/hospital-management-system/public` (default) |
| Dedicated virtual host | `http://medicore.test/index.php?route=login` | `/` (normalized to empty) |
| Alias into public/ | `http://127.0.0.1/hospital-management-system/index.php?route=login` | `/hospital-management-system` |

### Option A: dedicated virtual host

Manually configure XAMPP `apache/conf/extra/httpd-vhosts.conf` after checking that `apache/conf/httpd.conf` includes it. Preserve all existing sites and the first/default virtual host. If there are currently no virtual hosts, define the localhost host first so phpMyAdmin and other localhost projects keep their existing mapping:

```apache
# Only add this default host if an equivalent default host does not exist.
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "D:/Projects/xampp/htdocs"
</VirtualHost>

<VirtualHost *:80>
    ServerName medicore.test
    DocumentRoot "D:/Projects/xampp/htdocs/hospital-management-system/public"

    <Directory "D:/Projects/xampp/htdocs/hospital-management-system/public">
        Options -Indexes -MultiViews
        AllowOverride All
        Require local
        DirectoryIndex index.php
        SetEnv MEDICORE_BASE_PATH /
    </Directory>
</VirtualHost>
```

Manually add `127.0.0.1 medicore.test` to the Windows hosts file if using this hostname. This requires appropriate OS permissions. Port 80 is the existing XAMPP listener; do not duplicate or change Listen directives blindly.

Keep the old htdocs mount protected too. A new virtual host does not remove access through other hosts. For stronger server-level containment, configure a project-directory deny rule and the public-directory allowance in the relevant server/virtual-host scope:

```apache
<Directory "D:/Projects/xampp/htdocs/hospital-management-system">
    Require all denied
</Directory>

<Directory "D:/Projects/xampp/htdocs/hospital-management-system/public">
    Require local
</Directory>
```

The more specific public directory must keep the option/base-path settings from its selected deployment. Review authorization merging and existing aliases; do not grant access to app/, config/, or database/.

### Option B: Alias for the existing URL prefix

Instead of adding a named host, configure this in the existing localhost server/virtual-host scope, keeping unrelated aliases unchanged:

```apache
Alias "/hospital-management-system" "D:/Projects/xampp/htdocs/hospital-management-system/public"

<Directory "D:/Projects/xampp/htdocs/hospital-management-system/public">
    Options -Indexes -MultiViews
    AllowOverride All
    Require local
    DirectoryIndex index.php
    SetEnv MEDICORE_BASE_PATH /hospital-management-system
</Directory>
```

The Alias maps the URL prefix directly to public/. It does not change the document root of unrelated XAMPP sites. After this cutover, remove `/public` from the development URL. Old PHP endpoint filenames remain retired; use `index.php?route=...`.

### Validate and roll back a deployment

Back up Apache configuration outside web-served directories before editing. After review, run the XAMPP Apache binary with `-t` and `-S` to validate syntax and virtual-host selection, then restart Apache manually. No such changes or restart are performed by this refactor.

Verify the chosen hostname/base path, login form action, redirects, all four directory search forms, logout, CSS, and banner. Verify app/, config/, database/, Git files, source files, and the old htdocs access path cannot be read over HTTP. Keep both project and private-directory access rules.

If validation fails, restore the previous Apache configuration and development base path. The database and private PDO file do not need rollback. See [Apache virtual-host examples](https://httpd.apache.org/docs/2.4/vhosts/examples.html), [Alias documentation](https://httpd.apache.org/docs/2.4/mod/mod_alias.html#alias), and [authorization merging](https://httpd.apache.org/docs/2.4/mod/mod_authz_core.html#authmerging).

## 6. Verification and troubleshooting

- Lint project PHP sources with `D:/Projects/xampp/php/php.exe -l`, excluding private configuration and dependencies.
- Unknown routes return 404; malformed/array route selectors return 400; unsupported methods return 405 with Allow. Logout rejects invalid/missing tokens with 403. Login preserves its original validation-error page behavior.
- A 403 outside public/ is expected. Do not remove protection to make an internal PHP file load.
- For database connection failures, check local services/settings privately. Do not publish error details or credentials.
- If CSS is missing or redirects lose the URL prefix, check MEDICORE_BASE_PATH and the selected server mapping.
- Do not remove MySQL files or recover a database as part of a folder refactor.
- Use a separately provisioned disposable database with fictional records for write tests. Do not alter the working configuration to point at a test database. If no isolated test instance is available, database-backed CRUD writes remain unverified.
- The superseded root assets/, includes/, and modules/ directories have been removed; the application loads assets from public/assets/ and implementations from app/.

See [README architecture](../README.md#application-architecture), [Screenshot Guide](SCREENSHOT_GUIDE.md), and [Security Review](SECURITY_REVIEW.md).

## Privacy and backups

Keep private exports outside the repository and web root. Never commit local credentials, actual patient/medical records, temporary setup scripts, or database backups. This project remains an educational demo.
