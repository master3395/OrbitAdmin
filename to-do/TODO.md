# Backlog

Tracked work for future OrbitAdmin releases.

## v0.2 candidates

- [ ] WebSocket live tiles on the dashboard (PHP + Mercure or a Node side car).
- [ ] OAuth/SSO providers (GitHub, Google, generic OIDC).
- [ ] Plugin system: drop a folder under `modules/<vendor>__<plugin>/` and have it picked up automatically.
- [ ] Theme editor surface inside Settings (the CSS variables are already there).
- [ ] 2FA TOTP UI (the user column `totp_secret` is already in the schema).
- [ ] Per-role per-route guard middleware on top of `Auth::requireRole`.
- [ ] CLI scaffolding command `orbit make:module <name>`.

## Documentation

- [ ] Screencast on the live demo.
- [ ] Module authoring guide expanded with examples.

## Quality

- [ ] PHPUnit pact in addition to `Test/smoke_test.php`.
- [ ] PHPStan level 6 baseline.
