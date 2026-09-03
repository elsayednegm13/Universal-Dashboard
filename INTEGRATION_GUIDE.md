# Universal CMS — Integration Guide

## 1. اكتشاف الـAPI

اطلب:

```http
GET /api/v1/
```

النتيجة تعرض فقط الـroutes الخاصة بالوحدات المفعلة.

## 2. إعدادات الموقع

```http
GET /api/v1/site.php
```

تستخدم منها اسم الموقع، Logo/Favicon paths، اللغة، Theme default، SEO والوحدات المفعلة.

## 3. Content

```http
GET /api/v1/content.php
```

كل عنصر له `content_key` ثابت. في الواجهة اربط العناصر بواسطة المفتاح بدل الاعتماد على ترتيب الصفوف.

## 4. Catalog

كل المنتجات:

```http
GET /api/v1/catalog.php?type=products
```

منتج بواسطة slug:

```http
GET /api/v1/catalog.php?type=products&slug=my-product
```

فلترة:

```http
GET /api/v1/catalog.php?type=products&brand=brand-slug&category=category-slug
```

Featured:

```http
GET /api/v1/catalog.php?type=products&featured=1
```

## 5. Contact

قراءة قنوات التواصل:

```http
GET /api/v1/contact.php
```

إرسال رسالة:

```http
POST /api/v1/contact.php
Content-Type: application/json

{
  "name": "Visitor",
  "email": "visitor@example.com",
  "phone": "+20...",
  "subject": "Question",
  "message": "..."
}
```

## 6. Analytics

```http
POST /api/v1/analytics.php
Content-Type: application/json

{
  "event_type": "page_view",
  "entity_type": "page",
  "entity_key": "home",
  "language": "ar"
}
```

Allowed events:
- `page_view`
- `section_view`
- `item_view`
- `conversion`
- `whatsapp_click`

لا ترسل اسم أو بريد أو هاتف أو IP في analytics.

## 7. ربط JavaScript بسيط

```js
const site = await fetch('/api/v1/site.php').then(r => r.json());
const content = await fetch('/api/v1/content.php').then(r => r.json());
```

لو الواجهة على Domain/Origin منفصل، أضف Origin صراحة إلى `CMS_ALLOWED_ORIGINS` في إعداد السيرفر.
