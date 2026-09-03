# Changelog

## 0.3.0 — U3 Module SDK & Versioned Migrations

- Replaced hardcoded module registry with manifest discovery.
- Added `module.php` manifest to built-in modules.
- Added module version/order/table/dependency metadata.
- Added versioned migration engine and `cms_schema_migrations`.
- Added migration checksum drift detection.
- Added CLI migration runner/status tool.
- Added Module Generator with dry-run mode.
- Added generic CRUD/API module templates.
- Added dynamic backup table discovery from manifests.
- Added static platform verifier.
- Added Git-safe `.gitignore` and runtime placeholders.
- Protected developer tools on Apache.
- Added comprehensive GitHub README and architecture/security/deployment/module documentation.

## 0.2.0 — U2 Module Extraction

- Extracted reusable CMS modules.
- Added real Admin pages and public API v1.
- Added Catalog, FAQ, Partners, Gallery, Reels, Contact, Analytics, Media, Audit and Backups modules.
- Added responsive shared admin theme.
- Added login throttling and safer uploads.

## 0.1.0 — U1 Foundation

- Installer.
- Generic config/database/auth/session/CSRF core.
- Site settings.
- Module state foundation.
- Initial API foundation.
