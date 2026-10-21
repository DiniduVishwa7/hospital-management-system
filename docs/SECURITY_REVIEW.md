# MediCore — Security Review

**Scope:** documentation review of the current project layout after restructuring Stages 1–3. This updates the earlier Step 11.4 notes against the current source; it is not a new penetration test or production-readiness assessment. All paths below are relative to the project root. See the [README](../README.md#application-architecture) and [Local Setup](LOCAL_SETUP.md).

## Improvements implemented

- **Central guard:** `includes/auth_guard.php` protects the dashboard and CRUD implementations, directly or through the appointment/medical-record common helpers. Root `auth_guard.php` is a compatibility shim. Authentication runs before protected database access and HTML output.
- **Session inactivity timeout:** expires an authenticated session after 30 minutes without protected-page activity.
- **Session settings:** the guard enables strict-mode session IDs and cookie-only sessions. The guard, login, and logout set HttpOnly, SameSite=Lax, and HTTPS-dependent Secure cookie flags; session-start consistency remains a review item below.
- **Response headers:** prevent caching of sensitive pages; anti-framing, MIME sniffing and referer leakage protection.
- **HTTP folder protection:** `.htaccess` rules deny direct requests to `config/`, `database/`, `includes/`, and `modules/` when Apache honors the rules. All four module folders inherit `modules/.htaccess` without child overrides. PHP `require`/`require_once` can still load internal files.
- **Compatibility routing:** root CRUD entry files require implementations in `modules/patients/`, `modules/doctors/`, `modules/appointments/`, and `modules/medical-records/`. Original browser URLs, query parameters, forms, redirects, and navigation are retained; internal implementations are not public routes. Root search and module-common helpers remain forwarding shims.
- **Request protection:** login and CRUD mutation forms retain their CSRF tokens and `hash_equals()` checks. Logout remains POST-only and validates `logout_token`; the guard initializes that token when absent. Login verifies password hashes and regenerates the session ID after successful authentication.
- **No CRUD changes:** existing database SQL statements, forms, validation, transaction/conflict checks, deletion restrictions, and search behavior were retained during the module moves.

## Still needing attention

1. **Brute-force resistance:** `login.php` has no persistent login-attempt throttling. Add and verify an attempt limit as a separate security change.
2. **Authorization:** all authenticated users effectively have administrator privileges. Introduce roles and per-module permissions before deployment.
3. **Medical-record auditability:** record create/edit/delete and sensitive data views need an audit trail; medical records should not be permanently deleted in real deployments.
4. **Transport and data protection:** use HTTPS, encrypted backups, restricted access, and a healthcare privacy/legal review.
5. **Session initialization:** `login.php` and `logout.php` set cookie flags but do not explicitly enable the guard's strict-mode and cookie-only session settings. Review consistency separately; this restructuring does not change authentication or session handling.
6. **Query/report limits:** add pagination and limits to management tables for larger datasets.
7. **Content security policy:** review CDN usage and inline styles/scripts before deploying an appropriate CSP.

**Safety:** keep using fictional records only. This is a developer-focused audit, not a claim of clinical or production compliance.
