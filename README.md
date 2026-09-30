# Gmail SMTP — Hướng dẫn cài đặt và sử dụng

Extension này thay thế hàm `mail()` mặc định của hosting bằng cách gửi thư qua **Gmail SMTP**.
Áp dụng cho **toàn bộ** email của WordPress, không riêng một tính năng.

---

## 1. Vấn đề cần giải quyết

Nhiều hosting Việt Nam không cấu hình dịch vụ gửi mail, nên WordPress mặc định dùng hàm `mail()` của PHP sẽ:

- Báo thành công nhưng email không tới (`wp_mail()` trả về `true`, email bị bỏ rơi).
- Bị mã hoá sai tiếng Việt hoặc lỗi charset khi tiếng Việt có dấu.
- Rơi vào thư mục spam vì không có bản ghi SPF/DKIM hợp lệ.

Extension giải quyết cả ba bằng cách nối trực tiếp tới Gmail qua kênh mã hoá TLS.

## 2. Extension hoạt động như thế nào

```
Email phát sinh  →  wp_mail()  →  PHPMailer  →  phpmailer_init  →  Gmail SMTP  →  Người nhận
                     (WordPress)                    (extension này)
```

Vì can thiệp ở hook `phpmailer_init`, **mọi** thành phần gọi `wp_mail()` đều tự động dùng Gmail:

| Thành phần | Nguồn email |
|---|---|
| `notification-system` | Kênh `EmailChannel` — thông báo trong site |
| `my-account` | Mã xác minh đăng ký / đặt lại mật khẩu |
| `tour-builder` | Xác nhận đặt tour, đơn hàng |
| `base-ecommerce` | Đơn hàng, thanh toán, email marketing |
| WordPress core | Đăng ký người dùng mới, đặt lại mật khẩu, bình luận |
| WooCommerce (nếu có) | Xác nhận đơn, hóa đơn, nhắc thanh toán |

Không cần sửa code của các extension khác.

## 3. Điều kiện trước khi bắt đầu

Tài liệu này viết cho **Gmail cá nhân** (tài khoản `@gmail.com` tự quản lý), không phải Google Workspace.

- [ ] Website đang chạy WordPress và extension `gmail-smtp` đã được bật trong trang quản trị theme.
- [ ] Bạn có một tài khoản **Gmail cá nhân**, nên tạo riêng một tài khoản cho website, ví dụ `nobitour.vn@gmail.com`.
- [ ] Tài khoản Gmail cá nhân đã bật Xác minh 2 bước.
- [ ] Hosting cho phép kết nối ra ngoài qua cổng `587` hoặc `465`. Nếu nhà cung cấp chặn toàn bộ SMTP outbound thì extension này cũng không cứu được — phải đổi hosting hoặc dùng dịch vụ gửi mail chuyên dụng.

> **Không phải Google Workspace?** Tài khoản công ty (`ten@tencongty.com` do Google Workspace quản lý) bị Google chặn App
> Password và bị giới hạn gửi theo chính sách của tổ chức. Tài liệu này không áp dụng cho trường hợp đó.

---

## 4. Bước 1 — Bật Xác minh 2 bước

1. Truy cập <https://myaccount.google.com/security>.
2. Chọn **Bảo mật** (Security).
3. Ở mục **Đăng nhập vào Google (Sign-in)**, chọn **Xác minh 2 bước** → **Bật**.
4. Xác nhận bằng mã gửi về điện thoại.

## 5. Bước 2 — Tạo Mật khẩu ứng dụng (App Password)

Gmail **không** cho phép dùng mật khẩu tài khoản để gửi thư qua SMTP. Thay vào đó phải dùng App Password.

1. Truy cập <https://myaccount.google.com/apppasswords>.
2. Chọn một thiết bị, ví dụ: `Nobitour Website`.
3. Chọn quyền: **Mail (Gửi email)**.
4. Bấm **Tạo**.
5. Google hiển thị một chuỗi **16 ký tự**, ví dụ `abcd efgh ijkl mnop`.
6. **Bấm "Xong"** và sao chép ngay chuỗi này — Google chỉ hiển thị đúng một lần.

Quy tắc khi dùng App Password:

- Xóa toàn bộ khoảng trắng: `abcd efgh ijkl mnop` → `abcdefghijklmnop`.
- App Password **không hết hạn theo thời gian**, nhưng bạn có thể thu hồi bất cứ lúc nào.
- Bảo mật giống mật khẩu tài khoản: không ghi vào tài liệu, không gửi qua chat/Zalo, không commit vào Git.

