# Changelog

## 0.4.0 RC — U4 Release Engineering & Runtime Verification

- Added Docker PHP 8.3 + Apache + MariaDB 11.4 development runtime.
- Added GitHub Actions static matrix for PHP 8.2/8.3/8.4.
- Added MariaDB integration CI for installer, login, API health, upgrade, backup/restore and module generation.
- Added `tools/doctor.php` environment/runtime diagnostics.
- Added `tools/release-check.php` release gate.
- Added destructive-test safety guard requiring DB names containing `test` or `ci`.
- Added real v0.3 → v0.4 migration fixture/test.
- Added `cms_release_history` and U4 release migration.
- Added DB-scoped advisory locking for migration execution.
- Upgraded backup format to v3 with platform/schema/migration metadata and upload size/hash validation.
- Added restore compatibility preflight while keeping v2 backup read compatibility.
- Improved Backup Admin UI with backup validity/version/content summary.
- Added release/security/contribution documentation and production checklist.

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
