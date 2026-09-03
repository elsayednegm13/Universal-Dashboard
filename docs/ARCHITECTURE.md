# Architecture

## Principles

1. **Core stays generic** — project-specific behavior belongs in modules.
2. **Frontend independence** — public data is exposed through API v1.
3. **Modules are discoverable** — adding a valid manifest is enough for registry discovery.
4. **Disable is non-destructive** — module state does not drop data.
5. **Schema history is append-only** — versioned migrations track database evolution.
6. **Secrets are runtime data** — never part of the distributable repository.

## Request Flow — Admin

```text
Browser
  ↓
/admin/<route>.php
  ↓
module wrapper (optional)
  ↓
modules/<key>/admin.php
  ↓
cmsAdminModuleBoot()
  ├─ require auth
  ├─ connect DB
  └─ verify module enabled
  ↓
CSRF-protected mutation / query
  ↓
Audit
  ↓
Shared Admin UI
```

## Request Flow — Public API

```text
Frontend
  ↓
/api/v1/<route>.php
  ↓
module wrapper
  ↓
modules/<key>/api.php
  ↓
cmsApiBootstrap()
  ├─ JSON headers
  ├─ CORS exact allowlist
  └─ OPTIONS handling
  ↓
module enabled check
  ↓
public-only query
  ↓
JSON response
```

## Module Discovery

`includes/module-registry.php` scans:

```text
modules/*/module.php
```

The directory name and manifest `key` must match.

The registry drives:

- Admin sidebar.
- Installer module selection.
- Modules page.
- API discovery.
- Module migration locations.
- Backup table allowlist.

## Database Evolution

```text
module.php
   │
   └── migrations/*.sql
            ↓
      migration runner
            ↓
 cms_schema_migrations
            ↓
         MySQL
```

Applied migration checksums make accidental historical edits visible.
