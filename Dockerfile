FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html

RUN mkdir -p /usr/local/share/universal-cms \
    && cp /var/www/html/storage/.htaccess /usr/local/share/universal-cms/storage.htaccess \
    && cp /var/www/html/uploads/.htaccess /usr/local/share/universal-cms/uploads.htaccess \
    && mkdir -p /var/www/html/storage/backups /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/uploads \
    && chmod +x /var/www/html/docker/entrypoint.sh

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
CMD ["apache2-foreground"]
EXPOSE 80
