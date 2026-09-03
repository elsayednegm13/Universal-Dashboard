#!/bin/sh
set -eu

mkdir -p /var/www/html/storage/backups /var/www/html/uploads
if [ ! -f /var/www/html/storage/.htaccess ] && [ -f /usr/local/share/universal-cms/storage.htaccess ]; then
  cp /usr/local/share/universal-cms/storage.htaccess /var/www/html/storage/.htaccess
fi
if [ ! -f /var/www/html/uploads/.htaccess ] && [ -f /usr/local/share/universal-cms/uploads.htaccess ]; then
  cp /usr/local/share/universal-cms/uploads.htaccess /var/www/html/uploads/.htaccess
fi
chown -R www-data:www-data /var/www/html/storage /var/www/html/uploads
chmod -R u+rwX,g+rwX,o-rwx /var/www/html/storage || true
chmod -R u+rwX,g+rwX,o+rX /var/www/html/uploads || true

exec docker-php-entrypoint "$@"
