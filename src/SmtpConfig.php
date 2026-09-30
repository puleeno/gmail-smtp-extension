<?php

namespace Jankx\Extensions\GmailSmtp;

class SmtpConfig
{
    const OPTION_PREFIX = 'jankx_gmail_smtp_';

    const ENABLED = 'enabled';
    const HOST = 'host';
    const PORT = 'port';
    const ENCRYPTION = 'encryption';
    const AUTH = 'auth';
    const USERNAME = 'username';
    const PASSWORD = 'password';
    const FROM_EMAIL = 'from_email';
    const FROM_NAME = 'from_name';
    const FORCE_FROM = 'force_from';
    const DEBUG = 'debug';
    const TIMEOUT = 'timeout';

    const ENCRYPTIONS = ['ssl', 'tls', 'none'];

    public function defaults(): array
    {
        return [
            self::ENABLED    => 0,
            self::HOST       => 'smtp.gmail.com',
            self::PORT       => 587,
            self::ENCRYPTION => 'tls',
            self::AUTH       => 1,
            self::USERNAME   => '',
            self::PASSWORD   => '',
            self::FROM_EMAIL => '',
            self::FROM_NAME  => '',
            self::FORCE_FROM => 1,
            self::DEBUG      => 0,
            self::TIMEOUT    => 30,
        ];
    }

    public function optionName(string $key): string
    {
        return self::OPTION_PREFIX . $key;
    }

    public function keys(): array
    {
        return array_keys($this->defaults());
    }

    /**
     * @return mixed
     */
    public function get(string $key)
    {
        $defaults = $this->defaults();
        $default = array_key_exists($key, $defaults) ? $defaults[$key] : '';
        $value = get_option($this->optionName($key), $default);

        return apply_filters('jankx/gmail_smtp/config', $value, $key, $default);
    }

    public function all(): array
    {
        $config = [];

        foreach ($this->keys() as $key) {
            $config[$key] = $this->get($key);
        }

        return apply_filters('jankx/gmail_smtp/all_config', $config);
    }

    public function isEnabled(): bool
    {
        return (bool) $this->get(self::ENABLED);
    }

    public function hasCredentials(): bool
    {
        return (string) $this->get(self::USERNAME) !== '' && (string) $this->get(self::PASSWORD) !== '';
    }

    public function isReady(): bool
    {
        return $this->isEnabled()
            && (string) $this->get(self::HOST) !== ''
            && $this->hasCredentials();
    }

    /**
     * Gmail cá nhân chỉ xác thực được bằng địa chỉ @gmail.com.
     */
    public function isPersonalGmail(): bool
    {
        return $this->emailDomain((string) $this->get(self::USERNAME)) === 'gmail.com';
    }

    /**
     * Gmail cá nhân chỉ cho gửi khi From trùng tài khoản đã xác thực
     * (hoặc là một bí danh @gmail.com của chính tài khoản đó).
     */
    public function isFromAuthorized(): bool
    {
        $from = $this->getFromEmail();

        if ($from === '') {
            return true;
        }

        return strcasecmp($from, (string) $this->get(self::USERNAME)) === 0;
    }

    public function emailDomain(string $email): string
    {
        $position = strrpos($email, '@');

        if ($position === false) {
            return '';
        }

        return strtolower(substr($email, $position + 1));
    }

    /**
     * @return string '' | 'ssl' | 'tls'
     */
    public function resolveEncryption(): string
    {
        if ($this->getPort() === 465) {
            return 'ssl';
        }

        $encryption = strtolower(trim((string) $this->get(self::ENCRYPTION)));

        if ($encryption === 'none') {
            return '';
        }

        if ($encryption === 'tls') {
            return 'tls';
        }

        return 'tls';
    }

    public function getPort(): int
    {
        $port = (int) $this->get(self::PORT);

        return ($port > 0 && $port <= 65535) ? $port : 587;
    }

    public function getTimeout(): int
    {
        $timeout = (int) $this->get(self::TIMEOUT);

        return $timeout > 0 ? $timeout : 30;
    }

    public function getFromEmail(): string
    {
        $email = sanitize_email((string) $this->get(self::FROM_EMAIL));

        return is_email($email) ? $email : '';
    }

    public function getFromName(): string
    {
        $name = trim((string) $this->get(self::FROM_NAME));

        return $name !== '' ? $name : (string) get_bloginfo('name');
    }

    public function sanitizeBool($value): int
    {
        return empty($value) ? 0 : 1;
    }

    public function sanitizeHost($value): string
    {
        $host = sanitize_text_field((string) $value);

        return trim($host);
    }

    public function sanitizePort($value): int
    {
        $port = absint($value);

        return ($port > 0 && $port <= 65535) ? $port : 587;
    }

    public function sanitizeEncryption($value): string
    {
        $encryption = strtolower(sanitize_key((string) $value));

        return in_array($encryption, self::ENCRYPTIONS, true) ? $encryption : 'tls';
    }

    public function sanitizeEmail($value): string
    {
        $email = sanitize_email((string) $value);

        return is_email($email) ? $email : '';
    }

    public function sanitizeText($value): string
    {
        return sanitize_text_field((string) $value);
    }

    public function sanitizeTimeout($value): int
    {
        $timeout = absint($value);

        return $timeout > 0 ? min($timeout, 300) : 30;
    }

    public function sanitizeSecret($value): string
    {
        $secret = trim((string) $value);

        return $secret !== '' ? $secret : (string) get_option($this->optionName(self::PASSWORD), '');
    }
}