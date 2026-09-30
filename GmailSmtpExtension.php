<?php

namespace Jankx\Extensions\GmailSmtp;

use Jankx\Extensions\AbstractExtension;

class GmailSmtpExtension extends AbstractExtension
{
    protected static ?self $instance = null;

    private ?SmtpConfig $config = null;

    public function __construct()
    {
        $this->registerAutoloader();
        parent::__construct();
    }

    private function registerAutoloader(): void
    {
        spl_autoload_register(function ($class) {
            $prefix = 'Jankx\\Extensions\\GmailSmtp\\';
            $baseDir = __DIR__ . '/src/';
            $len = strlen($prefix);

            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
            if (file_exists($file)) {
                require $file;
            }
        });
    }

    public function init(): void
    {
        self::$instance = $this;
    }

    public static function instance(): ?self
    {
        return self::$instance;
    }

    public function config(): SmtpConfig
    {
        if ($this->config === null) {
            $this->config = new SmtpConfig();
        }

        return $this->config;
    }

    public function register_hooks(): void
    {
        (new SmtpMailer($this->config()))->register();

        if (is_admin()) {
            (new Admin\SettingsPage($this->config()))->register();
        }
    }
}