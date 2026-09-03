# Versioned Migrations

## الفكرة

Schema changes تعامل كتاريخ append-only وليس كملف schema واحد يتم تعديله باستمرار.

```text
001_initial.sql
002_add_status.sql
003_add_index.sql
```

## Tracking Table

```text
cms_schema_migrations
```

يخزن:

- `scope_key`
- `migration_key`
- `checksum`
- `applied_at`

Scopes:

```text
core
module:content
module:catalog
module:services
...
```

## Commands

```bash
php tools/migrate.php
php tools/migrate.php --all
php tools/migrate.php --status
```

بدون `--all` يتم تشغيل Enabled Modules فقط.

## Drift

تغيير ملف migration بعد تطبيقه يسبب checksum mismatch.

لا تصلحه بتعديل DB tracking يدويًا. أعد migration الأصلية وأنشئ migration جديدة للتغيير المطلوب.

## Naming

استخدم ترتيبًا متزايدًا:

```text
001_initial.sql
002_add_external_url.sql
003_add_featured.sql
```

أو تاريخيًا:

```text
20260903_001_add_status.sql
```

المهم أن أسماء الملفات تظل ثابتة بعد التطبيق.

## Destructive Changes

قبل:

- DROP TABLE
- DROP COLUMN
- mass UPDATE
- تغيير نوع قد يفقد بيانات

خذ Backup واختبر على Clone.

## SQL Contract

Runner الحالي يدعم plain SQL statements المنتهية بـ`;`.

لا تستخدم scripts تحتاج `DELIMITER` أو stored procedures مع parser الحالي.
