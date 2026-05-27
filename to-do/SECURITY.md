# Security posture

OrbitAdmin is built with a security-first mindset. This document summarises the controls in place and how to report issues.

## Reporting

Email `security+orbitadmin@newstargeted.com` or open a private security advisory in the GitHub repository at https://github.com/master3395/OrbitAdmin. Please do not file public issues for vulnerabilities.

## Configuration

- All secrets live in `config.php` (chmod 600). The web server denies HTTP access via `.htaccess`. `.env` files are not used by design.
- `config.php` is git-ignored. A safe template lives at `config.sample.php`.
- The installer marks `data/.installed` after success and refuses to re-run unless that marker is deleted.

## Headers and transport

Sent by `public/.htaccess` and reinforced by `bootstrap.php`:

- `Content-Security-Policy` with a per-request nonce on every inline `<script>` (no `'unsafe-inline'` for scripts).
- `Strict-Transport-Security` (sent only on HTTPS).
- `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`.
- `Permissions-Policy` denies camera, microphone, geolocation, USB, payment by default.
- `Cross-Origin-Opener-Policy: same-origin`, `Cross-Origin-Resource-Policy: same-site`.

## Sessions

- HttpOnly, SameSite=Strict, Secure auto-detected.
- ID regenerated on login.
- Fingerprint bound to the user agent and APP_KEY; mismatched fingerprints destroy the session.
- Idle expiry (default 1800 s) configurable via `SESSION_IDLE_SECONDS`.

## Authentication

- Passwords stored with `password_hash(PASSWORD_BCRYPT, ['cost' => 12])`; rehashed on login when cost changes.
- Login is rate-limited per client IP with a sliding window backed by `data/ratelimits.json`.
- CSRF tokens via double-submit (`_csrf` field + session value), rotated on login, verified for every POST/PUT/PATCH/DELETE.

## Storage

- MySQL and SQLite use PDO with prepared statements only.
- JSON driver writes atomically (temp file + `rename`) under `flock`.
- File uploads are extension and MIME allow-listed, randomised at storage, stored outside the web root (`data/uploads/`).

## Activity log

Append-only with a SHA-256 hash chain. Each row hashes `(previous_hash || current_row_payload)`. The System info page and `bin/orbit test` verify the chain.

## Demo mode

When `APP_DEMO=true`, the seeded admin (id 1) and Owner role (id 1) are read-only; mail sends are diverted to `logs/mail.log`; the installer is hidden.