## 6. Bước 3 — Nhập cấu hình trong trang quản trị

Vào **Jankx Dashboard → Gmail SMTP** (URL: `wp-admin/admin.php?page=jankx-gmail-smtp`).

| Trường | Giá trị gợi ý | Giải thích |
|---|---|---|
| Bật Gmail SMTP | ✅ Tích | Tắt thì WordPress dùng mail() của hosting |
| Host | `smtp.gmail.com` | Cố định với Gmail |
| Port | `587` | Hoặc `465`. Xem bảng bên dưới |
| Mã hoá | `TLS (STARTTLS)` | Khớp với port 587 |
| Timeout | `30` | Giây chờ kết nối |
| Tài khoản | `nobitour.vn@gmail.com` | Gmail cá nhân của bạn |
| Mật khẩu ứng dụng | 16 ký tự vừa tạo | Để trống nếu không muốn đổi mật khẩu đã lưu |
| Xác thực SMTP | ✅ Tích | Gmail luôn bắt buộc |
| Email gửi đi | `nobitour.vn@gmail.com` | Trùng với tài khoản xác thực (xem mục 8) |
| Tên hiển thị | `Nobitour` | Không bắt buộc, mặc định lấy tên site |
| Ghi đè From | ✅ Tích | Bảo đảm mọi email dùng địa chỉ trên |
| Ghi log SMTP | ❌ Tắt | Chỉ bật khi đang lỗi, xem mục 9 |

### Chọn port nào?

| Port | Mã hoá | Khi nào dùng |
|---|---|---|
| `587` | `TLS (STARTTLS)` | **Khuyên dùng.** Hoạt động trên hầu hết hosting, kể cả hosting có tường lửa giới hạn |
| `465` | `SSL/TLS ngầm định` | Dùng nếu port 587 bị chặn. Nhiều hosting cũ chặn 465 |

Sau khi điền xong bấm **Lưu cấu hình**.

## 7. Bước 4 — Gửi email kiểm thử

1. Cuộn xuống mục **Gửi email kiểm thử**.
2. Nhập địa chỉ nhận thư (có thể là chính tài khoản Gmail của bạn).
3. Bấm **Gửi email thử**.
   - ✅ Thông báo xanh `Đã gửi email kiểm thử tới ...` → SMTP đã kết nối được.
   - ❌ Thông báo đỏ kèm lỗi cụ thể → xem mục **Khắc phục sự cố**.
4. Kiểm tra cả **Inbox**, **Spam** và **Promotions** (Gmail đôi khi xếp email tự động vào tab khác).
5. Thử lại một luồng thật của website, ví dụ đăng ký tài khoản mới để nhận mã xác minh.

## 8. Quy tắc bắt buộc của Gmail về địa chỉ gửi

Gmail chỉ cho phép gửi khi địa chỉ `From` **trùng với tài khoản đã xác thực**.

Với **Gmail cá nhân**, cách an toàn và chắc chắn nhất là gửi bằng chính địa chỉ `@gmail.com` của bạn:

- Để **Tài khoản** = **Email gửi đi** = `nobitour.vn@gmail.com`.
- Bật **Ghi đè From** để mọi email của website đều dùng địa chỉ này.

### Muốn gửi từ địa chỉ `@gmail.com` khác?

Nếu bạn có thêm địa chỉ Gmail (ví dụ `contact.nibitour@gmail.com`), thêm nó làm **bí danh (alias)**:

1. Vào <https://mail.google.com/mail/u/0/#settings> → *Xem tất cả cài đặt*.
2. Mục **Tài khoản và nhập** → *Thêm tài khoản email khác* → *Tạo bí danh email cho người dùng này*.
3. Gmail hiển thị địa chỉ mới kèm mã `@gmail.com`.
4. Quay lại trang cài đặt extension, đổi **Tài khoản** và **Email gửi đi** sang địa chỉ vừa tạo.

### Có thể gửi từ `support@nibitour.vn` được không?

Với Gmail cá nhân, gần như **không nên làm**. Gmail chỉ cho bạn thêm địa chỉ mà bạn có thể nhận mã xác minh, và khi gửi bằng địa chỉ ngoài `@gmail.com` thì chữ ký DKIM vẫn thuộc về `gmail.com` — nhiều máy chủ nhận sẽ đánh giá email là giả mạo và cho vào spam hoặc chặn hẳn.

