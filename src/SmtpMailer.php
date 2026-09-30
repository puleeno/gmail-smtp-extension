<?php

namespace Jankx\Extensions\GmailSmtp;

use PHPMailer\PHPMailer\PHPMailer;

class SmtpMailer
{
    private SmtpConfig $config;

    public function __construct(SmtpConfig $config)
    {
        $this->config = $config;
    }

    public function register(): void
    {
        add_action('phpmailer_init', [$this, 'configure']);
    }

    public function configure(PHPMailer $phpmailer): void
    {
        if (!$this->config->isReady()) {
            return;
        }

        $secure = $this->config->resolveEncryption();

        $phpmailer->isSMTP();
        $phpmailer->Host = (string) $this->config->get(SmtpConfig::HOST);
        $phpmailer->Port = $this->config->getPort();
        $phpmailer->Timeout = $this->config->getTimeout();
        $phpmailer->SMTPAutoTLS = false;
        $phpmailer->SMTPSecure = $secure;
        $phpmailer->SMTPAuth = (bool) $this->config->get(SmtpConfig::AUTH);

        if ($phpmailer->SMTPAuth) {
            $phpmailer->Username = (string) $this->config->get(SmtpConfig::USERNAME);
            $phpmailer->Password = (string) $this->config->get(SmtpConfig::PASSWORD);
        }

        $fromEmail = $this->config->getFromEmail();
        if ($fromEmail !== '') {
            $phpmailer->setFrom(
                $fromEmail,
                $this->config->getFromName(),
                (bool) $this->config->get(SmtpConfig::FORCE_FROM)
            );
        }

        if ((bool) $this->config->get(SmtpConfig::DEBUG)) {
            $phpmailer->SMTPDebug = 2;
            $phpmailer->Debugoutput = 'error_log';
        } else {
            $phpmailer->SMTPDebug = 0;
        }

        do_action('jankx/gmail_smtp/configured', $phpmailer, $this->config->all());
    }
}