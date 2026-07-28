<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Résout les identifiants du compte technique utilisé pour accéder aux albums
 * privés Piwigo.
 *
 * Les constantes wp-config.php sont prioritaires afin de permettre de garder
 * le secret hors de la base WordPress :
 *
 * define('WPD_PIWIGO_SERVICE_ENABLED', true);
 * define('WPD_PIWIGO_SERVICE_USERNAME', 'wordpress-publication');
 * define('WPD_PIWIGO_SERVICE_PASSWORD', 'mot-de-passe');
 */
final class WPD_Service_Account
{
    public const ENABLED_CONSTANT = 'WPD_PIWIGO_SERVICE_ENABLED';
    public const USERNAME_CONSTANT = 'WPD_PIWIGO_SERVICE_USERNAME';
    public const PASSWORD_CONSTANT = 'WPD_PIWIGO_SERVICE_PASSWORD';

    public static function is_enabled(): bool
    {
        if (defined(self::ENABLED_CONSTANT)) {
            return filter_var(constant(self::ENABLED_CONSTANT), FILTER_VALIDATE_BOOLEAN);
        }

        $options = self::get_options();

        return filter_var($options['service_account_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public static function get_username(): string
    {
        if (defined(self::USERNAME_CONSTANT)) {
            return sanitize_text_field((string) constant(self::USERNAME_CONSTANT));
        }

        $options = self::get_options();

        return sanitize_text_field((string) ($options['service_account_username'] ?? ''));
    }

    public static function get_password(): string
    {
        if (defined(self::PASSWORD_CONSTANT)) {
            return (string) constant(self::PASSWORD_CONSTANT);
        }

        $options = self::get_options();

        return (string) ($options['service_account_password'] ?? '');
    }

    public static function is_configured(): bool
    {
        return self::is_enabled()
            && self::get_username() !== ''
            && self::get_password() !== '';
    }

    public static function is_managed_by_constants(): bool
    {
        return defined(self::USERNAME_CONSTANT) || defined(self::PASSWORD_CONSTANT);
    }

    /**
     * Identifiant non réversible destiné exclusivement à séparer les caches.
     */
    public static function get_context_hash(): string
    {
        if (!self::is_configured()) {
            return 'anonymous';
        }

        return hash('sha256', WPD_Settings::get_piwigo_url() . '|' . self::get_username());
    }

    /**
     * Retourne uniquement des données non sensibles pour l'administration et
     * le diagnostic. Le mot de passe ne doit jamais être exposé.
     *
     * @return array{enabled: bool, configured: bool, username: string, source: string}
     */
    public static function get_public_status(): array
    {
        return [
            'enabled' => self::is_enabled(),
            'configured' => self::is_configured(),
            'username' => self::get_username(),
            'source' => self::is_managed_by_constants() ? 'wp-config.php' : 'database',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function get_options(): array
    {
        $options = get_option(WPD_Settings::OPTION_NAME, []);

        return is_array($options) ? $options : [];
    }
}
