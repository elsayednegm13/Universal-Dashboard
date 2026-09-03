# Contributing

## Development flow

1. Create a branch.
2. Keep Core changes generic; project-specific logic belongs in a Module.
3. Never edit an already-applied migration. Add a new migration.
4. Run:

```bash
php tools/verify.php
php tools/doctor.php --static
find . -type f -name '*.php' -not -path './storage/*' -print0 | xargs -0 -n1 php -l
```

5. For DB changes, run the disposable MariaDB integration suite before merging.
6. Do not commit runtime secrets, backups, or uploads.

## Module changes

New reusable business areas should normally be created through:

```bash
php tools/generate-module.php <key> "Arabic Label" "English Label"
```

Then customize the generated Module and add append-only migrations.

## Pull requests

A PR should explain:

- what changed;
- why Core vs Module was chosen;
- migration impact;
- API compatibility;
- security impact;
- test evidence.
