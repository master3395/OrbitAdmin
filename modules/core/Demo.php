<?php
namespace OrbitAdmin\Core;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Demo mode guardrail: blocks destructive writes on the seeded admin and Owner role
 * so visitors cannot break the live demo.
 */
final class Demo
{
    public static function active(): bool
    {
        return Config::isDemo();
    }

    public static function guard(string $action, array $context = []): bool
    {
        if (!self::active()) {
            return true;
        }
        $protectedUserIds = [1];
        $protectedRoleIds = [1];

        if (isset($context['user_id']) && in_array((int) $context['user_id'], $protectedUserIds, true)
            && in_array($action, ['delete', 'edit', 'password'], true)) {
            Flash::warn('Demo mode: the seeded admin user is read-only. Create a new user to test changes.');
            return false;
        }
        if (isset($context['role_id']) && in_array((int) $context['role_id'], $protectedRoleIds, true)
            && in_array($action, ['delete', 'edit'], true)) {
            Flash::warn('Demo mode: the Owner role is read-only.');
            return false;
        }
        if ($action === 'install') {
            Flash::warn('Demo mode: the installer is disabled.');
            return false;
        }
        return true;
    }

    public static function bannerHtml(): string
    {
        if (!self::active()) {
            return '';
        }
        return '<div class="orbit-demo-banner">Demo mode: data resets periodically; some destructive actions are blocked.</div>';
    }
}
