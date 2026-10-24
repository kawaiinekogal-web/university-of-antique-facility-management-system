# Running UA-FMS with Laragon

## Quick Start

### 1. Start Laragon Services
Open Laragon and click **Start All** to start Apache, MySQL, and PHP.

### 2. Install Dependencies (First Time Only)
```bash
cd C:\laragon\www\university-of-antique-facility-management-system
composer install
npm install
```

### 3. Database Setup (First Time Only)
```bash
# Create database
php artisan db:create facility_management

# Or manually in Laragon's phpMyAdmin (http://localhost/phpmyadmin)
# Create database: facility_management
# User: root (no password by default)
```

### 4. Configure Environment
Copy `.env.example` to `.env` if not already done:
```bash
cp .env.example .env
```

Update these lines in `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=facility_management
DB_USERNAME=root
DB_PASSWORD=
```

Generate application key:
```bash
php artisan key:generate
```

### 5. Run Migrations & Seeders
```bash
# Run all migrations including the new signatory fix
php artisan migrate

# Seed the database with initial data
php artisan db:seed
```

### 6. Build Assets
```bash
npm run build
```

### 7. Start Development Server
```bash
php artisan serve
```

Or use the full dev environment:
```bash
composer dev
```

### 8. Access the Application
Open your browser and go to:
- **Local**: http://localhost:8000
- **Laragon URL**: http://university-of-antique-facility-management-system.test (if configured)

## Default Test Accounts

After seeding, you can log in with:

**Admin:**
- Email: `adminA@example.com`
- Password: `password123`

**College Staff:**
- CCIS: `college@ccis.com` / `password123`
- CEA: `college@cea.com` / `password123`
- CMG: `college@cmg` / `password123`

**Org Staff:**
- Email: `orgA@example.com`
- Password: `password123`

## Common Commands

```bash
# View logs
php artisan pail

# Run tests
php artisan test

# Clear caches
php artisan optimize:clear

# Run specific migration
php artisan migrate --path=database/migrations/2026_10_24_000001_fix_signatory_dean_names.php
```

## Troubleshooting

**Port 8000 already in use:**
```bash
php artisan serve --port=8001
```

**Database connection failed:**
- Ensure MySQL is running in Laragon
- Check credentials in `.env` match Laragon's MySQL setup
- Default Laragon MySQL: root user, no password

**Composer/NPM errors:**
- Clear cache: `composer clear-cache`
- Delete `vendor` and run `composer install` again
- Delete `node_modules` and run `npm install` again
