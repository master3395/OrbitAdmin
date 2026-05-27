# Architecture

OrbitAdmin is a thin PHP application with a hand-rolled router, a small core, and a pluggable storage layer.

## High-level flow

```
Browser -> /OrbitAdmin/.htaccess -> /OrbitAdmin/public/.htaccess -> public/index.php
        -> bootstrap.php (autoload, session, CSP)
        -> modules/core/Router
        -> modules/<feature>/<action>.php (controllers, < 500 lines each)
        -> modules/db/Database (factory) -> Mysql/Sqlite/Json driver
        -> views/layouts/* + views/pages/* (rendered via core/View)
```

## Folder responsibility

- `public/` is the only externally reachable folder. The bundled top-level `.htaccess` rewrites `/OrbitAdmin/*` into `public/*` so the URL stays clean.
- `bootstrap.php` boots the autoloader, loads `config.php`, sets up the session, applies security headers, and shares common view variables.
- `modules/core/` holds Router, Auth, Csrf, Session, View, Logger, Security, RateLimiter, Mailer, Validator, Url, Lang, Demo, Helpers. Every file is below 500 lines.
- `modules/db/` exposes a `DriverInterface` and three drivers. Use `OrbitAdmin\Db\Database::instance()` everywhere.
- `modules/<feature>/` holds controller scripts pointed to by the router. They render views and return JSON helpers.
- `modules/activity/ActivityLog.php` writes hash-chained rows. Use `ActivityLog::record('user.create', 'user#7', ['extra' => '...'])`.
- `views/layouts/app.php` is the main shell; `views/layouts/auth.php` is the login/installer surface.
- `bin/orbit` is the CLI entry. It calls `bootstrap.php` and dispatches commands; add new commands by dropping a file under `modules/<name>/cli/` and listing it via `orbit list`.

## Adding a module

1. Create a new folder under `modules/<name>/`.
2. Add controller scripts (e.g. `list.php`, `edit.php`); each must start with the `ORBIT_INIT` guard and call `Auth::requireLogin()` (or `requireRole(...)`).
3. Register routes in `public/index.php`.
4. Add view pages under `views/pages/<name>/`.
5. Optional: add a navigation entry in `views/partials/sidebar.php`.
6. Optional: add a CLI command under `modules/<name>/cli/`.

## Demo guardrail

`OrbitAdmin\Core\Demo::guard($action, $context)` returns false (and flashes a warning) when `APP_DEMO=true` and the action targets the seeded admin (id 1) or the Owner role (id 1). Controllers respect this before persisting destructive changes.
