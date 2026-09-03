# Tests

U4 adds two levels of verification:

1. **Static/repository checks** — no database required.
2. **Disposable MariaDB integration checks** — must run only on a database whose name contains `test` or `ci`.

Commands:

```bash
php tools/release-check.php
php tools/doctor.php --static
```

With a disposable MySQL/MariaDB database configured through `CMS_DB_*`:

```bash
bash tests/integration/http-installer.sh
php tests/integration/runtime.php
php tests/integration/generator.php
php tools/doctor.php
```

`reset-db.php` and `runtime.php` intentionally refuse destructive execution unless the configured database name contains `test` or `ci`.
