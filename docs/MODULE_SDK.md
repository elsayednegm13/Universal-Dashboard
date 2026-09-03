# Module SDK

## الهدف

Module SDK يسمح بإضافة وحدة جديدة للمشروع بدون تعديل Core Registry أو Sidebar أو API discovery يدويًا.

## التوليد

```bash
php tools/generate-module.php services "الخدمات" "Services"
```

الناتج:

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

## Manifest Contract

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

### key

- lowercase.
- يبدأ بحرف.
- حروف/أرقام/underscore فقط.
- يجب أن يطابق اسم مجلد Module.

### tables

اكتب كل الجداول التي تملكها Module. القائمة تستخدم في Backup allowlist.

### depends

مثال لو Module `projects` تحتاج `media`:

```php
'depends' => ['media'],
```

عند تفعيل `projects` سيتم تضمين dependency تلقائيًا.

## تخصيص Schema

قبل أول Migration في مشروع جديد يمكنك تعديل `001_initial.sql`.

بعد تطبيقها لا تعدلها. أضف:

```text
002_add_featured.sql
003_add_client_name.sql
```

## Admin Contract

ابدأ دائمًا بـ:

```php
require_once __DIR__ . '/../../includes/module-admin.php';
$pdo = cmsAdminModuleBoot('services');
```

هذا يفرض:

- Admin authentication.
- Module enabled state.
- Shared helpers/UI availability.

عمليات POST يجب أن تستخدم:

```php
cmsVerifyCsrf();
```

وفي Forms:

```php
<?=cmsCsrfField()?>
```

استخدم prepared statements للقيم.

## Public API Contract

ابدأ بـ:

```php
require_once __DIR__ . '/../../includes/api.php';
cmsApiBootstrap();
require_once __DIR__ . '/../../includes/module-registry.php';

$pdo = cmsPdo();
if (!cmsModuleEnabled($pdo, 'services')) {
    cmsJson(['ok'=>false,'error'=>'MODULE_DISABLED'], 404);
}
```

لا تعرض hidden/draft rows من Public API إلا لو Business Requirement يقول غير ذلك.

## Uploads

استخدم:

```php
cmsUploadFile(...)
```

ولا تستخدم اسم الملف الأصلي كاسم تخزين مباشر.

## Audit

بعد Mutation مهمة:

```php
cmsAudit('services.create', 'cms_services_items', (string)$id);
```

لا تسجل Passwords أو Tokens أو محتوى حساس داخل details.

## Wrapper Files

`modules/` محمي من الوصول المباشر، لذلك Admin/API public routes تعمل من wrappers:

```text
/admin/services.php
/api/v1/services.php
```

الـGenerator ينشئهم تلقائيًا.

## حذف Module من الكود

تعطيل Module من Dashboard لا يحذف بيانات.

لو تريد إزالة Module من Repository نهائيًا:

1. Disable في Production.
2. خذ Backup.
3. قرر بشكل منفصل هل بياناتها يجب الاحتفاظ بها أو حذفها.
4. لا تجعل حذف ملفات Module يؤدي تلقائيًا إلى `DROP TABLE`.
