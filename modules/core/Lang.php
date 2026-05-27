<?php
namespace OrbitAdmin\Core;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

final class Lang
{
    /** @var array<string,array<string,string>> */
    private static array $bundles = [];

    private static string $current = 'en';

    public static function init(string $locale = 'en'): void
    {
        self::$current = $locale;
        self::$bundles = [
            'en' => self::english(),
            'nb' => self::norwegian(),
        ];
    }

    public static function set(string $locale): void
    {
        if (isset(self::$bundles[$locale])) {
            self::$current = $locale;
        }
    }

    public static function get(string $key, ?string $default = null): string
    {
        $bundle = self::$bundles[self::$current] ?? [];
        if (isset($bundle[$key])) {
            return $bundle[$key];
        }
        $fallback = self::$bundles['en'] ?? [];
        return $fallback[$key] ?? ($default ?? $key);
    }

    /** @return array<string,string> */
    private static function english(): array
    {
        return [
            'app.name'         => 'OrbitAdmin',
            'app.tagline'      => 'Mission control for your server',
            'nav.dashboard'    => 'Dashboard',
            'nav.users'        => 'Users',
            'nav.roles'        => 'Roles',
            'nav.activity'     => 'Activity log',
            'nav.tokens'       => 'API tokens',
            'nav.emails'       => 'Email templates',
            'nav.files'        => 'File manager',
            'nav.system'       => 'System info',
            'nav.settings'     => 'Settings',
            'nav.profile'      => 'Profile',
            'nav.logout'       => 'Sign out',
            'auth.signin'      => 'Sign in',
            'auth.username'    => 'Username or email',
            'auth.password'    => 'Password',
            'auth.remember'    => 'Remember me',
            'auth.failed'      => 'Invalid credentials.',
            'auth.welcome'     => 'Welcome back.',
            'common.save'      => 'Save',
            'common.cancel'    => 'Cancel',
            'common.delete'    => 'Delete',
            'common.create'    => 'Create',
            'common.edit'      => 'Edit',
            'common.search'    => 'Search',
            'common.back'      => 'Back',
            'common.confirm'   => 'Confirm',
            'common.actions'   => 'Actions',
            'common.yes'       => 'Yes',
            'common.no'        => 'No',
        ];
    }

    /** @return array<string,string> */
    private static function norwegian(): array
    {
        return [
            'app.name'         => 'OrbitAdmin',
            'app.tagline'      => 'Kontrollsentral for serveren din',
            'nav.dashboard'    => 'Oversikt',
            'nav.users'        => 'Brukere',
            'nav.roles'        => 'Roller',
            'nav.activity'     => 'Aktivitetslogg',
            'nav.tokens'       => 'API-tokens',
            'nav.emails'       => 'E-postmaler',
            'nav.files'        => 'Filbehandler',
            'nav.system'       => 'Systeminfo',
            'nav.settings'     => 'Innstillinger',
            'nav.profile'      => 'Profil',
            'nav.logout'       => 'Logg ut',
            'auth.signin'      => 'Logg inn',
            'auth.username'    => 'Brukernavn eller e-post',
            'auth.password'    => 'Passord',
            'auth.remember'    => 'Husk meg',
            'auth.failed'      => 'Ugyldig brukernavn eller passord.',
            'auth.welcome'     => 'Velkommen tilbake.',
            'common.save'      => 'Lagre',
            'common.cancel'    => 'Avbryt',
            'common.delete'    => 'Slett',
            'common.create'    => 'Opprett',
            'common.edit'      => 'Rediger',
            'common.search'    => 'Sok',
            'common.back'      => 'Tilbake',
            'common.confirm'   => 'Bekreft',
            'common.actions'   => 'Handlinger',
            'common.yes'       => 'Ja',
            'common.no'        => 'Nei',
        ];
    }
}