Nếu thương hiệu bắt buộc phải dùng `support@nibitour.vn`, hãy dùng **Google Workspace** cho địa chỉ đó (kèm DNS theo mục 11), hoặc dịch vụ gửi mail chuyên dụng.

## 9. Ghi log SMTP để chẩn đoán

Bật **Ghi log SMTP** rồi gửi email thử, sau đó mở log lỗi PHP:

- XAMPP / Laragon: `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php_errors.log`
- Hosting cPanel: `/home/<user>/public_html/php_errors.log` hoặc **Errors** trong cPanel → *PHP Errors and Warnings*
- Hosting DirectAdmin/SaaS: trang **Logs** trong panel quản trị

Sau khi tìm ra nguyên nhân, **nhớ tắt lại Ghi log SMTP** vì log có thể chứa địa chỉ người nhận.

## 10. Hạn mức gửi của Gmail cá nhân

| Mức | Giá trị |
|---|---|
| Hạn mức chung | khoảng **500 email/ngày** |
| Số người nhận tối đa mỗi email | **20** (địa chỉ trong To/CC/BCC) |
| Số email tối đa gửi trong một lần nhập | **100** |

Hạn mức tính cho cả email bạn tự gửi tay lẫn email website gửi qua SMTP. Nếu vượt mức, Gmail sẽ từ chối và báo `550-5.7.1` hoặc `452-4.5.3`.

Đây là lý do nên **tạo một tài khoản Gmail riêng cho website**: những email bạn gửi cho khách hàng thủ công không làm hao hết hạn mức của site, và ngược lại.

Ngoài hạn mức, Gmail còn đánh giá "uy tín" người gửi. Nếu site gửi hàng loạt email marketing, hãy dùng dịch vụ chuyên dụng (Brevo, Mailchimp, Amazon SES...) thay vì Gmail.

## 11. DNS: SPF / DKIM / DMARC

> **Bạn không cần làm mục này.** Khi dùng Gmail cá nhân và gửi bằng địa chỉ `@gmail.com`, chữ ký DKIM và bản ghi SPF của
> Google đã có sẵn. Email gửi đi vẫn có dấu hiệu xác thực đầy đủ và rất ít khi rơi vào spam.

Mục này chỉ dành cho trường hợp bạn dùng địa chỉ của tên miền riêng (`support@nibitour.vn`) qua **Google Workspace**.

### SPF — cho phép Google gửi thay tên miền

Thêm bản ghi TXT tại zone DNS:

```
v=spf1 include:_spf.google.com ~all
```

### DKIM — ký email

