# MediCore Step 11.4 — Security Review

**Scope:** reviewed the MediCore PHP files available from earlier ZIP packages and refactored repeated authenticated-session startup. Current local files may have other changes; review diffs before replacing.

## Improvements implemented

- **Central guard:** 11 dashboard, patient, doctor, and shared module pages use `auth_guard.php`.
- **Session inactivity timeout:** expires an authenticated session after 30 minutes without protected-page activity.
- **Session cookie settings:** strict-mode session IDs; HttpOnly, SameSite=Lax; Secure cookies over HTTPS.
- **Response headers:** prevent caching of sensitive pages; anti-framing, MIME sniffing and referer leakage protection.
- **HTTP folder protection:** Apache access control files deny direct requests to `config/` and `database/`.
- **No CRUD changes:** existing database SQL statements, forms, navigation, and search behavior retained.

## Still needing attention

1. **Brute-force resistance:** current `login.php` wasn't included in the available working-file archive. Add a persistent attempt limit after reviewing the latest login code.
2. **Authorization:** all authenticated users effectively have administrator privileges. Introduce roles and per-module permissions before deployment.
3. **Medical-record auditability:** record create/edit/delete and sensitive data views need an audit trail; medical records should not be permanently deleted in real deployments.
4. **Transport and data protection:** use HTTPS, encrypted backups, restricted access, and a healthcare privacy/legal review.
5. **Session cookie on login:** match session strict mode settings in `login.php` for full protection from the start of each session.
6. **Query/report limits:** add pagination and limits to management tables for larger datasets.
7. **Content security policy:** review CDN usage and inline styles/scripts before deploying an appropriate CSP.

**Safety:** keep using fictional records only. This is a developer-focused audit, not a claim of clinical or production compliance.
