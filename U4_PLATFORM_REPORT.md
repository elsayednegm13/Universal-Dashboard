# U4 Platform Report — Release Engineering & Runtime Verification

## Status

**Implementation: COMPLETE**

**Current-container MariaDB runtime execution: BLOCKED BY ENVIRONMENT** because this container has PHP 8.4/PDO but does not have `mbstring`, `pdo_mysql`, a MySQL/MariaDB server, or Docker. U4 therefore includes an executable GitHub Actions + Docker runtime path so the missing proof can run on a disposable MariaDB environment instead of being simulated.

## Goal

U4 turns the reusable CMS starter into a release candidate with repeatable deployment/runtime evidence:

- reproducible PHP/MariaDB development runtime;
- CI matrix;
- full installer/login HTTP smoke;
- real disposable DB integration test;
- v0.3 -> v0.4 upgrade test;
- migration concurrency lock;
- stronger backup/restore compatibility validation;
- release doctor/checklist/tooling.

## Added runtime/deployment assets

- `Dockerfile`
- `docker-compose.yml`
- `docker/entrypoint.sh`
- `.env.example`
- `.env.docker.example`
- `.dockerignore`
- `Makefile`
- `.github/workflows/ci.yml`

Docker runtime uses:

- PHP 8.3 + Apache;
- `pdo_mysql`;
- `mbstring`;
- MariaDB 11.4.

## CI

Static matrix:

- PHP 8.2
- PHP 8.3
- PHP 8.4

MariaDB runtime job executes:

1. HTTP installer from empty disposable DB.
2. Admin login.
3. Dashboard access.
4. API health/discovery.
5. v0.3 -> v0.4 migration test.
6. Fresh Core + all Modules DB integration.
7. Migration drift detection.
8. Backup/Restore DB + upload proof.
9. Module disable/data retention proof.
10. Disposable Module Generator proof.
11. Runtime Doctor.

## Database upgrade

Added migration:

```text
database/migrations/002_u4_release_history.sql
```

It adds:

```text
cms_release_history
```

and records release `0.4.0`.

A v0.3 core fixture and executable upgrade test were added under `tests/`.

## Migration hardening

Migration execution now takes a MySQL/MariaDB advisory lock scoped by database name. This prevents two deployments/processes from applying schema migrations concurrently to the same project while avoiding unnecessary locking between separate CMS databases on the same DB server.

Migration filename validation is enforced during execution.

## Backup / Restore v3

New backups include:

- backup format version;
- CMS platform version;
- table schema metadata;
- migration snapshot/checksums;
- table data;
- uploads with SHA-256 and byte size.

Restore preflight validates the backup before mutation:

- envelope/hash;
- upload paths/hashes/sizes;
- required tables/columns;
- required new columns compatibility;
- migration drift.

Backups created by v0.3 (format v2) remain readable for backward compatibility.

The Admin Backup page now displays validity, CMS/backup version, table count and upload count.

## Safety of integration tests

Destructive DB test scripts refuse to run unless the configured DB name contains:

```text
test
```

or:

```text
ci
```

This is an additional guard; tests should still be run only on disposable databases/clones.

## Operational tools

### Environment doctor

```bash
php tools/doctor.php --static
php tools/doctor.php
```

### Repository/release verifier

```bash
php tools/verify.php
php tools/release-check.php
```

### Runtime suite

```bash
bash tests/integration/http-installer.sh
php tests/integration/upgrade-v0_3.php
php tests/integration/runtime.php
php tests/integration/generator.php
```

## GitHub documentation added

- `SECURITY.md`
- `CONTRIBUTING.md`
- `RELEASE_CHECKLIST.md`
- `MIGRATION_FROM_V0_3.md`
- `docs/DOCKER.md`
- `docs/RUNTIME_TESTING.md`
- `docs/RELEASES.md`
- PR template
- README updated for U4.

## Gate decision

U4 source/build gate: **PASS**.

Actual MariaDB runtime gate: **PENDING EXTERNAL/DISPOSABLE RUNTIME** until CI or Docker is executed in an environment with `pdo_mysql` + MariaDB.

Do not label v0.4 as a production-final release solely from the current-container static proof.
