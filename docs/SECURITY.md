# Security Model

## Secrets

Production secrets يجب أن تكون في Environment Variables أو `storage/config.local.php` فقط.

`.gitignore` يمنع ملف runtime config من Git افتراضيًا.

## Authentication

- Password hashing عبر PHP password API.
- Session regeneration بعد login.
- Active admin check.
- Login throttling.

## CSRF

كل mutation داخل Admin يجب أن تتحقق من CSRF token.

## Uploads

- Extension allowlist.
- MIME check.
- Random storage filename.
- SVG disabled by default.
- PHP execution blocked داخل uploads.

## Internal Paths

Apache يستخدم `.htaccess`.

Nginx يحتاج deny rules صريحة لـ:

```text
/storage
/includes
/database
/modules
/tools
```

## CORS

Exact allowlist only. لا تستخدم `*` تلقائيًا خصوصًا لو أضفت authenticated browser APIs مستقبلًا.

## Analytics

النسخة الحالية تخزن aggregate counters، وليس raw visitor profile.

## Backup

Backup archive يحتوي بيانات حساسة حسب محتوى الموقع. خزنه في مكان محمي ولا تضعه داخل Git أو public web path.

## Production Errors

لا تعرض stack traces أو DB errors للزائر. استخدم server logs.

## Updates

لا تعدل migrations مطبقة. checksum drift مقصود لمنع historical schema ambiguity.
