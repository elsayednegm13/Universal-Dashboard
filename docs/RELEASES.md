# Release Engineering

## Versioning

Universal CMS uses semantic-style project versions:

```text
MAJOR.MINOR.PATCH
```

Development releases may be marked RC in `includes/version.php`.

## Release process

1. Update `VERSION` and `includes/version.php`.
2. Add append-only migrations if schema changes are needed.
3. Update `CHANGELOG.md` and migration guide.
4. Run static checks.
5. Run disposable MariaDB integration tests.
6. Run backup/restore proof on clone.
7. Build release archive.
8. Generate SHA-256 checksum.
9. Tag only after CI/runtime evidence is accepted.

## Do not

- edit an applied migration;
- tag a release with pending drift;
- use production DB for destructive verification;
- commit runtime secrets into release archives.
