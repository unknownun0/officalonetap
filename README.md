# NFC Solutions - Three-Page Web Application

A complete web application with three standalone pages for NFC-enabled product display, admin management, and customer accounts.

## Pages

1. **index.php** - Public Product Page (no login required)
2. **admin.php** - Admin Panel (session-based login)
3. **account.php** - Customer Account Page (activation, login, profile, dashboard)

## Features

### Page 1: Public Product Page (index.php)
- Responsive product grid loaded from MySQL
- Modal product details with specifications
- Contact form (saves to database + emails admin)
- Mobile-first design, fast loading for NFC taps
- No navigation, no login required

### Page 2: Admin Panel (admin.php)
- Secure session-based authentication
- Generate new customers with unique activation codes
- QR code generation (Google Chart API)
- Customer list with status (Pending/Active)
- Revoke/delete customer access
- View/download QR codes

### Page 3: Customer Account (account.php)
- **Activation flow**: First visit → set password → activate
- **Public profile**: Read-only view when accessed by others
- **Dashboard**: Full edit access when logged in (own session)
- Profile fields: name, title, company, bio, phone
- Social links: Instagram, LinkedIn, Facebook, Twitter, GitHub, Website
- Password change, account deletion
- Data stored in MySQL (accessible from any device)

## Database Schema

Run `database.sql` in phpMyAdmin or MySQL CLI:

```sql
CREATE DATABASE nfc_app;
-- Tables: products, admins, customers, customer_details, messages
-- Sample data included
```

## Setup Instructions (XAMPP on Windows)

### 1. Database Setup
1. Start XAMPP Apache and MySQL
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Import `database.sql` or run the SQL manually
4. Default admin: `admin@example.com` / `admin123`

### 2. File Placement
Copy all files to your XAMPP htdocs folder:
```
C:\xampp\htdocs\officalonetap\
├── index.php
├── admin.php
├── account.php
├── config.php
├── qr_generator.php
├── database.sql
├── styles.css
├── templates/
│   └── error.php
└── qr_codes/ (auto-created)
```

### 3. Configuration
Edit `config.php` if needed:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'nfc_app');
define('DB_USER', 'root');
define('DB_PASS', '');  // XAMPP default
define('APP_URL', 'http://localhost/officalonetap');
define('ADMIN_EMAIL', 'your-email@example.com');
```

### 4. PHP Mail Setup (for contact form)
For XAMPP, configure `php.ini`:
```
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = your-email@gmail.com
auth_username = your-email@gmail.com
auth_password = your-app-password
```
Or use a local mail server like MailHog for testing.

### 5. Access URLs
- Products: `http://localhost/officalonetap/index.php`
- Admin: `http://localhost/officalonetap/admin.php`
- Customer: `http://localhost/officalonetap/account.php?code=ACTIVATION_CODE`

## QR Code Generation

Uses Google Chart API (no local library needed):
```
https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=URL&choe=UTF-8
```

QR codes are generated dynamically - no file storage needed unless you implement the download feature.

## Security Features

- PDO with prepared statements (SQL injection prevention)
- `password_hash()` / `password_verify()` for all passwords
- `htmlspecialchars()` on all output (XSS prevention)
- Session-based authentication
- Activation codes: 32-char hex (16 bytes random)
- No shared navigation - each page accessed via direct URL only

## Customization

### Adding Products
Insert via phpMyAdmin or admin panel (extend admin.php to add product management).

### Styling
Modify `styles.css` - uses CSS custom properties for easy theming:
```css
:root {
    --primary: #2563eb;
    --success: #16a34a;
    --danger: #dc2626;
    /* ... */
}
```

### Social Fields
Edit `$socialFields` array in `account.php` to add/remove platforms.

## Production Deployment

1. Change `DB_PASS` in config.php
2. Use HTTPS (required for session security)
3. Set secure session cookies:
   ```php
   ini_set('session.cookie_secure', 1);
   ini_set('session.cookie_httponly', 1);
   ini_set('session.cookie_samesite', 'Strict');
   ```
4. Use proper SMTP for emails
5. Consider rate limiting on login/activation endpoints
6. Remove demo admin account

## File Structure

```
officalonetap/
├── index.php          # Public products
├── admin.php          # Admin panel
├── account.php        # Customer account
├── config.php         # DB config, helpers
├── qr_generator.php   # QR code URL generator
├── database.sql       # Schema + sample data
├── styles.css         # Shared styles
├── templates/
│   └── error.php      # Error page template
└── qr_codes/          # Auto-created for QR downloads
```

## Requirements

- PHP 7.4+ (8.0+ recommended)
- MySQL 5.7+ / MariaDB 10.3+
- PDO MySQL extension
- PHP sessions enabled
- `mail()` function or SMTP configured

## License

MIT - Feel free to use and modify.