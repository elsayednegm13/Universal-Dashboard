# Runtime Testing

U4 adds executable integration tests for a disposable MariaDB database.

## Safety contract

The destructive reset/upgrade tests refuse to run unless `CMS_DB_NAME` contains `test` or `ci`.

Do not rename a production database to bypass this check. Use a disposable clone or CI database.

## Required environment

```bash
export CMS_DB_HOST=127.0.0.1
export CMS_DB_PORT=3306
export CMS_DB_NAME=universal_cms_test
export CMS_DB_USER=cms_test
export CMS_DB_PASS='...'
export CMS_APP_KEY='64-hex-secret'
```

## Test sequence

```bash
bash tests/integration/http-installer.sh
php tests/integration/upgrade-v0_3.php
php tests/integration/runtime.php
php tests/integration/generator.php
php tools/doctor.php
```

### What is proven

`http-installer.sh`:

- empty DB reset;
- installer GET;
- CSRF-protected installer POST;
- all built-in Modules selected;
- config + install lock creation;
- admin login;
- dashboard access;
- API health and discovery.

`upgrade-v0_3.php`:

- creates a v0.3 core fixture;
- records the U3 migration;
- runs v0.4 migrations;
- verifies release-history migration and zero drift.

`runtime.php`:

- creates fresh Core + all Module tables;
- checks migration records;
- proves checksum drift detection;
- creates and restores a real backup on disposable DB;
- proves an upload is restored;
- proves disabling a Module does not delete its tables/data;
- checks final migration status.

`generator.php`:

- copies the project to a disposable directory;
- generates a `services` Module;
- verifies files/routes/manifest discovery;
- runs the platform verifier.
