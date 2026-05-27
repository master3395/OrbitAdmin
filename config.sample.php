<?php
/**
 * OrbitAdmin configuration template.
 *
 * 1. Copy this file to config.php (or let bin/orbit install create it for you).
 * 2. Fill in the values appropriate for your deployment.
 * 3. Keep config.php out of source control (.gitignore already excludes it).
 *
 * Generate a fresh APP_KEY:
 *   php -r "echo bin2hex(random_bytes(32)).PHP_EOL;"
 */

return [
    'APP_NAME'         => 'OrbitAdmin',
    'APP_TAGLINE'      => 'Mission control for your server',
    'APP_VERSION'      => '0.1.0',
    'APP_KEY'          => 'change-me-orbitadmin-demo-key-replace-on-deploy',
    'APP_DEBUG'        => false,
    'APP_DEMO'         => false,
    'APP_LOCALE'       => 'en',

    'BASE_URL'         => '/OrbitAdmin',
    'TRUST_PROXY'      => false,

    'DB_DRIVER'        => 'json',
    'SQLITE_PATH'      => null,
    'MYSQL_HOST'       => '127.0.0.1',
    'MYSQL_PORT'       => 3306,
    'MYSQL_DATABASE'   => 'orbitadmin',
    'MYSQL_USER'       => '',
    'MYSQL_PASSWORD'   => '',

    'SESSION_NAME'         => 'orbit_sid',
    'SESSION_IDLE_SECONDS' => 1800,

    'RATE_LOGIN_MAX'   => 8,
    'RATE_LOGIN_WIN'   => 300,
    'RATE_API_MAX'     => 120,
    'RATE_API_WIN'     => 60,

    'MAIL_DISABLED'    => true,
    'MAIL_FROM'        => 'no-reply@example.com',
    'MAIL_FROM_NAME'   => 'OrbitAdmin',
];
