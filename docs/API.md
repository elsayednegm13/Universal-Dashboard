# API v1 Reference

## Discovery

```http
GET /api/v1/
```

يعرض Version + routes المفعلة.

## Health

```http
GET /api/v1/health.php
```

## Site

```http
GET /api/v1/site.php
```

يعرض public site settings والوحدات المفعلة.

## Content

```http
GET /api/v1/content.php
```

استخدم `content_key` كعقد ثابت بين CMS والFrontend.

## FAQ / Partners / Gallery / Reels

```http
GET /api/v1/faq.php
GET /api/v1/partners.php
GET /api/v1/gallery.php
GET /api/v1/reels.php
```

## Catalog

```http
GET /api/v1/catalog.php?type=products
GET /api/v1/catalog.php?type=products&slug=my-product
GET /api/v1/catalog.php?type=products&featured=1
GET /api/v1/catalog.php?type=brands
GET /api/v1/catalog.php?type=categories
```

## Contact

GET لقنوات التواصل، POST لإرسال رسالة.

```http
POST /api/v1/contact.php
Content-Type: application/json
```

```json
{
  "name": "Visitor",
  "email": "visitor@example.com",
  "phone": "+20...",
  "subject": "Question",
  "message": "Hello"
}
```

## Analytics

```http
POST /api/v1/analytics.php
Content-Type: application/json
```

```json
{
  "event_type": "page_view",
  "entity_type": "page",
  "entity_key": "home",
  "language": "ar"
}
```

## Errors

Modules disabled ترجع عادة:

```json
{
  "ok": false,
  "error": "MODULE_DISABLED"
}
```

مع HTTP 404.

## CORS

فقط origins الموجودة في `CMS_ALLOWED_ORIGINS` تحصل على CORS headers.
