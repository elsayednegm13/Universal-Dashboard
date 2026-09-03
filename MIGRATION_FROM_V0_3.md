# Upgrade from Universal CMS v0.3 to v0.4 RC

## What changes

U4 is primarily a release-engineering and verification upgrade. Existing business module data is preserved.

Database change:

```text
002_u4_release_history.sql
```

It creates `cms_release_history` and records `0.4.0`.

## Before upgrade

1. Create a current backup.
2. Copy `storage/config.local.php` to a safe location.
3. Preserve `uploads/`.
4. Test this upgrade on a disposable clone first.
5. Confirm v0.3 migration status has no checksum drift.

## Upgrade

Replace application source while preserving target runtime files, then run:

```bash
php tools/migrate.php --all
php tools/migrate.php --status
php tools/doctor.php
```

Expected core state:

```text
pending=0
drift=0
```

`cms_release_history` should contain `0.4.0`.

## Backup compatibility

U4 creates backup format v3. The reader keeps support for v2 backups created by v0.3.

New restores perform stronger compatibility validation before mutation.

## Runtime smoke

Verify:

- Login/logout.
- Theme Light/Dark/System.
- Modules page.
- API health/discovery.
- Enabled modules.
- Upload flow where used.
- New backup creation.

Run Restore only on a clone first.

## Rollback

If U4 source fails before production data changes beyond the additive release-history table, restore v0.3 source. The new `cms_release_history` table is additive and does not alter existing module rows.

Do not delete migration records to hide checksum errors.
