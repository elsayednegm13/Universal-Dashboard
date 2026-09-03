# Docker Development Runtime

U4 includes a reproducible local runtime using PHP 8.3 + Apache + MariaDB 11.4.

## Start

```bash
cp .env.docker.example .env
# Edit passwords in .env
docker compose up -d --build
```

Open:

```text
http://localhost:8080/install/
```

Default Docker DB host from the installer is:

```text
db
```

Use the database name/user/password configured in `.env`.

## Services

- `web`: PHP 8.3 Apache with `pdo_mysql`, `mbstring`, `fileinfo`.
- `db`: MariaDB 11.4.

Named volumes persist DB, uploads and storage. The Docker entrypoint restores the protective `.htaccess` files into persistent volumes if needed.

## Stop

```bash
docker compose down
```

Delete disposable development volumes only when intentional:

```bash
docker compose down -v
```

Never use `down -v` against a runtime whose volumes contain data you need.
