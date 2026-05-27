# Installing OrbitAdmin

OrbitAdmin can run on any LAMP-style stack with PHP 7.4 to 8.6. It is tested on AlmaLinux 8.10 and 9.6 with OpenLiteSpeed and LiteSpeed Enterprise.

## 1. Drop the folder into your web root

Place the `OrbitAdmin/` folder inside the document root of an existing vhost (for example `/home/yourdomain.com/public_html/OrbitAdmin/`). The bundled top-level `.htaccess` rewrites `/OrbitAdmin/*` requests into `/OrbitAdmin/public/*`, so URLs stay clean.

## 2. Install (web wizard or CLI)

### Web wizard

Visit `https://yourdomain.com/OrbitAdmin/install`. The wizard runs a server readiness check, lets you pick a driver, prompts for the first admin user, writes `config.php`, applies the schema, and locks itself by creating `data/.installed`.

### CLI

```bash
cd /path/to/OrbitAdmin
php bin/orbit install
```

The CLI installer prompts the same fields in interactive mode and supports headless use via flags: `--username --email --password --role`.

## 3. Pick a driver

| Driver | When to choose | Setup |
|--------|----------------|-------|
| json   | demos, homelab, tiny installs | none |
| sqlite | small projects, embedded | none (file lives at `data/orbit.sqlite`) |
| mysql  | production, multi-instance | create the database; the schema applies on first migrate |

You can switch later by editing `DB_DRIVER` in `config.php` and running `php bin/orbit migrate`.

## 4. Ownership and permissions

On the newstargeted.com production hosts, after install:

```
chown newst3922:nobody    /path/to/OrbitAdmin
chown -R newst3922:newst3922 /path/to/OrbitAdmin/*
chmod 600 /path/to/OrbitAdmin/config.php /path/to/OrbitAdmin/data/.installed
chmod 700 /path/to/OrbitAdmin/data /path/to/OrbitAdmin/logs
chmod 755 /path/to/OrbitAdmin/bin/orbit
```

## 5. Web server

The bundled `.htaccess` files work on OpenLiteSpeed and LiteSpeed Enterprise (Apache compatible). After editing `.htaccess` on LSWS, restart it. OLS reloads on the fly.

## 6. Useful CLI commands

```bash
php bin/orbit help
php bin/orbit info
php bin/orbit migrate
php bin/orbit user:add --username=jane --email=jane@example.com --password='********' --role=Editor
php bin/orbit token:create --user=jane --name='CI pipeline'
php bin/orbit demo:seed     # populate the JSON store with sample data
php bin/orbit demo:reset    # reset the JSON store from data/json.example/
php bin/orbit test          # smoke test
```
