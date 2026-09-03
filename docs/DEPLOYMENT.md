# Production Deployment Checklist

## قبل الرفع

- [ ] Backup للنسخة الحالية إن كانت Update.
- [ ] PHP version مناسب.
- [ ] `pdo_mysql`, `mbstring`, `fileinfo`, `zlib` متاحة.
- [ ] DB user مخصص.
- [ ] HTTPS جاهز.

## أثناء الرفع

- [ ] لا تستبدل `storage/config.local.php` في Update.
- [ ] لا تمسح `uploads/`.
- [ ] ارفع migrations الجديدة.
- [ ] تأكد من حماية internal directories.

## بعد الرفع

```bash
php tools/migrate.php --status
php tools/migrate.php
php tools/migrate.php --status
```

- [ ] drift = 0.
- [ ] pending = 0 للوحدات المطلوبة.
- [ ] Login يعمل.
- [ ] Theme يعمل Light/Dark/System.
- [ ] Mobile Sidebar يعمل.
- [ ] API discovery يعمل.
- [ ] Frontend endpoints تعمل.
- [ ] Upload يعمل.
- [ ] Backup creation يعمل.
- [ ] Restore تم اختباره على Clone.

## Nginx

تذكر أن `.htaccess` لا تعمل على Nginx.
