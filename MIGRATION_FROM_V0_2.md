# Upgrade from Universal CMS v0.2 to v0.3

## Goal

U3 introduces manifest discovery and versioned migrations without deleting existing v0.2 data.

## Before Upgrade

1. Create a database backup.
2. Copy `storage/config.local.php` to a safe location.
3. Keep the full `uploads/` directory.
4. Prefer testing the upgrade on a clone first.

## File Upgrade

Replace application source with v0.3 while preserving:

```text
storage/config.local.php
uploads/
storage/backups/   if you want to keep local backups
```

Do not copy a development `install.lock` over a real production one. Preserve the target environment's runtime state.

## Database Upgrade

Run:

```bash
php tools/migrate.php --all
```

Then:

```bash
php tools/migrate.php --status
```

Initial U3 module migrations use `CREATE TABLE IF NOT EXISTS`, so existing v0.2 module tables are preserved while their migration state becomes tracked.

## Runtime Verification

- Login.
- Dashboard.
- Modules page.
- Run migration status.
- Open every enabled module.
- Test API discovery.
- Test the frontend routes used by the site.
- Create a new backup.

## Rollback

If application compatibility fails before any intentional schema migration beyond U3 tracking:

1. Restore v0.2 source.
2. Keep the existing database.
3. If needed, restore the pre-upgrade DB backup.

Do not delete `cms_schema_migrations` just to suppress a checksum error; investigate drift instead.
