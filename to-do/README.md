# OrbitAdmin

> Mission control for your server. A futuristic, mobile-friendly Bootstrap 5 admin panel with a pluggable backend.

[![PHP](https://img.shields.io/badge/PHP-7.4--8.6-7e57c2.svg)](https://www.php.net)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952b3.svg)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/license-MIT-22c55e.svg)](LICENSE)
[![Demo](https://img.shields.io/badge/demo-live-22d3ee.svg)](https://test.newstargeted.com/OrbitAdmin/)
[![Pages](https://img.shields.io/badge/pages-master3395.github.io%2FOrbitAdmin-d946ef.svg)](https://master3395.github.io/OrbitAdmin/)

![OrbitAdmin login](../docs/screenshots/login.svg)

![OrbitAdmin dashboard](../docs/screenshots/dashboard.svg)

OrbitAdmin is a single-folder PHP admin panel built for small teams, side projects, and homelabs. It ships with three interchangeable storage drivers, a real install wizard, an integrated CLI, hash-chained activity logging, and a futuristic glassmorphism UI that respects the user's reduced-motion preference.

## Highlights

- Three backends, one interface: pick `mysql`, `sqlite`, or `json` in `config.php`. JSON is the default so the demo works with zero setup.
- Mobile-friendly Bootstrap 5.3 UI with neon accents, dark/light toggle, command palette (Ctrl+K), and Norwegian dd/mm/yyyy formatting.
- Modules under 500 lines each: core, db, auth, dashboard, users, roles, activity, tokens, emails, files, system, settings, installer.
- Security baseline: CSP with per-request nonce, HSTS, XFO, XCTO, Referrer-Policy, Permissions-Policy, COOP/COEP, session fingerprinting, double-submit CSRF, bcrypt cost 12, rate-limited login, append-only hash-chained activity log.
- Install wizard: server readiness, driver picker, first admin user, install lock.
- CLI `bin/orbit`: install, migrate, user:add, token:create, demo:seed/reset, test, release.
- Live demo at https://test.newstargeted.com/OrbitAdmin/ and a GitHub Pages landing at https://master3395.github.io/OrbitAdmin/.

## Quick start

```bash
git clone git@github.com:master3395/OrbitAdmin.git
cd OrbitAdmin
php bin/orbit install
```

Open `/OrbitAdmin/` in a browser (or the path you deployed under) and sign in with the admin account you just created. To try without installing, copy `config.sample.php` to `config.php`, the JSON driver and bundled seed will boot the panel instantly.

### Demo credentials (live demo only)

```
admin  / OrbitDemo!2026
editor / OrbitDemo!2026
viewer / OrbitDemo!2026
```

## Folder map

```
public/        web root (vhost target after the top-level rewrite)
modules/       application modules (each file < 500 lines)
views/         layouts, partials, pages, components
data/          runtime state (sqlite, json, uploads), git-ignored
data/json.example/  committed seed used to spawn data/json/ on first boot
sql/           mysql.sql, sqlite.sql schemas
bin/orbit      PHP CLI entry
Test/          smoke + routing tests
to-do/         all canonical .md docs (per workspace convention)
docs/          GitHub Pages site (served from main branch /docs)
```

## Security notes

- All secrets stay in `config.php` (chmod 600, git-ignored). Never store them in views or templates.
- Sessions are HttpOnly, SameSite=Strict, Secure auto-detected, regenerated on login, idle-expired after 30 minutes by default.
- File uploads are extension + MIME allow-listed, stored outside the web root, served via PHP.
- The activity log is append-only and hash-chained; `bin/orbit test` and the System info page verify the chain.

See [SECURITY.md](SECURITY.md) for the full posture.

## Backend matrix

| Driver | Setup | Best for                          |
|--------|-------|-----------------------------------|
| json   | none  | homelab, demo, very small teams   |
| sqlite | none  | small projects, embedded installs |
| mysql  | DB    | production, multi-instance        |

Switch drivers by editing `DB_DRIVER` in `config.php`. The schema lives in `sql/`, and `bin/orbit migrate` is idempotent.

## License

[MIT](LICENSE) (c) 2026 master3395.
