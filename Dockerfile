FROM php:8.2-apache

# Szükséges csomagok telepítése (pl. zip, git) a Composerhez
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Composer telepítése
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apache mod_rewrite bekapcsolása (bár itt most főleg statikus a frontend, de hasznos lehet)
RUN a2enmod rewrite

# Munkakönyvtár beállítása
WORKDIR /var/www/html

# Beállítjuk a jogosultságokat
RUN chown -R www-data:www-data /var/www/html
