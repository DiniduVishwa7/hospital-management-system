# MediCore — Security Review

**Scope:** documentation and source review of the front-controller refactor. This is not a penetration test, production-readiness approval, or claim of healthcare compliance. Use fictional data only. Paths are relative to the project root.

See [Application architecture](../README.md#application-architecture) and [Local Setup](LOCAL_SETUP.md).

## Preserved protections

- `public/index.php` selects one developer-owned handler from `app/routes.php`. All 20 routes are enabled with explicit method and authentication metadata. Request input never becomes a filesystem path.
- The dispatcher rejects malformed/array selectors with 400, unknown routes with 404, and unsupported methods with 405/Allow. There is no fallback to removed root PHP implementations.
- `app/shared/auth_guard.php` runs before protected database access, validates the integer user ID, applies the 30-minute inactivity timeout/security headers, and initializes `logout_token`.
- The protected guard retains strict-mode/cookie-only sessions. Login and logout retain their independent session startup and existing HttpOnly, SameSite=Lax, and HTTPS-dependent Secure settings.
- Login retains password verification and session-ID regeneration. Login and CRUD CSRF names/checks/invalidation rules are unchanged. Logout remains POST-only with token validation, session destruction, and matching cookie deletion.
- PDO queries, bindings, transactions, appointment scheduling conflicts/status rules, linked-record deletion restrictions, optional medical-record doctor associations, output escaping, validation, and flash messages are retained.
- Shared URL helpers generate application-relative destinations from a validated trusted base path, never the Host header. Edit IDs and POST body identifiers remain intact; GET search forms preserve their route field.
- The existing `config/.htaccess` and `database/.htaccess` remain unchanged. `config/database.local.php` remains private, ignored, and untracked.

## HTTP exposure and deployment status

Root `.htaccess` permits only public/ paths through the current htdocs mount. `app/.htaccess` and `app/shared/.htaccess` deny direct HTTP requests. Public rules disable listings/path-info and reject alternate PHP entry filenames. Query-string routing needs no broad catch-all rewrite.

These are Apache-dependent containment rules. The actual XAMPP document root remains htdocs until a separately approved virtual-host or Alias cutover is applied and tested. A new public/ folder alone does not establish filesystem isolation. Local Setup documents both deployment options, old-mount protection, base paths, and rollback.

Root PHP wrappers, old PHP implementations, duplicate root assets, and empty legacy directories have been removed. Implementations remain under app/ and browser assets under public/assets/.

## Verification boundaries

Static source/SQL comparison, syntax checks, rendered-template tests with fictional data, and unauthenticated Apache route/security tests can validate routing without changing working data. Rendering fixtures are not database-backed CRUD tests.

Successful credentialed login, authenticated live dashboard/directory displays, valid logout, responsive browser appearance, and full create/update/delete workflows require manual verification or an isolated test instance. Never use working patient records for destructive tests. If no disposable test database is provided, mark those write tests pending.

## Existing educational-project limitations

1. Persistent login-attempt throttling is not implemented.
2. All authenticated accounts effectively have administrator privileges; fine-grained roles are absent.
3. Audit logging, medical-record retention/archival rules, and soft deletion are absent.
4. Some CRUD operations permanently delete records. Related-record restrictions are not a healthcare retention policy.
5. Login/logout do not explicitly initialize all strict-mode settings used by the guard; this existing difference was preserved.
6. Pagination and larger-dataset controls are not implemented.
7. CDN dependencies, inline content, HTTPS deployment, encryption/backups, and content-security policy need separate review.
8. Screenshot contents must be checked by a person for private information; matching image hashes do not prove data is fictional.

Do not deploy this demo for real patient information or describe it as production-ready.
