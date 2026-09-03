# U3 — Starter Platform / Module SDK Report

## Status

`IMPLEMENTED — STATIC VERIFIED — REAL MYSQL RUNTIME PENDING`

## الهدف

تحويل Universal CMS من Template بها Modules جاهزة إلى Starter Platform تسمح بإضافة Modules جديدة بدون تعديل Core، مع Versioned Migrations وتوثيق كامل مناسب لـGitHub.

## ما تم تنفيذه

### 1. Dynamic Module Manifests

تم نقل تعريف كل Module إلى:

```text
modules/<key>/module.php
```

ويتم اكتشاف Modules تلقائيًا بدل القائمة Hardcoded.

الـmanifest يعرف:

- key
- label AR/EN
- description
- version
- display order
- default enabled state
- admin route
- API route
- owned tables
- dependencies

### 2. Module Registry v2

`includes/module-registry.php` أصبح مسؤولًا عن:

- Discovery.
- Manifest validation.
- Sidebar ordering metadata.
- Module state.
- Dependencies.
- Migration execution.
- Module-owned table inventory.

إضافة Module سليمة لا تحتاج تعديل Registry.

### 3. Versioned Migration Engine

تم إضافة:

```text
includes/migrations.php
database/migrations/
modules/*/migrations/
```

Tracking table:

```text
cms_schema_migrations
```

كل Migration تسجل SHA-256 checksum ووقت التطبيق.

تم تطبيع LF/CRLF قبل checksum لمنع drift كاذب بسبب اختلاف line endings بين Windows/Linux.

### 4. Migration Drift Protection

إذا تم تعديل Migration بعد تطبيقها، يتم رفضها بـ:

```text
MIGRATION_CHECKSUM_MISMATCH
```

المسار الصحيح هو إنشاء Migration جديدة.

### 5. CLI Migration Tool

```bash
php tools/migrate.php
php tools/migrate.php --all
php tools/migrate.php --status
```

كما تم إضافة تشغيل التحديثات من Admin → Modules.

### 6. Module Generator

```bash
php tools/generate-module.php services "الخدمات" "Services"
```

ينشئ:

- Manifest.
- Admin CRUD starter.
- Public API starter.
- Initial migration.
- Admin/API wrappers.
- Module README.

ويعمل بدون تعديل Core.

يدعم:

```text
--dry-run
```

ولا يقوم بالOverwrite لو Module أو routes موجودة بالفعل.

### 7. Module Dependencies Foundation

manifest تدعم:

```php
'depends' => ['media']
```

وعند اختيار Module يتم توسيع الاختيار تلقائيًا ليشمل dependencies.

### 8. Dynamic Backup Tables

Backup allowlist لم تعد تعتمد على قائمة Business Tables ثابتة.

Core tables + `tables` من manifests تدخل تلقائيًا في Backup.

هذا يعني أن Module جديدة مولدة بالـSDK تدخل في Backup بمجرد إعلان جداولها في manifest.

### 9. Git Safety

تم إضافة `.gitignore` لمنع رفع:

```text
storage/config.local.php
storage/install.lock
storage/backups runtime data
uploads runtime data
```

مع إبقاء `.htaccess` و`.gitkeep` المطلوبة.

### 10. Tools Protection

تم حماية `/tools` عبر Apache `.htaccess`، والـCLI scripts نفسها ترفض Web execution.

### 11. Static Verification Tool

```bash
php tools/verify.php
```

يفحص:

- Module manifests.
- Admin/API module files.
- Route wrappers.
- Migration files.
- Runtime secret files داخل distributable package.

### 12. GitHub README / Documentation

تم استبدال README المختصر بدليل شامل يغطي:

- تعريف المشروع.
- المعمارية النظرية.
- التثبيت العملي.
- Shared Hosting / VPS.
- Apache / Nginx.
- Configuration.
- Modules.
- Module SDK.
- Migrations.
- API integration.
- CORS.
- Security.
- Uploads.
- Backup / Restore.
- Analytics privacy.
- Production deployment.
- Upgrade.
- GitHub workflow.
- Tests.
- Troubleshooting.
- Current limitations.

كما تمت إضافة Docs منفصلة للمعمارية، SDK، Migrations، API، Security، Deployment وTroubleshooting.

## Backward Compatibility

تم الإبقاء على:

```php
cmsInstallModuleSchema()
```

كـcompatibility alias، لكنه في U3 يشغل versioned migrations بدل `schema.sql`.

ملفات `schema.sql` القديمة بقيت داخل Built-in Modules كمرجع/compatibility، لكن lifecycle الجديد يعتمد `migrations/`.

## Database Safety

- لا توجد DROP TABLE / DROP COLUMN migrations في U3.
- Initial module migrations تستخدم `CREATE TABLE IF NOT EXISTS`.
- Disable Module لا يحذف data.
- Migration status GET لا ينشئ tracking table؛ الإنشاء يتم عند تنفيذ migration فعليًا.

## Runtime Gate

بيئة التنفيذ الحالية تحتوي:

```text
PDO
fileinfo
zlib
```

ولا تحتوي `pdo_mysql` أو `mbstring`، لذلك لم يتم الادعاء بنجاح Install/Migrate حقيقي على MySQL داخل هذه البيئة.

المطلوب لإغلاق Runtime:

1. MySQL/MariaDB test database.
2. `pdo_mysql` + `mbstring`.
3. Fresh installer test.
4. Enable/disable module test.
5. Migration status/apply test.
6. Generate a test module and migrate it.
7. Backup/restore clone proof.
