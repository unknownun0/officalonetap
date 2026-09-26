# Vercel Deployment Guide

This guide explains how to deploy the NFC Solutions application to Vercel.

## Prerequisites

1. **Vercel Account** - Sign up at [vercel.com](https://vercel.com)
2. **Git Repository** - Push your code to GitHub, GitLab, or Bitbucket
3. **Managed MySQL Database** - Vercel doesn't host databases. Use:
   - [PlanetScale](https://planetscale.com) (MySQL-compatible, generous free tier)
   - [Neon](https://neon.tech) (PostgreSQL, but MySQL-compatible via proxy)
   - [Railway](https://railway.app) (MySQL/PostgreSQL)
   - [Aiven](https://aiven.io) (Managed MySQL)

## Quick Deploy

### 1. Push to Git

```bash
cd C:\xampp\htdocs\officalonetap
git init
git add .
git commit -m "Initial commit for Vercel deployment"
git branch -M main
git remote add origin https://github.com/yourusername/your-repo.git
git push -u origin main
```

### 2. Create Database (PlanetScale Example)

1. Go to [PlanetScale](https://planetscale.com) and create a database
2. Get your connection string: `mysql://user:pass@host/db?ssl_mode=verify_identity`
3. Run the SQL from `database.sql` in your database console

### 3. Deploy to Vercel

1. Go to [Vercel Dashboard](https://vercel.com/dashboard)
2. Click "Add New..." → "Project"
3. Import your Git repository
4. Configure:
   - **Framework Preset**: PHP
   - **Build Command**: `composer install --no-dev --optimize-autoloader`
   - **Output Directory**: Leave empty
   - **Install Command**: `composer install`

### 4. Set Environment Variables

In Vercel Project Settings → Environment Variables, add:

| Name | Value | Description |
|------|-------|-------------|
| `DATABASE_URL` | `mysql://user:pass@host/db?ssl_mode=verify_identity` | Your database connection string |
| `APP_URL` | `https://your-project.vercel.app` | Your Vercel deployment URL |
| `APP_ENV` | `production` | Environment |
| `ADMIN_EMAIL` | `your-email@example.com` | Admin contact email |
| `SESSION_SECURE_COOKIE` | `1` | Secure cookies (HTTPS only) |
| `SESSION_HTTP_ONLY` | `1` | HttpOnly cookies |
| `SESSION_SAME_SITE` | `Strict` | CSRF protection |

### 5. Deploy

Click "Deploy" - Vercel will build and deploy automatically.

## Important Notes

### Database-Backed Sessions
The app uses database-backed sessions (table `sessions`) instead of file-based sessions because Vercel's filesystem is ephemeral. The `database.sql` includes the sessions table.

### QR Code Generation
QR codes are generated via external APIs (`api.qrserver.com`, `chart.googleapis.com`) since we can't write files to Vercel's read-only filesystem.

### Email Sending
The `mail()` function won't work on Vercel. For production, integrate with:
- [SendGrid](https://sendgrid.com)
- [Mailgun](https://mailgun.com)
- [Resend](https://resend.com)
- [Postmark](https://postmarkapp.com)

Update the `sendEmail()` function in `config.php` to use your preferred service.

### SSL Certificates for Database
For PlanetScale/Neon with SSL, you may need to:
1. Download the CA certificate
2. Place it at `/certs/ca.pem` in your project
3. Or set `MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false` in PDO options (less secure)

### Custom Domains
In Vercel Project Settings → Domains, add your custom domain. Update `APP_URL` environment variable accordingly.

## Local Development with Vercel CLI

```bash
# Install Vercel CLI
npm i -g vercel

# Login
vercel login

# Link project
vercel link

# Pull environment variables
vercel env pull .env.local

# Start dev server
vercel dev
```

## Troubleshooting

### "Database connection failed"
- Verify `DATABASE_URL` is correct
- Check database allows connections from Vercel IPs
- For PlanetScale: enable "Allow all IPs" or add Vercel's IP ranges

### Sessions not persisting
- Ensure `sessions` table exists in database
- Check `SESSION_LIFETIME` environment variable
- Verify session handler is registered before `session_start()`

### QR codes not showing
- Check browser console for CORS errors
- Try alternative QR API in `config.php`: `generateQrCodeUrl()`

### 404 on page refresh
- Ensure `vercel.json` rewrites are configured correctly
- Check that all PHP files are in the root directory

## Environment Variables Reference

```env
# Database (use either individual vars OR DATABASE_URL)
DATABASE_URL=mysql://user:pass@host/db?ssl_mode=verify_identity
# OR
DB_HOST=your-host
DB_NAME=your-db
DB_USER=your-user
DB_PASS=your-pass
DB_SSL=true

# Application
APP_URL=https://your-project.vercel.app
APP_ENV=production
ADMIN_EMAIL=admin@example.com

# Session Security
SESSION_SECURE_COOKIE=1
SESSION_HTTP_ONLY=1
SESSION_SAME_SITE=Strict
SESSION_LIFETIME=7200
```

## File Structure for Vercel

```
officalonetap/
├── api/                    # Vercel serverless functions (optional)
├── certs/                  # SSL certificates for database
│   └── ca.pem
├── templates/
│   └── error.php
├── .env.example            # Template for environment variables
├── .gitignore
├── composer.json
├── vercel.json             # Vercel configuration
├── database.sql            # Database schema
├── config.php              # Main configuration
├── qr_generator.php        # QR code utilities
├── index.php               # Public products page
├── admin.php               # Admin panel
├── account.php             # Customer account
└── styles.css              # Shared styles
```

## Security Checklist for Production

- [ ] Change default admin password
- [ ] Use strong `DATABASE_URL` with SSL
- [ ] Set `SESSION_SECURE_COOKIE=1`
- [ ] Set `SESSION_SAME_SITE=Strict`
- [ ] Configure proper SMTP for emails
- [ ] Enable Vercel DDoS protection
- [ ] Set up custom domain with HTTPS
- [ ] Regular database backups
- [ ] Monitor Vercel function logs

## Support

- Vercel PHP Docs: https://vercel.com/docs/functions/runtimes/php
- PlanetScale PHP: https://planetscale.com/docs/tutorials/connect-php
- Neon PHP: https://neon.tech/docs/connect/php