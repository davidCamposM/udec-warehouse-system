# Imagen de desarrollo: PHP 8.5 + Apache, lo más parecida posible al servidor institucional.
FROM php:8.5-apache

# php.ini de desarrollo: muestra todos los errores.
# Zona horaria y parámetros de sesión NO se fijan aquí a propósito: el servidor real puede no
# tenerlos, así que la aplicación los configura por sí misma.
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Extensión para MySQL vía PDO (mbstring ya viene incluida en la imagen oficial)
RUN docker-php-ext-install pdo_mysql

# mod_rewrite para URLs amigables (url_mode = rewrite)
RUN a2enmod rewrite \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

# Apache sirve public/ y respeta .htaccess
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Apache corre con el UID/GID del usuario del host, para que pueda escribir en storage/
# y los archivos creados desde el contenedor no queden con otro dueño.
ARG UID=1000
ARG GID=1000
RUN groupmod -g "${GID}" www-data && usermod -u "${UID}" -g "${GID}" www-data

WORKDIR /var/www/html