Trong [Google Admin Console](https://admin.google.com) → *Apps* → *Gmail* → *Authenticate email*, bật **DKIM** rồi thêm bản ghi TXT mà Google cung cấp (thường có selector `_google`).

### DMARC — báo cáo và siết chặt

```
v=DMARC1; p=none; rua=mailto:dmarc@nibitour.vn
```

Sau khi đã đúng, đổi `p=none` thành `p=quarantine` rồi `p=reject`.

## 12. Khắc phục sự cố

| Thông báo lỗi | Nguyên nhân | Cách sửa |
|---|---|---|
| `Failed to authenticate` / `535-5.7.8 Username and Password not accepted` | Sai App Password hoặc mật khẩu ứng dụng bị thu hồi | Tạo lại App Password, dán đúng 16 ký tự đã bỏ khoảng trắng |
| `535-5.7.8 ... from address not authorized` | Địa chỉ gửi không trùng tài khoản xác thực | Sửa **Email gửi đi** cho trùng với **Tài khoản** (mục 8) |
| `550-5.7.1` hoặc `452-4.5.3` | Vượt hạn mức gửi của Gmail | Giảm số email/ngày, hoặc chuyển sang dịch vụ chuyên dụng (mục 10) |
| Không tạo được App Password ở trang Google | Đang dùng tài khoản Google Workspace, không phải Gmail cá nhân | Dùng đúng tài khoản `@gmail.com` (xem mục 3) |
| `Connection timed out` / `Failed to connect to smtp.gmail.com port 587` | Hosting chặn cổng 587 | Đổi port `465` + mã hoá `SSL/TLS ngầm định` |
| `SSL operation failed` / `wrong version number` | Đang dùng SSL ngầm định ở port 587 | Đặt port `587` + mã hoá `TLS (STARTTLS)`, hoặc ngược lại |
| `Access denied. Please visit https://support.google.com/accounts/answer/6010255` | Tài khoản không bật xác minh 2 bước hoặc bị Google chặn SMTP | Bật 2SV, kiểm tra thêm: `https://accounts.google.com/b/0/DisplaySMTPStatus` |
| Email vào Spam | Gửi quá nhiều trong thời gian ngắn, hoặc nội dung/liên kết bị nghi ngờ | Giảm tần suất gửi, bỏ link không cần thiết |
| `wp_mail()` trả về `false` nhưng không có thông báo | Bị chặn bởi plugin khác hoặc hosting | Tắt tạm các plugin gửi mail khác, xem error log |
| Không có lỗi nhưng email không tới | Hosting chặn hoàn toàn SMTP outbound | Liên hệ nhà cung cầp hosting mở cổng 587/465 |

### Tự kiểm tra kết nối SMTP không cần WordPress

Chạy lệnh sau trong terminal hosting (đổi thông tin tương ứng):

```bash
openssl s_client -starttls smtp -connect smtp.gmail.com:587 -crlf -quiet
```

Nếu nhận được `220 smtp.gmail.com` → kết nối được, vấn đề nằm ở cấu hình WordPress.
Nếu báo `Connection refused` hoặc treo → hosting đang chặn cổng này.

## 13. Bảo mật

- App Password được lưu trong bảng `wp_options` của WordPress. Hãy chắc chắn:
  - Chỉ tài khoản vai trò **Quản trị viên (Administrator)** được vào trang này (extension dùng quyền `manage_options`).
  - `wp-config.php` không bị truy cập công khai.
  - Sao lưu database được lưu ở nơi an toàn.
- Trang cài đặt **không bao giờ hiển thị lại** App Password — trường đó luôn hiển thị trống sau khi tải trang. Để trống khi lưu nghĩa là giữ nguyên mật khẩu cũ.
- Muốn đổi App Password: vào <https://myaccount.google.com/apppasswords> xoá mật khẩu cũ, tạo mật khẩu mới, rồi lưu lại trong trang cài đặt.
- Tắt **Ghi log SMTP** sau khi sửa xong lỗi.
- Không ghi App Password vào kho mã nguồn, file log, hoặc tin nhắn chat.

## 14. Tắt Gmail SMTP

Bỏ dấu **Bật Gmail SMTP** rồi lưu. Website quay về dùng mail mặc định của hosting. Toàn bộ cấu hình (kể cả App Password) được giữ nguyên, bật lại chỉ cần tích lại.

## 15. Dành cho lập trình viên

Bộ lọc để tùy biến hoặc đặt cấu hình qua code (ví dụ trong `wp-config.php` hoặc `functions.php` của child theme):

```php
// Thay đổi giá trị của một trường
add_filter('jankx/gmail_smtp/config', function ($value, $key) {
    if ($key === 'from_name') {
        return 'Nobitour Booking';
    }
    return $value;
}, 10, 2);

// Chặn gửi mail trong môi trường local/staging
add_filter('jankx/gmail_smtp/config', function ($value, $key) {
    if ($key === 'enabled' && (defined('WP_DEBUG') && WP_DEBUG)) {
        return 0;
    }
    return $value;
}, 10, 2);
```

Action phát ra sau khi PHPMailer đã được cấu hình xong:

```php
add_action('jankx/gmail_smtp/configured', function ($phpmailer, $config) {
    // $phpmailer là đối tượng PHPMailer\PHPMailer\PHPMailer
}, 10, 2);
```

Các tên option trong database đều có tiền tố `jankx_gmail_smtp_`:
`jankx_gmail_smtp_enabled`, `jankx_gmail_smtp_host`, `jankx_gmail_smtp_port`,
`jankx_gmail_smtp_encryption`, `jankx_gmail_smtp_auth`, `jankx_gmail_smtp_username`,
`jankx_gmail_smtp_password`, `jankx_gmail_smtp_from_email`, `jankx_gmail_smtp_from_name`,
`jankx_gmail_smtp_force_from`, `jankx_gmail_smtp_debug`, `jankx_gmail_smtp_timeout`.

---

## Cấu trúc thư mục

```
gmail-smtp/
├── manifest.json              Khai báo extension cho theme framework
├── GmailSmtpExtension.php     Lớp khởi tạo + autoloader
├── README.md                  Tài liệu này
└── src/
    ├── SmtpConfig.php         Đọc/ghi và kiểm tra cấu hình
    ├── SmtpMailer.php         Cấu hình PHPMailer qua hook phpmailer_init
    └── Admin/
        └── SettingsPage.php   Trang cài đặt trong wp-admin
```
