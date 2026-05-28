# Hostel Allocation Portal — Production README

## Overview
A secure, production-grade PHP hostel room allocation system for universities.  
Students register, login, and receive an automatic room assignment.  
Admins manage students, view allocations, and reset passwords via a dashboard.

---

## Project Structure

```
hostel/
├── config.php              # Central config: DB, session, rate limiting, CSRF, flash
├── db.php                  # PDO singleton + query helpers (dbQuery, dbFetchOne, etc.)
├── index.php               # Public landing page
├── register.php            # Student registration with full validation
├── login.php               # Login with brute-force rate limiting
├── logout.php              # Secure session destruction
├── allocation.php          # Student room allocation view + request form
├── allocate.php            # Allocation logic (DB transaction, no race conditions)
├── setup.php               # ONE-TIME admin account creator — DELETE after use
├── error.php               # Custom 403 / 404 / 500 error page
├── rooms.sql               # Production-ready database schema
├── .htaccess               # Security headers, access control, caching
│
├── includes/
│   ├── header.php          # Shared HTML head + navbar
│   └── footer.php          # Shared footer + JS
│
├── assets/
│   ├── css/style.css       # Full responsive stylesheet
│   └── js/main.js          # UX: double-submit guard, matric uppercase, password strength
│
└── admin/
    ├── auth_guard.php      # Include in every admin page (session check)
    ├── login.php           # Admin login with rate limiting (5 attempts / 30 min)
    ├── logout.php          # Admin session destruction
    ├── dashboard.php       # Stats, occupancy bar, department breakdown, recent students
    ├── students.php        # Full student list with search + pagination
    ├── student-detail.php  # Per-student view + remove allocation
    ├── allocations.php     # All allocations, filterable by block
    ├── deallocate.php      # Remove a room allocation (CSRF-protected)
    ├── delete-student.php  # Delete student + allocation (CSRF-protected)
    └── reset-password.php  # Reset any student's password
```

---

## Quick Start

### 1. Database Setup

```sql
CREATE DATABASE rooms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create a dedicated user (NEVER use root in production)
CREATE USER 'rooms_user'@'localhost' IDENTIFIED BY 'YourStrongPasswordHere';
GRANT SELECT, INSERT, UPDATE, DELETE ON rooms.* TO 'rooms_user'@'localhost';
FLUSH PRIVILEGES;
```

Then import the schema:
```bash
mysql -u rooms_user -p rooms < rooms.sql
```

### 2. Configure Environment

Edit `config.php` or set environment variables:

```bash
# Recommended: set real env vars in your Apache/Nginx vhost or .env
export DB_HOST=localhost
export DB_NAME=rooms
export DB_USER=rooms_user
export DB_PASS=YourStrongPasswordHere
export APP_ENV=production
export SITE_URL=https://yourdomain.com
export ADMIN_EMAIL=admin@yourdomain.com
```

### 3. Create Admin Account

Visit `https://yourdomain.com/setup.php` in your browser.  
Fill in a username and a **strong password (min 10 chars)**.  
**Delete `setup.php` immediately after — it is a security risk if left.**

### 4. Deploy

Upload all files to your web root or a subdirectory.  
Ensure `mod_rewrite` and `mod_headers` are enabled on Apache.

---

## Security Improvements Over Original (2019)

| Issue | Old Code | New Code |
|-------|----------|----------|
| Auth algorithm | MD5 (broken) | bcrypt cost 12 |
| SQL | Raw `mysql_*` | PDO prepared statements |
| CSRF protection | None | Token-based, regenerated on success |
| Brute force | None | Rate limiting (file-based, swap to Redis) |
| Session security | Minimal | Regenerate on login, idle timeout, strict flags |
| Admin password | Plaintext in readme.txt | Hashed via setup.php |
| `verify.php` | Iterated all users in PHP | Direct indexed DB lookup |
| Error display | Raw PHP errors | Environment-aware, logged not displayed |
| Directory listing | Exposed | Disabled via .htaccess |
| Security headers | None | Full set (CSP, HSTS, X-Frame, etc.) |
| Race condition in allocation | Yes (random retry loop) | DB transaction with `FOR UPDATE` lock |
| Input validation | Partial | Comprehensive with error list |
| Admin panel | None | Full CRUD dashboard |
| Double-submit | Possible | JS guard on all forms |

---

## Production Checklist

- [ ] Set `APP_ENV=production` in config / environment
- [ ] Use a dedicated DB user (not root)
- [ ] Run setup.php once then **delete it**
- [ ] Enable HTTPS and uncomment HSTS in .htaccess
- [ ] Set a real `SITE_URL` and `ADMIN_EMAIL`
- [ ] Configure PHP `error_log` path on the server
- [ ] Enable `session.cookie_secure` (automatic when `APP_ENV=production`)
- [ ] Consider replacing file-based rate limiting with Redis/APCu for scale
- [ ] Schedule regular DB backups
- [ ] Set correct file permissions: `644` for files, `755` for directories

---

## Hostel Configuration

All hostel rules live in `config.php`:

```php
define('ROOM_CAPACITY',      4);    // Students per room
define('TOTAL_BLOCKS',       18);   // Number of blocks
define('TOTAL_ROOMS',        24);   // Rooms per block
define('SPECIAL_ROOMS',      [1, 12, 13, 24]);  // 400-level only
define('STANDARD_ROOMS_MIN', 2);
define('STANDARD_ROOMS_MAX', 23);
```

---

## Notes

- `verify.php` and `connect.php` (the old 2019 files) are **superseded** and should be deleted.
- The old `readme.txt` with plaintext credentials should be deleted.
- Rate limiting uses temp files by default. For high-traffic deployments, swap `rateLimitCheck()` in `config.php` for a Redis/APCu implementation.
