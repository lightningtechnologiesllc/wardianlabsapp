FROM php:8.4-fpm AS base

ENV TIMEZONE Europe/Madrid

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        gnupg2 \
        gcc g++ make \
        zlib1g-dev \
        libxml2-dev \
        libzip-dev \
        libpq-dev \
        libpng-dev \
        unzip \
        libicu-dev \
    && docker-php-ext-install \
        zip \
        intl \
        pgsql \
        pdo_pgsql \
        gd \
        opcache \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql

# Node 22 installation
RUN curl -sL https://deb.nodesource.com/setup_22.x | bash -

# Yarn installation
RUN curl https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor -o /usr/share/keyrings/yarn-keyring.gpg
RUN echo "deb [signed-by=/usr/share/keyrings/yarn-keyring.gpg] https://dl.yarnpkg.com/debian/ stable main" > /etc/apt/sources.list.d/yarn.list

RUN apt-get update && apt-get install -y --no-install-recommends \
    nodejs \
    yarn

# Clean apt repositories
RUN apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini \
    && "date"

WORKDIR /app

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Configure PHP-FPM to listen on all interfaces (for container networking)
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

COPY .docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["php-fpm"]

EXPOSE 9000

FROM base AS dev

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN pecl install xdebug-3.4.5 \
	&& docker-php-ext-enable xdebug

COPY etc/infrastructure/dev/php/ "$PHP_INI_DIR/"

# Chromium and ChromeDriver
ENV PANTHER_NO_SANDBOX 1
ENV PANTHER_CHROME_ARGUMENTS='--disable-dev-shm-usage'

RUN apt-get update && \
    apt-get install -y chromium-driver


FROM base AS prod

COPY . /app

RUN mv "/app/.docker/php/prod/php.ini" "$PHP_INI_DIR/php.ini"

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Build code
RUN composer config --global process-timeout 2000
RUN composer install --no-dev --prefer-dist --no-scripts --no-interaction --no-progress --optimize-autoloader --classmap-authoritative

RUN cd /app \
     && yarn install \
     && yarn add pkg-up \
     && yarn run build
