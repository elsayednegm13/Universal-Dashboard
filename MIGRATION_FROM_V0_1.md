# Upgrade from Universal CMS v0.1 Foundation to v0.2

لو كنت ثبتت v0.1 بالفعل:

1. خذ Backup من قاعدة البيانات وملفات المشروع بالطريقة المتاحة على الاستضافة.
2. ارفع ملفات v0.2 Patch فوق v0.1.
3. افتح `/admin/modules.php` واضغط **حفظ الوحدات** مرة واحدة.
4. النظام ينفذ `CREATE TABLE IF NOT EXISTS` لكل Module مفعلة ويضيف جداول U2 بدون حذف بيانات Core.
5. أضف `CMS_APP_KEY` و`CMS_ALLOWED_ORIGINS` إلى `storage/config.local.php` إن لم يكونا موجودين. يمكن توليد App Key بقيمة hex عشوائية طويلة.
6. جرّب `/api/v1/health.php` ثم الوحدات المفعلة.

لا يوجد `DROP TABLE` أو `DROP COLUMN` في Schemas الخاصة بـU2.
