# Universal CMS Admin

**Reusable PHP + MySQL CMS / Admin Dashboard Starter Platform**

Universal CMS Admin is a standalone Dashboard/CMS project designed as a ready foundation for any new website, instead of rebuilding an admin panel for every project. The core idea is separating the **CMS Core** from the **Business Modules** and the **Frontend**, so you install the system once, enable the modules you need, and connect any frontend through API v1.

> Current version: **v0.4.0 RC — U4 Release Engineering & Disposable Runtime Verification**

---

## Table of Contents

- [1. Idea and Goal](#1-idea-and-goal)
- [2. What the System Provides](#2-what-the-system-provides)
- [3. Architecture](#3-architecture)
- [4. Requirements](#4-requirements)
- [5. Quick Install](#5-quick-install)
- [6. Install on Shared Hosting / cPanel / Hostinger](#6-install-on-shared-hosting--cpanel--hostinger)
- [7. Install on a VPS](#7-install-on-a-vps)
- [8. Configuration](#8-configuration)
- [9. Dashboard and Theme](#9-dashboard-and-theme)
- [10. Modules](#10-modules)
- [11. Module SDK / Generator](#11-module-sdk--generator)
- [12. Versioned Migrations](#12-versioned-migrations)
- [13. API v1 and Frontend Integration](#13-api-v1-and-frontend-integration)
- [14. CORS](#14-cors)
- [15. Security](#15-security)
- [16. File Uploads](#16-file-uploads)
- [17. Backup / Restore](#17-backup--restore)
- [18. Analytics and Privacy](#18-analytics-and-privacy)
- [19. Production Deployment](#19-production-deployment)
- [20. Updating an Existing Install](#20-updating-an-existing-install)
- [21. Git / GitHub Workflow](#21-git--github-workflow)
- [22. Testing and Verification](#22-testing-and-verification)
- [23. Troubleshooting](#23-troubleshooting)
- [24. Current Limitations](#24-current-limitations)
- [25. Documentation Files](#25-documentation-files)
- [26. U4: Docker / CI / Runtime Verification](#26-u4-docker--ci--runtime-verification)

---

# 1. Idea and Goal

In traditional projects a new dashboard gets built for every website, which repeats the same work:

- Login / Sessions.
- Site Settings.
- SEO.
- Media.
- Contact Messages.
- Analytics.
- Backup.
- Audit Log.
- FAQ / Partners / Gallery.
- Product Catalog.

Universal CMS turns these pieces into a **reusable platform**.

A new project becomes:

```text
New Website
    ↓
Clone Universal CMS
    ↓
Configure DB + Site Identity
    ↓
Run Installer
    ↓
Enable Required Modules
    ↓
Create Custom Modules if needed
    ↓
Connect Frontend to API v1
    ↓
Test
    ↓
Deploy
```

### The goal

A stable and secure starter, while every project stays free in:

- Frontend design.
- Site type.
- Required modules.
- Project-specific business logic.

### What the system is not

Universal CMS is not a replacement framework for Laravel or Symfony, not a full e-commerce store, and not a WordPress clone. It is a **lightweight CMS/Admin starter** for sites that need structured content and data management with a simple API.

---

# 2. What the System Provides

## Core

- Installer for a new site.
- Admin authentication.
- Session regeneration on login.
- CSRF protection for all admin operations.
- Login throttling.
- Site identity + SEO.
- Light / Dark / System theme.
- Responsive admin shell.
- Dynamic module registry.
- Module enable / disable without deleting data.
- Module manifests.
- Versioned SQL migrations.
- Migration checksum drift detection.
- Module SDK / Generator.
- API v1 discovery.
- CORS allowlist.
- Audit foundation.
- Backup / Restore foundation.

## Built-in Modules

| Module | Default | Admin | Public API |
|---|---:|---:|---:|
| Content | ON | ✅ | ✅ |
| Media | ON | ✅ | ✅ |
| Contact | ON | ✅ | ✅ |
| Analytics | ON | ✅ | ✅ |
| Audit | ON | ✅ | — |
| Backups | ON | ✅ | — |
| FAQ | OFF | ✅ | ✅ |
| Partners | OFF | ✅ | ✅ |
| Gallery | OFF | ✅ | ✅ |
| Reels | OFF | ✅ | ✅ |
| Catalog | OFF | ✅ | ✅ |

## Catalog Module

Includes:

- Brands.
- Categories.
- Products.
- Slugs.
- Availability.
- Featured products.
- New flag.
- Soft delete / Trash.
- Restore.
- Permanent delete.
- CSV Import / Export.

---

# 3. Architecture

```text
Frontend
  HTML / PHP / React / Next.js / Vue / Mobile
                │
                │ HTTP JSON
                ▼
            /api/v1/
                │
        ┌───────┴────────┐
        │   CMS Core     │
        │ Config / DB    │
        │ Auth / CSRF    │
        │ Modules        │
        │ Migrations     │
        └───────┬────────┘
                │
       ┌────────┴─────────┐
       │                  │
   Admin UI           Modules
       │                  │
       └────────┬─────────┘
                ▼
             MySQL
                +
             uploads/
```

## Separation of Responsibilities

### CMS Core

Contains no project-specific business logic. Responsible for:

- Config.
- DB connection.
- Sessions/Auth.
- CSRF.
- Admin shell.
- Module discovery.
- Migration engine.
- Shared API behavior.
- Backup infrastructure.

### Modules

Each module defines itself through:

```text
modules/<module-key>/
├── module.php
├── admin.php
├── api.php              optional
├── migrations/
│   ├── 001_initial.sql
│   └── 002_....sql
└── README.md            optional
```

### Frontend

The CMS does not depend on any framework. The frontend talks to JSON APIs only.

---

# 4. Requirements

## Server

- PHP **8.1+**.
- MySQL 8+ or a recent compatible MariaDB.
- Apache or Nginx.

## PHP Extensions

Required:

```text
PDO
pdo_mysql
mbstring
fileinfo
zlib
```

Also useful depending on the host:

```text
openssl
json
session
```

Check from the CLI:

```bash
php -v
php -m
```

Or create a temporary file containing:

```php
<?php phpinfo();
```

Then delete it immediately when done.

## Write Permissions

PHP needs write access to:

```text
storage/
uploads/
```

Do not use `777` except as a very temporary diagnostic. Ownership and permissions should match the web server user.

---

# 5. Quick Install

## 1. Create a Database

Example:

```sql
CREATE DATABASE my_project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Create a dedicated user for the project instead of using root.

## 2. Upload the Project

Point the Document Root at the project folder.

## 3. Verify Write Access

```bash
mkdir -p storage/backups uploads
```

On Linux, set owner/group according to your server setup.

## 4. Open the Installer

```text
https://example.com/install/
```

Enter:

- Site Name.
- Base URL.
- Allowed Origins.
- DB Host.
- DB Port.
- DB Name.
- DB User.
- DB Password.
- Admin Name.
- Admin Email.
- Admin Password.
- Required modules.

## 5. After Success

The installer creates:

```text
storage/config.local.php
storage/install.lock
```

Then redirects you to:

```text
/admin/login.php
```

## 6. Verify

```text
/api/v1/health.php
/api/v1/
```

Then open the dashboard and test Light / Dark / System.

---

# 6. Install on Shared Hosting / cPanel / Hostinger

1. Create a MySQL database from the hosting panel.
2. Create a database user and attach it to the database.
3. Upload the project files to `public_html` or the domain's Document Root.
4. Select PHP 8.1 or newer.
5. Enable the required extensions if the hosting panel allows it.
6. Make sure `storage/` and `uploads/` are writable.
7. Open `/install/`.
8. Complete the installation.
9. After success, confirm that `/storage/`, `/includes/`, `/database/`, `/modules/`, and `/tools/` cannot be browsed from the internet.
10. Test Login + API + Upload + Backup.

### Apache Note

The project ships `.htaccess` files that protect sensitive folders. The host must allow `.htaccess` / `AllowOverride`.

---

# 7. Install on a VPS

## Apache

General example:

```apache
<VirtualHost *:80>
    ServerName example.com
    DocumentRoot /var/www/universal-cms

    <Directory /var/www/universal-cms>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

After setting up HTTPS, use the real domain in `CMS_BASE_URL` and Allowed Origins.

## Nginx

Nginx **does not read `.htaccess`**, so protection must be applied in the server config.

Basic example:

```nginx
server {
    server_name example.com;
    root /var/www/universal-cms;
    index index.php index.html;

    location ~ ^/(storage|includes|database|modules|tools)(/|$) {
        deny all;
        return 404;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location / {
        try_files $uri $uri/ =404;
    }
}
```

Adjust the PHP-FPM path to your server version.

### uploads on Nginx

PHP execution inside `uploads` must be blocked:

```nginx
location ~* ^/uploads/.*\.php$ {
    deny all;
    return 404;
}
```

---

# 8. Configuration

The system reads settings in this order:

1. Environment Variable.
2. `storage/config.local.php`.
3. Default value from code.

Example file:

```text
storage/config.local.example.php
```

Key values:

```php
return [
    'CMS_APP_NAME' => 'My Website',
    'CMS_BASE_URL' => 'https://example.com',
    'CMS_DEFAULT_LOCALE' => 'ar',
    'CMS_LANGUAGES' => 'ar,en',
    'CMS_THEME_DEFAULT' => 'system',
    'CMS_APP_KEY' => 'RANDOM_SECRET',
    'CMS_ALLOWED_ORIGINS' => 'https://example.com',
    'CMS_DB_HOST' => '127.0.0.1',
    'CMS_DB_PORT' => 3306,
    'CMS_DB_NAME' => 'database_name',
    'CMS_DB_USER' => 'database_user',
    'CMS_DB_PASS' => 'secret',
];
```

### Important

Never push `storage/config.local.php` to GitHub. The file is in `.gitignore`.

---

# 9. Dashboard and Theme

The Admin UI uses one shared design system in:

```text
/admin/assets/admin-theme.css
```

It supports:

```text
Light
Dark
System
```

The admin's choice is saved in Local Storage under the key:

```text
universal_cms_admin_theme
```

Every admin page uses the same:

- Sidebar.
- Topbar.
- Cards.
- Forms.
- Tables.
- Status pills.
- Mobile menu.
- Responsive breakpoints.

The theme is built on CSS variables, so a new module that uses the shared classes joins Light/Dark automatically.

---

# 10. Modules

Management page:

```text
/admin/modules.php
```

### Enable

When a module is enabled:

1. Dependencies are resolved.
2. Pending migrations run.
3. The module is registered as enabled.
4. It appears in the sidebar.
5. Its API appears if it has one.

### Disable

Disabling:

- Hides the admin route from the sidebar.
- Makes the public API return 404 / MODULE_DISABLED.
- **Does not delete tables or data.**

### Manifest

Each module has a `module.php`:

```php
return [
    'key' => 'services',
    'label' => 'الخدمات',
    'label_en' => 'Services',
    'description' => '...',
    'version' => '1.0.0',
    'order' => 500,
    'default' => false,
    'admin' => '/admin/services.php',
    'api' => '/api/v1/services.php',
    'tables' => ['cms_services_items'],
    'depends' => [],
];
```

The core discovers modules automatically; there is no hardcoded list to edit when adding a module.

---

# 11. Module SDK / Generator

U3 added a generator that creates a new module without touching the core.

## Example: Services

```bash
php tools/generate-module.php services "الخدمات" "Services"
```

It automatically creates:

```text
modules/services/
├── module.php
├── admin.php
├── api.php
├── README.md
└── migrations/
    └── 001_initial.sql

admin/services.php
api/v1/services.php
```

The module appears automatically on the Modules page because the registry works by manifest discovery.

## Dry Run

```bash
php tools/generate-module.php services "الخدمات" "Services" --dry-run
```

Shows the files that would be created without writing anything.

## After Generation

1. Review `module.php`.
2. Review the first migration.
3. Adjust fields if needed **before applying the migration for the first time**.
4. Run the migrations.
5. Enable the module from the dashboard.
6. Adjust the Admin/API to the project's business logic.

## Examples

```bash
php tools/generate-module.php projects "المشاريع" "Projects"
php tools/generate-module.php team "فريق العمل" "Team"
php tools/generate-module.php properties "العقارات" "Properties"
php tools/generate-module.php services "الخدمات" "Services"
```

### Important Rule

After a migration is applied to any database, do not edit the old file. Create a new one:

```text
002_add_price.sql
003_add_location.sql
004_add_featured_flag.sql
```

See [docs/MODULE_SDK.md](docs/MODULE_SDK.md).

---

# 12. Versioned Migrations

U3 does not rely on re-running `schema.sql` on every update.

Each module uses:

```text
modules/<key>/migrations/*.sql
```

Applied migrations are recorded in:

```text
cms_schema_migrations
```

For each migration the system stores:

- Scope.
- Filename.
- SHA-256 checksum.
- Applied timestamp.

## Running Updates

Enabled modules only:

```bash
php tools/migrate.php
```

All modules, even disabled ones:

```bash
php tools/migrate.php --all
```

Status only:

```bash
php tools/migrate.php --status
```

Migrations can also run from:

```text
Admin → Modules → Run updates
```

## Migration Drift

If you apply:

```text
002_add_price.sql
```

then edit the same file later, the checksum changes and the system stops the migration instead of applying untrusted history.

The correct fix:

```text
Do not edit 002
Create 003_fix_price.sql
```

This is the idea of **append-only migrations**.

### DDL Warning

MySQL may implicitly commit some DDL statements, so do not rely on a transaction alone as a rollback mechanism for schema changes. Take a backup before large or destructive migrations.

---

# 13. API v1 and Frontend Integration

## API Discovery

```http
GET /api/v1/
```

Shows routes for enabled modules only.

## Site

```http
GET /api/v1/site.php
```

## Health

```http
GET /api/v1/health.php
```

## Built-in Endpoints

```text
/api/v1/content.php
/api/v1/media.php
/api/v1/contact.php
/api/v1/analytics.php
/api/v1/faq.php
/api/v1/partners.php
/api/v1/gallery.php
/api/v1/reels.php
/api/v1/catalog.php
```

## JavaScript Example

```js
const response = await fetch('/api/v1/site.php');
const result = await response.json();

if (!result.ok) {
  throw new Error('CMS request failed');
}

console.log(result.data);
```

## Frontend on a Different Domain

Example:

```text
CMS: https://cms.example.com
Frontend: https://www.example.com
```

Add the frontend origin to `CMS_ALLOWED_ORIGINS`.

## Catalog Example

```http
GET /api/v1/catalog.php?type=products
GET /api/v1/catalog.php?type=products&slug=my-product
GET /api/v1/catalog.php?type=products&featured=1
GET /api/v1/catalog.php?type=products&brand=brand-slug&category=category-slug
```

See [INTEGRATION_GUIDE.md](INTEGRATION_GUIDE.md).

---

# 14. CORS

The API does not use a wildcard by default.

Example:

```php
'CMS_ALLOWED_ORIGINS' => 'https://www.example.com,https://app.example.com',
```

The origin must exactly match an allowed value.

If the frontend and the CMS are on the same origin, you usually do not need CORS at all.

---

# 15. Security

## Authentication

- `password_hash()` / `password_verify()`.
- Session ID regeneration after login.
- Admin activation flag.

## Login Throttling

After multiple failed attempts inside the time window, attempts are throttled.

The fingerprint uses HMAC and combines the email plus the connection address for throttling, without storing the raw IP in the attempts table.

## CSRF

Every admin operation that changes data must use:

```php
cmsCsrfField()
cmsVerifyCsrf()
```

## SQL

Dynamic values use prepared statements. Dynamic table names in Backup are governed by an allowlist derived from trusted manifests.

## Secrets

Do not put:

- DB password.
- API keys.
- Production secrets.

inside the Git repository.

## Protected Paths

Apache: `.htaccess` files protect internal paths.

Nginx: `deny` rules must be configured manually because Nginx does not read `.htaccess`.

## Installer

After a successful install the system creates:

```text
storage/install.lock
```

The installer will not run again unless an admin deliberately removes the file.

See [docs/SECURITY.md](docs/SECURITY.md).

---

# 16. File Uploads

Upload helper:

```text
includes/uploads.php
```

By default it allows specific types such as:

```text
jpg / jpeg / png / webp / gif
pdf
mp4 / webm / mov
```

It checks:

- Upload error.
- Maximum size.
- Extension allowlist.
- MIME using `finfo` when available.
- Safe generated filename.
- Subdirectory path.

SVG is not allowed by default to reduce XSS risk.

PHP execution inside uploads must be blocked at the web server level.

---

# 17. Backup / Restore

Backups use:

```text
.cmsbak
```

They contain:

- Core CMS tables.
- Tables declared by module manifests.
- Upload files within safety limits.
- SHA-256 integrity information.

So a module created by the generator that declares a table in `tables` inside its manifest automatically joins the backup allowlist.

## Restore

Before a restore, the system creates:

```text
pre-restore backup
```

Restore only handles tables known to the current system.

### Operational Rule

Test restore the first time on a clone/staging before using it on an important production system.

---

# 18. Analytics and Privacy

The built-in analytics work as aggregate counters, not a full visitor tracking platform.

By default they do not store:

- IP.
- User-Agent.
- Name.
- Email.
- Phone.

Example events:

```text
page_view
section_view
item_view
conversion
whatsapp_click
```

This reduces the amount of stored personal data, but the project owner remains responsible for the privacy policy and legal requirements of their country and actual usage.

---

# 19. Production Deployment

Before production:

1. Use HTTPS.
2. Use a dedicated DB user.
3. Do not push `storage/config.local.php` to Git.
4. Make sure internal directories are protected.
5. Block PHP inside uploads.
6. Run migrations.
7. Review migration status.
8. Test login.
9. Test the admin theme on desktop/mobile.
10. Test the APIs actually in use.
11. Test upload.
12. Create a backup.
13. Test restore on a clone.
14. Review the PHP error log without displaying errors to visitors.

## Migration Gate

```bash
php tools/migrate.php --status
```

There must be no:

```text
drift > 0
```

And preferably no pending migrations before releasing a version.

See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

---

# 20. Updating an Existing Install

## From v0.2 to v0.3

1. Back up files and the DB.
2. Upload the v0.3 files while keeping `storage/config.local.php` and uploads.
3. Run:

```bash
php tools/migrate.php --all
```

Or run the updates from Admin → Modules.

4. Open:

```bash
php tools/migrate.php --status
```

5. Test Admin + APIs.

Initial module migrations use `CREATE TABLE IF NOT EXISTS`, so the existing v0.2 schema state can be recorded without dropping data.

Do not use `DROP` or destructive changes in production without a backup and a restore plan.

---

# 21. Git / GitHub Workflow

The project includes a `.gitignore` that protects sensitive runtime files.

## First Repository

```bash
git init
git add .
git commit -m "Initial Universal CMS"
git branch -M main
git remote add origin <YOUR_REPOSITORY>
git push -u origin main
```

## New Client Project

You can:

### Option A — GitHub Template Repository

Make the base repository a **template repository**, then use **Use this template** for each project.

### Option B — Clone

```bash
git clone <repo> client-project
cd client-project
```

Then install the project from `/install/`.

## Do Not Push to GitHub

```text
storage/config.local.php
storage/install.lock
storage/backups/*
uploads/* runtime content
```

## Do Push to GitHub

```text
source code
module manifests
migrations
README/docs
.htaccess
.gitignore
config.local.example.php
```

---

# 22. Testing and Verification

## Static Platform Verification

```bash
php tools/verify.php
```

Verifies manifests, modules, wrappers, migration files, and the absence of runtime secrets from the distributed copy.

## PHP Syntax

Linux/macOS:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Module Generator Dry Run

```bash
php tools/generate-module.php services "الخدمات" "Services" --dry-run
```

## Migration Status

```bash
php tools/migrate.php --status
```

## Runtime Smoke

Check:

```text
/install/           fresh project only
/admin/login.php
/admin/
/admin/modules.php
/api/v1/health.php
/api/v1/
/api/v1/site.php
```

Then test every enabled module.

---

# 23. Troubleshooting

## `could not find driver`

Usually `pdo_mysql` is not installed/enabled.

Check:

```bash
php -m | grep pdo_mysql
```

## Installer Cannot Write Config

Review permissions on:

```text
storage/
```

## Upload Fails

Review:

- `upload_max_filesize`.
- `post_max_size`.
- Folder permissions.
- MIME/extension allowlist.

## API Returns `MODULE_DISABLED`

Enable the module from:

```text
Admin → Modules
```

## Migration Checksum Mismatch

A previously applied migration was edited. Restore the original file and create a new migration instead of editing it.

## Dark Mode Does Not Appear

- Hard refresh.
- Review `admin/assets/admin-theme.css`.
- Clear the `universal_cms_admin_theme` value from Local Storage when testing.
- Make sure the page uses `cmsAdminHead()` and `cmsAdminEnd()`.

## Nginx Serves an Internal Directory

`.htaccess` does not work on Nginx. Add deny rules to the Nginx config.

See [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md).

---

# 24. Current Limitations

- The system currently has a simple single-role admin, not multi-role RBAC.
- No package marketplace or remote module installer.
- The Module Generator produces a generic bilingual CRUD starting point, not complete business logic for every domain.
- The SQL migration parser targets plain SQL files; it does not support `DELIMITER` or complex stored procedure scripts.
- The built-in backup suits small/medium projects; very large databases or uploads need an infrastructure-level backup strategy.
- Runtime MySQL verification must run on an environment with `pdo_mysql` and a real database.

---

# 25. Documentation Files

```text
README.md                       Start here, general reference
INTEGRATION_GUIDE.md            Frontend / API integration
MIGRATION_FROM_V0_1.md          Old upgrade history
U1_FOUNDATION_REPORT.md         U1 report
U2_MODULE_EXTRACTION_REPORT.md  U2 report
U2_VERIFICATION.md              U2 verification
docs/ARCHITECTURE.md            Architecture and request flow
docs/MODULE_SDK.md              Building new modules
docs/MIGRATIONS.md              Migrations system
docs/DEPLOYMENT.md              Production deployment
docs/SECURITY.md                Security model
docs/API.md                     API reference
docs/TROUBLESHOOTING.md         Common problems
U3_PLATFORM_REPORT.md           What was done in U3
U3_VERIFICATION.md              U3 verification results
MIGRATION_FROM_V0_2.md          Upgrade from v0.2 to v0.3
CHANGELOG.md                    Release history
MIGRATION_FROM_V0_3.md          Upgrade from v0.3 to v0.4
RELEASE_CHECKLIST.md            Closing gate before Release/Production
SECURITY.md                     Vulnerability reporting policy
CONTRIBUTING.md                 Contribution rules
docs/DOCKER.md                  Docker runtime
docs/RUNTIME_TESTING.md         Runtime/Clone test suite
docs/RELEASES.md                Release engineering
```

---

## Version

```text
Universal CMS Admin v0.4.0 RC
```

### U4 Summary

```text
Dynamic Module Manifests        ✅
Auto Module Discovery           ✅
Module SDK / Generator          ✅
Versioned Migrations            ✅
Migration Checksums + Locking   ✅
Migration Status                ✅
Dependency Foundation           ✅
Dynamic Module Backup Tables    ✅
Backup Compatibility Preflight  ✅
Docker Dev Runtime              ✅
GitHub Actions CI               ✅
Disposable DB Test Suite        ✅
Doctor / Release Check Tools    ✅
Git-safe Runtime Ignore Rules   ✅
Comprehensive GitHub README     ✅
```

---

**Deployment rule:** reusable Core, extensible Modules, independent Frontend, secrets outside Git, and append-only schema changes through migrations.


# 26. U4: Docker / CI / Runtime Verification

U4 moves the project from a **reusable starter** stage to a **release candidate verifiable automatically**. The goal is not adding new business features, but proving that install, upgrade, migrations, the Module Generator, and Backup/Restore can be tested on a disposable environment before touching production.

## Quick Local Run with Docker

```bash
cp .env.docker.example .env
# Change the passwords inside .env
docker compose up -d --build
```

Then open:

```text
http://localhost:8080/install/
```

More detail: [docs/DOCKER.md](docs/DOCKER.md).

## Environment Check

Static only:

```bash
php tools/doctor.php --static
```

On a runtime connected to a database:

```bash
php tools/doctor.php
```

The check verifies PHP/extensions, write access to storage/uploads, the DB, migration drift/pending state, and module manifests.

## Release Check

```bash
php tools/release-check.php
```

It combines verifier + static doctor + PHP syntax.

## Runtime Integration Suite

**Destructive tests are forbidden on production.** The tests themselves refuse to run unless the database name contains `test` or `ci`.

```bash
bash tests/integration/http-installer.sh
php tests/integration/upgrade-v0_3.php
php tests/integration/runtime.php
php tests/integration/generator.php
php tools/doctor.php
```

This chain proves:

- Installer from an empty database through login.
- API health/discovery.
- A real upgrade from v0.3 schema to v0.4.
- Migration checksum drift detection.
- MariaDB data writes/reads.
- Backup + Restore of the DB and uploads on a clone.
- Pre-restore backup.
- Disabling a module without deleting its table.
- Generating a new module on a disposable copy and discovering it without core changes.

See [docs/RUNTIME_TESTING.md](docs/RUNTIME_TESTING.md).

## Migration Locking

U4 adds a MariaDB/MySQL advisory lock while applying migrations, tied to the database name so it does not block another CMS project using the same MySQL server. The goal is to prevent two deploy/admin processes from running migrations at the same moment.

## Backup Format v3

New backups contain additional metadata:

```text
platform_version
schema signature
migration snapshot
table data
upload hashes + sizes
```

Before a restore, the system checks:

- Envelope/hash integrity.
- Upload hashes/paths.
- Presence of required tables/columns.
- No migration drift.
- No new required column that the old backup cannot fill.

U4 still reads Backup v2 from v0.3 for backward compatibility.

## GitHub Actions

The file:

```text
.github/workflows/ci.yml
```

Runs:

1. Static matrix on PHP 8.2 / 8.3 / 8.4.
2. MariaDB 11.4 service.
3. HTTP installer/login smoke.
4. v0.3 → v0.4 upgrade test.
5. DB + Backup/Restore runtime test.
6. Module Generator test.
7. Runtime Doctor.

## Before a Tag or Production Deploy

Use:

```text
RELEASE_CHECKLIST.md
```

Do not consider a release locked until CI is green and restore has been tested on a disposable clone.
