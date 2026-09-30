<?php

namespace Jankx\Extensions\GmailSmtp\Admin;

use Jankx\Extensions\GmailSmtp\SmtpConfig;

class SettingsPage
{
    const PAGE_SLUG = 'jankx-gmail-smtp';
    const OPTION_GROUP = 'jankx_gmail_smtp_settings';
    const TEST_NONCE = 'jankx_gmail_smtp_test';

    private SmtpConfig $config;

    public function __construct(SmtpConfig $config)
    {
        $this->config = $config;
    }

    private function opt(string $key): string
    {
        return SmtpConfig::OPTION_PREFIX . $key;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu'], 25);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addMenu(): void
    {
        add_submenu_page(
            'jankx-dashboard',
            __('Gmail SMTP', 'jankx'),
            __('Gmail SMTP', 'jankx'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
        $group = self::OPTION_GROUP;

        register_setting($group, $this->opt(SmtpConfig::ENABLED), [
            'default'           => 0,
            'type'              => 'integer',
            'sanitize_callback' => [$this->config, 'sanitizeBool'],
            'show_in_rest'      => false,
        ]);

        register_setting($group, $this->opt(SmtpConfig::HOST), [
            'default'           => 'smtp.gmail.com',
            'type'              => 'string',
            'sanitize_callback' => [$this->config, 'sanitizeHost'],
            'show_in_rest'      => false,
        ]);

        register_setting($group, $this->opt(SmtpConfig::PORT), [
            'default'           => 587,
            'type'              => 'integer',
            'sanitize_callback' => [$this->config, 'sanitizePort'],
            'show_in_rest'      => false,
        ]);

        register_setting($group, $this->opt(SmtpConfig::ENCRYPTION), [
            'default'           => 'tls',
            'type'              => 'string',
            'sanitize_callback' => [$this->config, 'sanitizeEncryption'],
            'show_in_rest'      => false,
        ]);

        register_setting($group, $this->opt(SmtpConfig::AUTH), [
            'default'           => 1,
            'type'              => 'integer',
            'sanitize_callback' => [$this->config, 'sanitizeBool'],
            'show_in_rest'      => false,
        ]);

        register_setting($group, $this->opt(SmtpConfig::USERNAME), [
            'default'           => '',
            'type'              => 'string',
            'sanitize_callback' => [$this->config, 'sanitizeEmail'],
            'show_in_rest'      => false,
        ]);

        register_setting($group, $this->opt(SmtpConfig::PASSWORD), [
            'default'           => '',
            'type'              => 'string',
            'sanitize_callback' => [$this->config, 'sanitizeSecret'],
            'show_in_rest'      => false,
        ]);

        register_setting($group, $this->opt(SmtpConfig::FROM_EMAIL), [
            'default'           => '',
            'type'              => 'string',
            'sanitize_callback' => [$this->config, 'sanitizeEmail'],
            'show_in_rest'      => false,
        ]);

        register_setting($group, $this->opt(SmtpConfig::FROM_NAME), [
            'default'           => '',
            'type'              => 'string',
            'sanitize_callback' => [$this->config, 'sanitizeText'],
            'show_in_rest'      => false,
        ]);

        register_setting($group, $this->opt(SmtpConfig::FORCE_FROM), [
            'default'           => 1,
            'type'              => 'integer',
            'sanitize_callback' => [$this->config, 'sanitizeBool'],
            'show_in_rest'      => false,
        ]);

        register_setting($group, $this->opt(SmtpConfig::DEBUG), [
            'default'           => 0,
            'type'              => 'integer',
            'sanitize_callback' => [$this->config, 'sanitizeBool'],
            'show_in_rest'      => false,
        ]);

        register_setting($group, $this->opt(SmtpConfig::TIMEOUT), [
            'default'           => 30,
            'type'              => 'integer',
            'sanitize_callback' => [$this->config, 'sanitizeTimeout'],
            'show_in_rest'      => false,
        ]);
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Bạn không có quyền truy cập trang này.', 'jankx'));
        }

        $testResult = $this->handleTestEmail();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Gmail SMTP', 'jankx'); ?></h1>
            <p class="description">
                <?php esc_html_e('Gửi email của toàn site (wp_mail) qua Gmail thay cho hàm mail() mặc định của hosting.', 'jankx'); ?>
            </p>

            <?php if (!$this->config->hasCredentials()): ?>
                <div class="notice notice-warning inline">
                    <p>
                        <?php esc_html_e('Chưa nhập Tài khoản Gmail hoặc Mật khẩu ứng dụng. Xem tài liệu README.md của extension để biết cách tạo App Password.', 'jankx'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($this->config->hasCredentials() && !$this->config->isPersonalGmail()): ?>
                <div class="notice notice-warning inline">
                    <p>
                        <strong><?php esc_html_e('Tài khoản không phải Gmail cá nhân.', 'jankx'); ?></strong>
                        <?php esc_html_e('Gmail chỉ cấp Mật khẩu ứng dụng cho tài khoản @gmail.com. Tài khoản do công ty quản lý (Google Workspace) sẽ không tạo được App Password và Gmail thường chặn địa chỉ gửi không thuộc tài khoản đó.', 'jankx'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($this->config->isPersonalGmail() && !$this->config->isFromAuthorized()): ?>
                <div class="notice notice-warning inline">
                    <p>
                        <strong><?php esc_html_e('Địa chỉ gửi đi không khớp tài khoản.', 'jankx'); ?></strong>
                        <?php esc_html_e('Gmail cá nhân chỉ cho gửi khi Email gửi đi trùng với Tài khoản, hoặc là một bí danh @gmail.com đã tạo trong chính tài khoản đó. Nếu không, Gmail sẽ từ chối với lỗi 535-5.7.8.', 'jankx'); ?>
                    </p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php" style="max-width:760px;">
                <?php settings_fields(self::OPTION_GROUP); ?>

                <h2><?php esc_html_e('Kích hoạt', 'jankx'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($this->opt(SmtpConfig::ENABLED)); ?>">
                                <?php esc_html_e('Bật Gmail SMTP', 'jankx'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="<?php echo esc_attr($this->opt(SmtpConfig::ENABLED)); ?>"
                                   name="<?php echo esc_attr($this->opt(SmtpConfig::ENABLED)); ?>"
                                   value="1"
                                   <?php checked((bool) $this->config->get(SmtpConfig::ENABLED), true); ?>>
                            <p class="description">
                                <?php esc_html_e('Khi tắt, WordPress trở lại dùng cấu hình mail mặc định của hosting.', 'jankx'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Máy chủ SMTP', 'jankx'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($this->opt(SmtpConfig::HOST)); ?>">
                                <?php esc_html_e('Host', 'jankx'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                   id="<?php echo esc_attr($this->opt(SmtpConfig::HOST)); ?>"
                                   name="<?php echo esc_attr($this->opt(SmtpConfig::HOST)); ?>"
                                   value="<?php echo esc_attr($this->config->get(SmtpConfig::HOST)); ?>"
                                   class="regular-text">
                            <p class="description"><?php esc_html_e('Gmail: smtp.gmail.com', 'jankx'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($this->opt(SmtpConfig::PORT)); ?>">
                                <?php esc_html_e('Port', 'jankx'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number"
                                   id="<?php echo esc_attr($this->opt(SmtpConfig::PORT)); ?>"
                                   name="<?php echo esc_attr($this->opt(SmtpConfig::PORT)); ?>"
                                   value="<?php echo esc_attr((string) $this->config->get(SmtpConfig::PORT)); ?>"
                                   class="small-text"
                                   min="1"
                                   max="65535">
                            <p class="description"><?php esc_html_e('587 (STARTTLS) hoặc 465 (SSL/TLS).', 'jankx'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($this->opt(SmtpConfig::ENCRYPTION)); ?>">
                                <?php esc_html_e('Mã hoá', 'jankx'); ?>
                            </label>
                        </th>
                        <td>
                            <select id="<?php echo esc_attr($this->opt(SmtpConfig::ENCRYPTION)); ?>"
                                    name="<?php echo esc_attr($this->opt(SmtpConfig::ENCRYPTION)); ?>">
                                <?php $current = (string) $this->config->get(SmtpConfig::ENCRYPTION); ?>
                                <option value="tls" <?php selected($current, 'tls'); ?>><?php esc_html_e('TLS (STARTTLS)', 'jankx'); ?></option>
                                <option value="ssl" <?php selected($current, 'ssl'); ?>><?php esc_html_e('SSL/TLS ngầm định', 'jankx'); ?></option>
                                <option value="none" <?php selected($current, 'none'); ?>><?php esc_html_e('Không mã hoá', 'jankx'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Port 587 nên chọn TLS, port 465 nên chọn SSL/TLS ngầm định.', 'jankx'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($this->opt(SmtpConfig::TIMEOUT)); ?>">
                                <?php esc_html_e('Timeout (giây)', 'jankx'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number"
                                   id="<?php echo esc_attr($this->opt(SmtpConfig::TIMEOUT)); ?>"
                                   name="<?php echo esc_attr($this->opt(SmtpConfig::TIMEOUT)); ?>"
                                   value="<?php echo esc_attr((string) $this->config->get(SmtpConfig::TIMEOUT)); ?>"
                                   class="small-text"
                                   min="1"
                                   max="300">
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Tài khoản Gmail', 'jankx'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($this->opt(SmtpConfig::USERNAME)); ?>">
                                <?php esc_html_e('Tài khoản', 'jankx'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="email"
                                   id="<?php echo esc_attr($this->opt(SmtpConfig::USERNAME)); ?>"
                                   name="<?php echo esc_attr($this->opt(SmtpConfig::USERNAME)); ?>"
                                   value="<?php echo esc_attr($this->config->get(SmtpConfig::USERNAME)); ?>"
                                   class="regular-text"
                                   autocomplete="off">
                            <p class="description"><?php esc_html_e('Gmail cá nhân: tên đăng nhập của bạn, ví dụ nobitour.vn@gmail.com', 'jankx'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($this->opt(SmtpConfig::PASSWORD)); ?>">
                                <?php esc_html_e('Mật khẩu ứng dụng', 'jankx'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="password"
                                   id="<?php echo esc_attr($this->opt(SmtpConfig::PASSWORD)); ?>"
                                   name="<?php echo esc_attr($this->opt(SmtpConfig::PASSWORD)); ?>"
                                   value=""
                                   class="regular-text"
                                   autocomplete="new-password">
                            <p class="description">
                                <?php esc_html_e('Dán mật khẩu ứng dụng gồm 16 ký tự. Để trống nếu muốn giữ nguyên mật khẩu đã lưu.', 'jankx'); ?>
                                <?php if ((string) $this->config->get(SmtpConfig::PASSWORD) !== ''): ?>
                                    <strong><?php esc_html_e('Đang lưu mật khẩu.', 'jankx'); ?></strong>
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($this->opt(SmtpConfig::AUTH)); ?>">
                                <?php esc_html_e('Xác thực SMTP', 'jankx'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="<?php echo esc_attr($this->opt(SmtpConfig::AUTH)); ?>"
                                   name="<?php echo esc_attr($this->opt(SmtpConfig::AUTH)); ?>"
                                   value="1"
                                   <?php checked((bool) $this->config->get(SmtpConfig::AUTH), true); ?>>
                            <p class="description"><?php esc_html_e('Gmail luôn yêu cầu bật xác thực.', 'jankx'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Địa chỉ gửi', 'jankx'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($this->opt(SmtpConfig::FROM_EMAIL)); ?>">
                                <?php esc_html_e('Email gửi đi', 'jankx'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="email"
                                   id="<?php echo esc_attr($this->opt(SmtpConfig::FROM_EMAIL)); ?>"
                                   name="<?php echo esc_attr($this->opt(SmtpConfig::FROM_EMAIL)); ?>"
                                   value="<?php echo esc_attr($this->config->get(SmtpConfig::FROM_EMAIL)); ?>"
                                   class="regular-text"
                                   placeholder="<?php echo esc_attr($this->config->get(SmtpConfig::USERNAME)); ?>">
                            <p class="description"><?php esc_html_e('Gmail cá nhân chỉ cho gửi khi địa chỉ này trùng với Tài khoản (hoặc là bí danh đã tạo trong chính tài khoản đó).', 'jankx'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($this->opt(SmtpConfig::FROM_NAME)); ?>">
                                <?php esc_html_e('Tên hiển thị', 'jankx'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text"
                                   id="<?php echo esc_attr($this->opt(SmtpConfig::FROM_NAME)); ?>"
                                   name="<?php echo esc_attr($this->opt(SmtpConfig::FROM_NAME)); ?>"
                                   value="<?php echo esc_attr($this->config->get(SmtpConfig::FROM_NAME)); ?>"
                                   class="regular-text"
                                   placeholder="<?php echo esc_attr((string) get_bloginfo('name')); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($this->opt(SmtpConfig::FORCE_FROM)); ?>">
                                <?php esc_html_e('Ghi đè From', 'jankx'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="<?php echo esc_attr($this->opt(SmtpConfig::FORCE_FROM)); ?>"
                                   name="<?php echo esc_attr($this->opt(SmtpConfig::FORCE_FROM)); ?>"
                                   value="1"
                                   <?php checked((bool) $this->config->get(SmtpConfig::FORCE_FROM), true); ?>>
                            <p class="description"><?php esc_html_e('Bật để mọi email đều dùng địa chỉ gửi đi ở trên.', 'jankx'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Chẩn đoán', 'jankx'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($this->opt(SmtpConfig::DEBUG)); ?>">
                                <?php esc_html_e('Ghi log SMTP', 'jankx'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="<?php echo esc_attr($this->opt(SmtpConfig::DEBUG)); ?>"
                                   name="<?php echo esc_attr($this->opt(SmtpConfig::DEBUG)); ?>"
                                   value="1"
                                   <?php checked((bool) $this->config->get(SmtpConfig::DEBUG), true); ?>>
                            <p class="description"><?php esc_html_e('Chỉ bật khi đang lỗi. Nội dung log ghi vào error_log của PHP, có thể chứa thông tin nhạy cảm.', 'jankx'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Lưu cấu hình', 'jankx')); ?>
            </form>

            <hr>

            <h2><?php esc_html_e('Gửi email kiểm thử', 'jankx'); ?></h2>

            <?php if (is_array($testResult)): ?>
                <div class="notice notice-<?php echo $testResult['success'] ? 'success' : 'error'; ?> inline">
                    <p><?php echo esc_html($testResult['message']); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" style="max-width:760px;">
                <?php wp_nonce_field(self::TEST_NONCE); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Gửi đến', 'jankx'); ?>
                        </th>
                        <td>
                            <p>
                                <code><?php echo esc_html($this->currentUserEmail()); ?></code>
                            </p>
                            <p class="description">
                                <?php esc_html_e('Email kiểm thử luôn được gửi tới địa chỉ của tài khoản đang đăng nhập.', 'jankx'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Gửi email thử', 'jankx'), 'secondary', 'jankx_gmail_smtp_send_test', false); ?>
            </form>
        </div>
        <?php
    }

    private function currentUserEmail(): string
    {
        $user = wp_get_current_user();

        if (!$user || !$user->exists()) {
            return '';
        }

        return (string) $user->user_email;
    }

    private function handleTestEmail(): ?array
    {
        if (!isset($_POST['jankx_gmail_smtp_send_test'])) {
            return null;
        }

        if (!current_user_can('manage_options')) {
            return null;
        }

        check_admin_referer(self::TEST_NONCE);

        $to = sanitize_email($this->currentUserEmail());

        if (!is_email($to)) {
            return [
                'success' => false,
                'message' => __('Không xác định được email của tài khoản đang đăng nhập. Hãy kiểm tra lại trang Hồ sơ người dùng.', 'jankx'),
            ];
        }

        if (!$this->config->isReady()) {
            return [
                'success' => false,
                'message' => __('Vui lòng bật Gmail SMTP và nhập đủ tài khoản cùng mật khẩu ứng dụng trước khi kiểm thử.', 'jankx'),
            ];
        }

        $subject = sprintf(
            /* translators: %s: site name */
            __('[%s] Kiểm thử gửi email qua Gmail SMTP', 'jankx'),
            wp_specialchars_decode((string) get_option('blogname'), ENT_QUOTES)
        );

        $body = sprintf(
            /* translators: %s: site name */
            __('Email này được gửi từ %s để xác nhận cấu hình Gmail SMTP đã hoạt động.', 'jankx'),
            wp_specialchars_decode((string) get_option('blogname'), ENT_QUOTES)
        );

        $failure = null;
        $capture = static function ($error) use (&$failure) {
            $failure = $error;
        };

        add_action('wp_mail_failed', $capture);
        $sent = wp_mail($to, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
        remove_action('wp_mail_failed', $capture);

        if ($sent) {
            return [
                'success' => true,
                'message' => sprintf(
                    /* translators: %s: recipient email */
                    __('Đã gửi email kiểm thử tới %s.', 'jankx'),
                    $to
                ),
            ];
        }

        $message = __('Không gửi được email. Bật "Ghi log SMTP" rồi kiểm tra error_log để biết nguyên nhân.', 'jankx');

        if ($failure instanceof \WP_Error) {
            $message = $failure->get_error_message();
        }

        return [
            'success' => false,
            'message' => $message,
        ];
    }
}
