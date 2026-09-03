#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PORT="${CMS_TEST_HTTP_PORT:-8090}"
BASE="http://127.0.0.1:${PORT}"
COOKIE="$(mktemp)"
GET_HTML="$(mktemp)"
LOGIN_HTML="$(mktemp)"
SERVER_LOG="$(mktemp)"

cleanup() {
  if [[ -n "${SERVER_PID:-}" ]]; then kill "$SERVER_PID" >/dev/null 2>&1 || true; fi
  rm -f "$COOKIE" "$GET_HTML" "$LOGIN_HTML" "$SERVER_LOG"
  rm -f "$ROOT/storage/config.local.php" "$ROOT/storage/install.lock"
}
trap cleanup EXIT

php "$ROOT/tests/integration/reset-db.php"
rm -f "$ROOT/storage/config.local.php" "$ROOT/storage/install.lock"

php -S "127.0.0.1:${PORT}" -t "$ROOT" >"$SERVER_LOG" 2>&1 &
SERVER_PID=$!
for _ in $(seq 1 40); do
  if curl -fsS "$BASE/install/" -c "$COOKIE" -o "$GET_HTML"; then break; fi
  sleep 0.25
done

grep -q 'تثبيت Universal CMS' "$GET_HTML"
TOKEN="$(sed -n 's/.*name="csrf_token" value="\([^"]*\)".*/\1/p' "$GET_HTML" | head -1)"
[[ -n "$TOKEN" ]]

ARGS=(
  --data-urlencode "csrf_token=$TOKEN"
  --data-urlencode "site_name=Universal CMS Installer CI"
  --data-urlencode "base_url=$BASE"
  --data-urlencode "allowed_origins=$BASE"
  --data-urlencode "db_host=${CMS_DB_HOST:-127.0.0.1}"
  --data-urlencode "db_port=${CMS_DB_PORT:-3306}"
  --data-urlencode "db_name=${CMS_DB_NAME:-universal_cms_ci}"
  --data-urlencode "db_user=${CMS_DB_USER:-cms_ci}"
  --data-urlencode "db_pass=${CMS_DB_PASS:-cms_ci_password}"
  --data-urlencode "admin_name=CI Admin"
  --data-urlencode "admin_email=ci-admin@example.test"
  --data-urlencode "admin_password=Installer-CI-Password-123!"
)
for key in analytics audit backups catalog contact content faq gallery media partners reels; do
  ARGS+=(--data-urlencode "modules[]=$key")
done

CODE="$(curl -sS -o /tmp/cms-install-post.html -w '%{http_code}' -b "$COOKIE" -c "$COOKIE" -X POST "${ARGS[@]}" "$BASE/install/")"
[[ "$CODE" == "302" ]]
[[ -f "$ROOT/storage/config.local.php" ]]
[[ -f "$ROOT/storage/install.lock" ]]

curl -fsS "$BASE/admin/login.php" -b "$COOKIE" -c "$COOKIE" -o "$LOGIN_HTML"
TOKEN="$(sed -n 's/.*name="csrf_token" value="\([^"]*\)".*/\1/p' "$LOGIN_HTML" | head -1)"
[[ -n "$TOKEN" ]]
CODE="$(curl -sS -o /tmp/cms-login-post.html -w '%{http_code}' -b "$COOKIE" -c "$COOKIE" -X POST \
  --data-urlencode "csrf_token=$TOKEN" \
  --data-urlencode "email=ci-admin@example.test" \
  --data-urlencode "password=Installer-CI-Password-123!" \
  "$BASE/admin/login.php")"
[[ "$CODE" == "302" ]]

curl -fsS "$BASE/admin/" -b "$COOKIE" | grep -q 'Universal CMS'
curl -fsS "$BASE/api/v1/health.php" | grep -q '"status":"UP"'
curl -fsS "$BASE/api/v1/" | grep -q '"api_version":"v1"'

echo "HTTP INSTALLER PASS"
