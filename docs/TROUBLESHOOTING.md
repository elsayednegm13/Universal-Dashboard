# Troubleshooting

## DB Driver Missing

الرسالة:

```text
could not find driver
```

الحل: فعّل `pdo_mysql` لنفس PHP runtime المستخدم بواسطة Web Server/CLI.

## 500 بعد Upload

راجع PHP error log وpermissions لـ`uploads/`.

## Installer Locked

وجود:

```text
storage/install.lock
```

يعني أن النظام مثبت. لا تحذفه إلا لو تقصد إعادة التثبيت وتفهم أثر ذلك.

## Module لا تظهر

تحقق من:

- `modules/<key>/module.php` موجود.
- key يطابق اسم المجلد.
- manifest valid.
- Module enabled في `cms_modules`.

## API Module 404

قد تكون Module disabled أو wrapper غير موجود.

## Migration Drift

شغّل:

```bash
php tools/migrate.php --status
```

لو drift > 0، استرجع migration الأصلية وأنشئ migration جديدة بدل تعديل القديمة.

## CSS / Dark Mode

Hard refresh، ثم افحص:

```text
/admin/assets/admin-theme.css
```

وLocal Storage key:

```text
universal_cms_admin_theme
```

## Nginx Internal Files Visible

أضف deny rules في Nginx. `.htaccess` لا تؤثر عليه.
