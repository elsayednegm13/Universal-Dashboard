# Universal CMS Release Checklist

Use this checklist before tagging a release or deploying a new project.

## Repository

- [ ] `VERSION` matches `includes/version.php`.
- [ ] `php tools/verify.php` passes.
- [ ] All PHP files pass `php -l`.
- [ ] No `storage/config.local.php`, `.env`, install lock, backups, or user uploads are committed.
- [ ] README / CHANGELOG / migration guide are current.

## Runtime

- [ ] Required PHP extensions are enabled: PDO, pdo_mysql, mbstring, fileinfo, zlib.
- [ ] `php tools/doctor.php` passes on target runtime.
- [ ] MySQL/MariaDB connection uses a project-specific DB user.
- [ ] `storage/` and `uploads/` permissions are correct without `777`.
- [ ] Installer becomes locked after first install.

## Database

- [ ] `php tools/migrate.php --status` reports no drift.
- [ ] Pending migrations are reviewed before application.
- [ ] Migration is tested on a disposable clone when updating an existing site.
- [ ] A current backup exists before production migration.

## Functional smoke

- [ ] Login / Logout.
- [ ] Light / Dark / System.
- [ ] Mobile sidebar and tables.
- [ ] Enabled Modules appear; disabled Modules return 404 and retain data.
- [ ] Public API v1 health/discovery.
- [ ] Upload and Media flow if enabled.
- [ ] Contact flow if enabled.
- [ ] Catalog/FAQ/Partners/Gallery/Reels if enabled.

## Backup / Restore

- [ ] Backup creation succeeds.
- [ ] Backup summary shows valid format/version.
- [ ] Restore compatibility preflight succeeds on a disposable clone.
- [ ] DB data restores.
- [ ] Upload files restore.
- [ ] Pre-restore backup is created.

## Production security

- [ ] HTTPS enabled.
- [ ] CORS allowlist contains only required origins.
- [ ] Internal directories are blocked by Apache/Nginx.
- [ ] PHP execution is blocked inside uploads.
- [ ] DB/runtime secrets are outside Git.
- [ ] Error display is disabled in production; server logging remains enabled.
- [ ] Installer remains locked.

## Release

- [ ] CI is green.
- [ ] CHANGELOG updated.
- [ ] Release archive checksum generated.
- [ ] Tag/release notes identify migration and restore requirements.
