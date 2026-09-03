# Universal CMS Admin

**Reusable PHP + MySQL CMS / Admin Dashboard Starter Platform**

Universal CMS Admin هو مشروع Dashboard/CMS مستقل صُمم ليكون قاعدة جاهزة لأي موقع جديد بدل إعادة بناء لوحة الإدارة في كل مشروع. الفكرة الأساسية هي فصل **CMS Core** عن **Business Modules** وعن **Frontend**، بحيث يمكن تثبيت النظام مرة، تفعيل الوحدات المطلوبة، ثم ربط أي واجهة أمامية عبر API v1.

> الإصدار الحالي: **v0.4.0 RC — U4 Release Engineering & Disposable Runtime Verification**

---

## Table of Contents

- [1. الفكرة والهدف](#1-الفكرة-والهدف)
- [2. ما الذي يقدمه النظام؟](#2-ما-الذي-يقدمه-النظام)
- [3. المعمارية](#3-المعمارية)
- [4. المتطلبات](#4-المتطلبات)
- [5. التثبيت السريع](#5-التثبيت-السريع)
- [6. التثبيت على Shared Hosting / cPanel / Hostinger](#6-التثبيت-على-shared-hosting--cpanel--hostinger)
- [7. التثبيت على VPS](#7-التثبيت-على-vps)
- [8. الإعداد Configuration](#8-الإعداد-configuration)
- [9. لوحة التحكم والثيم](#9-لوحة-التحكم-والثيم)
- [10. الوحدات Modules](#10-الوحدات-modules)
- [11. Module SDK / Generator](#11-module-sdk--generator)
- [12. Versioned Migrations](#12-versioned-migrations)
- [13. API v1 وربط الواجهة](#13-api-v1-وربط-الواجهة)
- [14. CORS](#14-cors)
- [15. الأمان](#15-الأمان)
- [16. رفع الملفات](#16-رفع-الملفات)
- [17. Backup / Restore](#17-backup--restore)
- [18. Analytics والخصوصية](#18-analytics-والخصوصية)
- [19. النشر Production Deployment](#19-النشر-production-deployment)
- [20. تحديث نسخة موجودة](#20-تحديث-نسخة-موجودة)
- [21. Git / GitHub Workflow](#21-git--github-workflow)
- [22. الاختبارات والتحقق](#22-الاختبارات-والتحقق)
- [23. Troubleshooting](#23-troubleshooting)
- [24. حدود النظام الحالية](#24-حدود-النظام-الحالية)
- [25. ملفات التوثيق](#25-ملفات-التوثيق)
- [26. U4: Docker / CI / Runtime Verification](#26-u4-docker--ci--runtime-verification)

---

# 1. الفكرة والهدف

في المشاريع التقليدية غالبًا يتم بناء Dashboard جديدة لكل موقع. هذا يؤدي إلى تكرار نفس الأشياء:

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

Universal CMS يحول هذه الأجزاء إلى **Platform قابلة لإعادة الاستخدام**.

المشروع الجديد يصبح:

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

### الهدف

أن يكون لديك Starter ثابت وآمن، بينما يظل كل مشروع حرًا في:

- التصميم الأمامي.
- نوع الموقع.
- الوحدات المطلوبة.
- Business Logic الخاص بالمشروع.

### ما ليس هدفًا للنظام

Universal CMS ليس Framework بديلًا لـLaravel أو Symfony، وليس متجرًا إلكترونيًا كاملًا، وليس WordPress clone. هو **CMS/Admin Starter خفيف** مناسب للمواقع التي تحتاج إدارة محتوى وبيانات منظمة مع API بسيط.

---

# 2. ما الذي يقدمه النظام؟

## Core

- Installer لموقع جديد.
- Admin Authentication.
- Session regeneration عند تسجيل الدخول.
- CSRF protection لكل عمليات الإدارة.
- Login throttling.
- Site identity + SEO.
- Light / Dark / System Theme.
- Responsive Admin Shell.
- Module Registry ديناميكي.
- Module enable / disable بدون حذف البيانات.
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

يتضمن:

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

# 3. المعمارية

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

## فصل المسؤوليات

### CMS Core

لا يحتوي Business Logic خاص بمشروع بعينه. مسؤول عن:

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

كل Module تعرف نفسها بواسطة:

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

لا يعتمد CMS على Framework معين. الواجهة تتعامل مع JSON APIs فقط.

---

# 4. المتطلبات

## Server

- PHP **8.1+**.
- MySQL 8+ أو MariaDB حديثة متوافقة.
- Apache أو Nginx.

## PHP Extensions

مطلوب:

```text
PDO
pdo_mysql
mbstring
fileinfo
zlib
```

مفيد أيضًا حسب الاستضافة:

```text
openssl
json
session
```

تحقق من CLI:

```bash
php -v
php -m
```

أو أنشئ ملفًا مؤقتًا يحتوي:

```php
<?php phpinfo();
```

ثم احذفه فور الانتهاء.

## صلاحيات الكتابة

PHP يحتاج الكتابة على:

```text
storage/
uploads/
```

لا تستخدم `777` إلا كتشخيص مؤقت جدًا. الأفضل أن تكون الملكية والصلاحيات مناسبة لمستخدم Web Server.

---

# 5. التثبيت السريع

## 1. أنشئ Database

مثال:

```sql
CREATE DATABASE my_project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

أنشئ User مخصصًا للمشروع بدل استخدام root.

## 2. ارفع المشروع

اجعل Document Root يشير إلى مجلد المشروع الحالي.

## 3. تحقق من الكتابة

```bash
mkdir -p storage/backups uploads
```

على Linux اضبط owner/group حسب إعداد السيرفر.

## 4. افتح Installer

```text
https://example.com/install/
```

أدخل:

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
- Modules المطلوبة.

## 5. بعد النجاح

Installer ينشئ:

```text
storage/config.local.php
storage/install.lock
```

ثم يحولك إلى:

```text
/admin/login.php
```

## 6. تحقق

```text
/api/v1/health.php
/api/v1/
```

ثم افتح Dashboard واختبر Light / Dark / System.

---

# 6. التثبيت على Shared Hosting / cPanel / Hostinger

1. أنشئ MySQL Database من لوحة الاستضافة.
2. أنشئ Database User واربطه بالقاعدة.
3. ارفع ملفات المشروع إلى `public_html` أو Document Root الخاص بالدومين.
4. اختر PHP 8.1 أو أحدث.
5. فعّل extensions المطلوبة إن كانت لوحة الاستضافة تسمح بذلك.
6. تأكد أن `storage/` و`uploads/` قابلان للكتابة.
7. افتح `/install/`.
8. أكمل التثبيت.
9. بعد النجاح، تأكد أن `/storage/`, `/includes/`, `/database/`, `/modules/`, `/tools/` لا يمكن تصفحها من الإنترنت.
10. اختبر Login + API + Upload + Backup.

### ملاحظة Apache

المشروع يحتوي `.htaccess` لحماية المجلدات الحساسة. يجب أن تكون الاستضافة تسمح بـ`.htaccess` / `AllowOverride`.

---

# 7. التثبيت على VPS

## Apache

مثال عام:

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

بعد إعداد HTTPS استخدم الدومين الحقيقي في `CMS_BASE_URL` وAllowed Origins.

## Nginx

Nginx **لا يقرأ `.htaccess`**؛ لذلك يجب تطبيق الحماية في Server Config.

مثال أساسي:

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

عدّل مسار PHP-FPM حسب إصدار السيرفر.

### uploads على Nginx

يجب منع تنفيذ PHP داخل `uploads`:

```nginx
location ~* ^/uploads/.*\.php$ {
    deny all;
    return 404;
}
```

---

# 8. الإعداد Configuration

النظام يقرأ الإعدادات بالترتيب:

1. Environment Variable.
2. `storage/config.local.php`.
3. Default value من الكود.

ملف المثال:

```text
storage/config.local.example.php
```

أهم القيم:

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

### مهم

لا ترفع `storage/config.local.php` إلى GitHub. الملف موجود في `.gitignore`.

---

# 9. لوحة التحكم والثيم

الـAdmin UI يستخدم Shared Design System واحد في:

```text
/admin/assets/admin-theme.css
```

يدعم:

```text
Light
Dark
System
```

اختيار Admin يحفظ في Local Storage بالمفتاح:

```text
universal_cms_admin_theme
```

كل صفحات الإدارة تستخدم نفس:

- Sidebar.
- Topbar.
- Cards.
- Forms.
- Tables.
- Status pills.
- Mobile menu.
- Responsive breakpoints.

الثيم مبني على CSS Variables، لذلك Module جديدة تستخدم classes المشتركة تدخل في Light/Dark تلقائيًا.

---

# 10. الوحدات Modules

صفحة الإدارة:

```text
/admin/modules.php
```

### Enable

عند تفعيل Module:

1. يتم حل dependencies.
2. يتم تشغيل migrations المعلقة.
3. يتم تسجيل الوحدة Enabled.
4. تظهر في Sidebar.
5. يظهر API الخاص بها إن كان موجودًا.

### Disable

التعطيل:

- يخفي Admin route من Sidebar.
- يجعل Public API ترجع 404/MODULE_DISABLED.
- **لا يحذف الجداول ولا البيانات.**

### Manifest

كل Module لديها `module.php`:

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

الـCore يكتشف Modules تلقائيًا؛ لا توجد قائمة Hardcoded تحتاج تعديلًا عند إضافة Module جديدة.

---

# 11. Module SDK / Generator

U3 أضاف Generator لإنشاء Module جديدة بدون تعديل Core.

## مثال: Services

```bash
php tools/generate-module.php services "الخدمات" "Services"
```

ينشئ تلقائيًا:

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

ويظهر Module تلقائيًا في صفحة Modules لأن Registry يعمل بالـmanifest discovery.

## Dry Run

```bash
php tools/generate-module.php services "الخدمات" "Services" --dry-run
```

يعرض الملفات التي سيتم إنشاؤها بدون كتابة شيء.

## بعد التوليد

1. راجع `module.php`.
2. راجع أول migration.
3. عدّل fields إذا احتجت **قبل تطبيق migration لأول مرة**.
4. شغّل migrations.
5. فعّل الوحدة من Dashboard.
6. عدّل Admin/API حسب Business Logic المشروع.

## أمثلة

```bash
php tools/generate-module.php projects "المشاريع" "Projects"
php tools/generate-module.php team "فريق العمل" "Team"
php tools/generate-module.php properties "العقارات" "Properties"
php tools/generate-module.php services "الخدمات" "Services"
```

### قاعدة مهمة

بعد تطبيق migration في أي Database، لا تعدل الملف القديم. أنشئ ملفًا جديدًا:

```text
002_add_price.sql
003_add_location.sql
004_add_featured_flag.sql
```

راجع [docs/MODULE_SDK.md](docs/MODULE_SDK.md).

---

# 12. Versioned Migrations

U3 لا يعتمد على إعادة تشغيل `schema.sql` في كل تحديث.

كل Module تستخدم:

```text
modules/<key>/migrations/*.sql
```

ويتم تسجيل ما تم تطبيقه في:

```text
cms_schema_migrations
```

لكل Migration يتم حفظ:

- Scope.
- Filename.
- SHA-256 checksum.
- Applied timestamp.

## تشغيل التحديثات

Enabled modules فقط:

```bash
php tools/migrate.php
```

كل Modules الموجودة حتى لو Disabled:

```bash
php tools/migrate.php --all
```

الحالة فقط:

```bash
php tools/migrate.php --status
```

كما يمكن تشغيل migrations من:

```text
Admin → Modules → تشغيل التحديثات
```

## Migration Drift

لو طبقت:

```text
002_add_price.sql
```

ثم عدلت نفس الملف لاحقًا، الـchecksum ستختلف ويوقف النظام migration بدل تطبيق تاريخ غير موثوق.

الحل الصحيح:

```text
لا تعدل 002
أنشئ 003_fix_price.sql
```

هذه هي فكرة **append-only migrations**.

### تنبيه DDL

MySQL قد يعمل implicit commit لبعض أوامر DDL. لذلك لا تعتمد على Transaction وحدها كوسيلة Rollback لتغييرات Schema. خذ Backup قبل migrations الكبيرة أو destructive.

---

# 13. API v1 وربط الواجهة

## API Discovery

```http
GET /api/v1/
```

يعرض routes الخاصة بالـModules المفعلة فقط.

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

## Frontend على Domain مختلف

مثال:

```text
CMS: https://cms.example.com
Frontend: https://www.example.com
```

أضف Frontend Origin إلى `CMS_ALLOWED_ORIGINS`.

## Catalog Example

```http
GET /api/v1/catalog.php?type=products
GET /api/v1/catalog.php?type=products&slug=my-product
GET /api/v1/catalog.php?type=products&featured=1
GET /api/v1/catalog.php?type=products&brand=brand-slug&category=category-slug
```

راجع [INTEGRATION_GUIDE.md](INTEGRATION_GUIDE.md).

---

# 14. CORS

الـAPI لا تستخدم wildcard افتراضيًا.

مثال:

```php
'CMS_ALLOWED_ORIGINS' => 'https://www.example.com,https://app.example.com',
```

الـOrigin يجب أن يطابق قيمة مسموحة بالضبط.

إذا الـFrontend والـCMS على نفس Origin، غالبًا لا تحتاج CORS أصلًا.

---

# 15. الأمان

## Authentication

- `password_hash()` / `password_verify()`.
- Session ID regeneration بعد Login.
- Admin activation flag.

## Login Throttling

بعد محاولات فاشلة متعددة داخل النافذة الزمنية يتم تقييد المحاولات.

Fingerprint يستخدم HMAC ويجمع البريد + عنوان الاتصال لغرض throttling بدون تخزين IP خام في جدول المحاولات.

## CSRF

كل عمليات Admin التي تغيّر بيانات يجب أن تستخدم:

```php
cmsCsrfField()
cmsVerifyCsrf()
```

## SQL

القيم الديناميكية تستخدم Prepared Statements. أسماء الجداول الديناميكية في Backup محكومة Allowlist مشتقة من manifests الموثوقة.

## Secrets

لا تضع:

- DB password.
- API keys.
- Production secrets.

داخل Git repository.

## Protected Paths

Apache: ملفات `.htaccess` تحمي المسارات الداخلية.

Nginx: يجب إعداد `deny` يدويًا لأن Nginx لا يقرأ `.htaccess`.

## Installer

بعد نجاح التثبيت يتم إنشاء:

```text
storage/install.lock
```

ولا يعمل Installer مرة أخرى إلا إذا أزال المسؤول الملف عمدًا.

راجع [docs/SECURITY.md](docs/SECURITY.md).

---

# 16. رفع الملفات

Upload helper:

```text
includes/uploads.php
```

الافتراضي يسمح بأنواع محددة مثل:

```text
jpg / jpeg / png / webp / gif
pdf
mp4 / webm / mov
```

يتم فحص:

- Upload error.
- Maximum size.
- Extension allowlist.
- MIME باستخدام `finfo` عند توفرها.
- Safe generated filename.
- Subdirectory path.

SVG غير مسموح به افتراضيًا لتقليل مخاطر XSS.

تنفيذ PHP داخل uploads يجب أن يكون محظورًا على Web Server.

---

# 17. Backup / Restore

النسخ تستخدم:

```text
.cmsbak
```

وتحتوي على:

- Core CMS tables.
- Tables المعلنة بواسطة Module manifests.
- Upload files ضمن حدود الأمان.
- SHA-256 integrity information.

بالتالي Module يتم إنشاؤها بالـGenerator وتضيف table في `tables` داخل manifest تدخل تلقائيًا في Backup allowlist.

## Restore

قبل Restore يتم إنشاء:

```text
pre-restore backup
```

Restore يتعامل فقط مع الجداول المعروفة للنظام الحالي.

### قاعدة تشغيلية

اختبر Restore لأول مرة على Clone/Staging قبل استخدامه على Production مهم.

---

# 18. Analytics والخصوصية

Analytics المدمجة تعمل كـaggregate counters وليست visitor tracking platform كاملة.

لا تخزن افتراضيًا:

- IP.
- User-Agent.
- Name.
- Email.
- Phone.

أمثلة Events:

```text
page_view
section_view
item_view
conversion
whatsapp_click
```

هذا يقلل كمية البيانات الشخصية المخزنة، لكن مسؤول المشروع يظل مسؤولًا عن سياسة الخصوصية والمتطلبات القانونية الخاصة ببلده واستخدامه الفعلي.

---

# 19. النشر Production Deployment

قبل Production:

1. استخدم HTTPS.
2. استخدم DB User مخصصًا.
3. لا ترفع `storage/config.local.php` إلى Git.
4. تأكد من حماية internal directories.
5. امنع PHP داخل uploads.
6. شغّل migrations.
7. راجع migration status.
8. اختبر Login.
9. اختبر Admin Theme على desktop/mobile.
10. اختبر APIs المستخدمة فعليًا.
11. اختبر Upload.
12. أنشئ Backup.
13. اختبر Restore على Clone.
14. راجع PHP error log بدون عرض errors للزوار.

## Migration Gate

```bash
php tools/migrate.php --status
```

يجب ألا يوجد:

```text
drift > 0
```

والمفضل ألا يوجد pending migrations قبل إطلاق النسخة.

راجع [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

---

# 20. تحديث نسخة موجودة

## من v0.2 إلى v0.3

1. Backup للملفات والـDB.
2. ارفع ملفات v0.3 مع الحفاظ على `storage/config.local.php` وuploads.
3. شغّل:

```bash
php tools/migrate.php --all
```

أو من Admin → Modules شغّل التحديثات.

4. افتح:

```bash
php tools/migrate.php --status
```

5. اختبر Admin + APIs.

مigrations الأولية للوحدات تستخدم `CREATE TABLE IF NOT EXISTS`، لذلك يمكن تسجيل حالة Schema الموجودة في v0.2 بدون إسقاط البيانات.

لا تستخدم `DROP` أو تعديلات destructive في Production بدون Backup وخطة Restore.

---

# 21. Git / GitHub Workflow

المشروع يحتوي `.gitignore` لحماية ملفات Runtime الحساسة.

## أول Repository

```bash
git init
git add .
git commit -m "Initial Universal CMS"
git branch -M main
git remote add origin <YOUR_REPOSITORY>
git push -u origin main
```

## مشروع عميل جديد

يمكنك:

### Option A — GitHub Template Repository

اجعل repository الأساسي **Template repository** ثم استخدم **Use this template** لكل مشروع.

### Option B — Clone

```bash
git clone <repo> client-project
cd client-project
```

ثم ثبت المشروع من `/install/`.

## لا ترفع إلى GitHub

```text
storage/config.local.php
storage/install.lock
storage/backups/*
uploads/* runtime content
```

## ارفع إلى GitHub

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

# 22. الاختبارات والتحقق

## Static Platform Verification

```bash
php tools/verify.php
```

يتحقق من manifests والوحدات والـwrappers وملفات migrations وغياب runtime secrets من النسخة الموزعة.

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

تحقق من:

```text
/install/           fresh project only
/admin/login.php
/admin/
/admin/modules.php
/api/v1/health.php
/api/v1/
/api/v1/site.php
```

ثم اختبر كل Module مفعلة.

---

# 23. Troubleshooting

## `could not find driver`

غالبًا `pdo_mysql` غير مثبت/مفعل.

تحقق:

```bash
php -m | grep pdo_mysql
```

## Installer لا يستطيع كتابة config

راجع صلاحيات:

```text
storage/
```

## Upload يفشل

راجع:

- `upload_max_filesize`.
- `post_max_size`.
- Folder permissions.
- MIME/extension allowlist.

## API ترجع `MODULE_DISABLED`

فعّل الوحدة من:

```text
Admin → Modules
```

## Migration checksum mismatch

تم تعديل migration مطبقة سابقًا. أعد الملف الأصلي وأنشئ migration جديدة بدل تعديله.

## Dark Mode لا يظهر

- Hard refresh.
- راجع `admin/assets/admin-theme.css`.
- امسح قيمة `universal_cms_admin_theme` من Local Storage للتجربة.
- تأكد أن الصفحة تستخدم `cmsAdminHead()` و`cmsAdminEnd()`.

## Nginx يسمح بفتح internal directory

`.htaccess` لا تعمل على Nginx. أضف deny rules في Nginx config.

راجع [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md).

---

# 24. حدود النظام الحالية

- النظام حاليًا Admin single-role بسيط، وليس RBAC متعدد الأدوار.
- لا يوجد Package marketplace أو remote module installer.
- Module Generator يولد CRUD ثنائي اللغة عام كبداية، وليس Business Logic كاملًا لكل مجال.
- SQL migration parser مخصص لملفات SQL العادية؛ لا يدعم `DELIMITER` / stored procedure scripts المعقدة.
- Backup المدمج مناسب للمشاريع الصغيرة/المتوسطة؛ قواعد البيانات أو uploads الكبيرة جدًا تحتاج استراتيجية backup على مستوى البنية التحتية.
- Runtime MySQL verification يجب أن يتم على بيئة تحتوي `pdo_mysql` وقاعدة فعلية.

---

# 25. ملفات التوثيق

```text
README.md                       البداية والمرجع العام
INTEGRATION_GUIDE.md            ربط Frontend / API
MIGRATION_FROM_V0_1.md          تاريخ التحديث القديم
U1_FOUNDATION_REPORT.md         تقرير U1
U2_MODULE_EXTRACTION_REPORT.md  تقرير U2
U2_VERIFICATION.md              تحقق U2
docs/ARCHITECTURE.md            شرح المعمارية وتدفق الطلبات
docs/MODULE_SDK.md              بناء Modules جديدة
docs/MIGRATIONS.md              نظام migrations
docs/DEPLOYMENT.md              Production deployment
docs/SECURITY.md                Security model
docs/API.md                     API reference
docs/TROUBLESHOOTING.md         المشاكل الشائعة
U3_PLATFORM_REPORT.md           ما تم في U3
U3_VERIFICATION.md              نتائج تحقق U3
MIGRATION_FROM_V0_2.md          التحديث من v0.2 إلى v0.3
CHANGELOG.md                    تاريخ الإصدارات
MIGRATION_FROM_V0_3.md          التحديث من v0.3 إلى v0.4
RELEASE_CHECKLIST.md            بوابة الإغلاق قبل Release/Production
SECURITY.md                     سياسة الإبلاغ عن الثغرات
CONTRIBUTING.md                 قواعد المساهمة
docs/DOCKER.md                  تشغيل Docker
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

**Deployment rule:** Core reusable، Modules extensible، Frontend independent، Secrets outside Git، وSchema changes append-only عبر migrations.


# 26. U4: Docker / CI / Runtime Verification

U4 ينقل المشروع من مرحلة **Reusable Starter** إلى مرحلة **Release Candidate قابل للتحقق آليًا**. الهدف ليس إضافة Business Features جديدة، بل إثبات أن التثبيت، الترقية، migrations، Module Generator، وBackup/Restore يمكن اختبارهم على بيئة disposable قبل لمس Production.

## تشغيل محلي سريع بـDocker

```bash
cp .env.docker.example .env
# غيّر كلمات المرور داخل .env
docker compose up -d --build
```

ثم افتح:

```text
http://localhost:8080/install/
```

تفاصيل أكثر: [docs/DOCKER.md](docs/DOCKER.md).

## فحص البيئة

Static فقط:

```bash
php tools/doctor.php --static
```

على Runtime متصل بقاعدة البيانات:

```bash
php tools/doctor.php
```

الفحص يتحقق من PHP/extensions، الكتابة على storage/uploads، DB، migration drift/pending state، وModule manifests.

## Release check

```bash
php tools/release-check.php
```

ويجمع verifier + static doctor + PHP syntax.

## Runtime integration suite

**ممنوع تشغيل destructive tests على Production.** الاختبارات نفسها ترفض العمل إلا إذا كان اسم قاعدة البيانات يحتوي `test` أو `ci`.

```bash
bash tests/integration/http-installer.sh
php tests/integration/upgrade-v0_3.php
php tests/integration/runtime.php
php tests/integration/generator.php
php tools/doctor.php
```

تثبت هذه السلسلة:

- Installer من Database فارغة حتى Login.
- API health/discovery.
- Upgrade حقيقي من v0.3 schema إلى v0.4.
- Migration checksum drift detection.
- MariaDB data writes/reads.
- Backup + Restore للـDB وuploads على Clone.
- Pre-Restore Backup.
- تعطيل Module بدون حذف جدولها.
- توليد Module جديدة على نسخة disposable واكتشافها بدون تعديل Core.

راجع [docs/RUNTIME_TESTING.md](docs/RUNTIME_TESTING.md).

## Migration locking

U4 يضيف MariaDB/MySQL advisory lock أثناء تطبيق migrations، والـlock مرتبط باسم Database حتى لا يمنع مشروع CMS آخر يستخدم نفس MySQL Server. الهدف منع تشغيل migrations متزامنة من عمليتي deploy/admin في نفس اللحظة.

## Backup format v3

النسخ الجديدة تحتوي Metadata إضافية:

```text
platform_version
schema signature
migration snapshot
table data
upload hashes + sizes
```

وقبل Restore يتم فحص:

- سلامة envelope/hash.
- upload hashes/paths.
- وجود الجداول/الأعمدة المطلوبة.
- عدم وجود Migration Drift.
- عدم وجود عمود جديد Required لا تستطيع النسخة القديمة ملأه.

U4 ما زال يقرأ Backup v2 من v0.3 للتوافق الخلفي.

## GitHub Actions

الملف:

```text
.github/workflows/ci.yml
```

يشغل:

1. Static matrix على PHP 8.2 / 8.3 / 8.4.
2. MariaDB 11.4 service.
3. HTTP installer/login smoke.
4. v0.3 → v0.4 upgrade test.
5. DB + Backup/Restore runtime test.
6. Module Generator test.
7. Runtime Doctor.

## قبل Tag أو Production Deploy

استخدم:

```text
RELEASE_CHECKLIST.md
```

ولا تعتبر Release مقفولة حتى تكون CI خضراء ويكون Restore مجربًا على disposable clone.
