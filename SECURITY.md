# Security Policy

Universal CMS Admin is designed for self-hosted websites. Security issues should be handled privately before public disclosure.

## Supported release

The current development release is **v0.4.0 RC**. For production, deploy only after your own environment passes `php tools/doctor.php`, migrations are clean, and backup/restore has been verified on a disposable clone.

## Reporting a vulnerability

Do not publish credentials, database dumps, backup files, private URLs, or exploit details in a public issue. Use a private repository security advisory or another private channel controlled by the repository owner.

Include:

- affected version;
- affected module/path;
- reproduction steps;
- expected vs actual behavior;
- impact;
- proposed mitigation if known.

## Secrets

Never commit:

- `storage/config.local.php`;
- `.env`;
- database passwords;
- `storage/backups/*`;
- user uploads containing private data.

See `docs/SECURITY.md` for the technical security model.
