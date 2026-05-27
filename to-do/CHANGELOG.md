# Changelog

All notable changes to OrbitAdmin live here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and dates use Norwegian `dd/mm/yyyy`.

## [0.1.0] - 26/05/2026

### Added

- Initial OrbitAdmin scaffold with a futuristic Bootstrap 5 theme, mobile sidebar, theme toggle, and command palette.
- Pluggable storage drivers: MySQL/MariaDB, SQLite, JSON (default for the demo).
- Modules: core, db, auth, dashboard, users, roles, activity, tokens, emails, files, system, settings, installer.
- Hash-chained append-only activity log with chain verification on the System info page and via `bin/orbit test`.
- API tokens with sha256 hashes at rest; full secret shown once at creation.
- Email templates with `{{var}}` placeholders, preview, and test send (logged when mail is disabled).
- File manager with extension and MIME allow-lists; uploads stored outside the web root.
- Install wizard with server readiness, driver picker, and first admin user; locks itself after success.
- `bin/orbit` CLI: install, migrate, user:add, user:list, user:passwd, token:create, token:revoke, cache:clear, demo:seed, demo:reset, test, release.
- Security headers (CSP nonce, HSTS, XFO, XCTO, Referrer-Policy, Permissions-Policy, COOP/COEP), CSRF tokens, bcrypt cost 12, rate-limited login.
- Live demo at `https://test.newstargeted.com/OrbitAdmin/` and GitHub Pages landing under `docs/`.

### Notes

- Workspace conventions honoured: every module under 500 lines, secrets in `config.php` only, Norwegian `dd/mm/yyyy` dates, no em dashes.
