# syntax = docker/dockerfile:1.0.2-experimental
ARG SPRYKER_PHP_VERSION=7.4.16
FROM php:${SPRYKER_PHP_VERSION}-fpm-alpine3.13

ARG COMPOSER_VERSION=2.0.12
ARG BLACKFIRE_VERSION=1.57.0
ARG NEWRELIC_VERSION=9.16.0.295
ARG TIDEWAYS_VERSION=5.3.14

ARG PHP_RUN_DEPS="\
    icu-libs \
    libbz2 \
    libxslt \
    libpng \
    freetype \
    libxpm \
    libwebp \
    libxml2 \
    libjpeg-turbo \
    libzip \
    libc6-compat \
    gnu-libiconv \
    gmp"

ARG PHP_BUILD_DEPS="\
    postgresql-dev \
    libpng-dev \
    libwebp-dev \
    libjpeg-turbo-dev \
    libxpm-dev \
    libxml2-dev \
    freetype-dev \
    gmp-dev \
    icu-dev \
    bzip2-dev \
    libzip-dev \
    autoconf \
    g++ \
    make"

ARG PHP_EXTENSIONS="\
    gd \
    gmp \
    intl \
    pdo_pgsql \
    pdo_mysql \
    mysqli \
    pgsql \
    bcmath \
    bz2 \
    sockets \
    soap \
    pcntl \
    opcache \
    zip"

ARG PHP_PECL_EXTENSIONS="\
    apcu \
    redis"

ARG CFLAGS="-I/usr/src/php"

ENV srcRoot=/data \
# Related to https://github.com/docker-library/php/issues/240
    LD_PRELOAD=/usr/lib/preloadable_libiconv.so \
# Blackfire
    BLACKFIRE_AGENT_SOCKET='' \
# Tideways
    TIDEWAYS_APIKEY='' TIDEWAYS_DAEMON_URI='' TIDEWAYS_CLI_ENABLED=0 \
# New Relic
    NEWRELIC_ENABLED=0 NEWRELIC_LICENSE='' NEWRELIC_APPNAME='Spryker'

RUN mkdir -p ${srcRoot} \
    && apk update --no-cache \
    && apk add --no-cache \
    bash \
    curl \
#    git \
#    unzip \
    graphviz \
    netcat-openbsd \
    libpq \
#    mysql-client \
#    openssh \
#    postgresql-client \
    procps \
    shadow \
    coreutils \
    ${PHP_RUN_DEPS} \
    && \
    apk add --no-cache --virtual .php-build-deps ${PHP_BUILD_DEPS} \
    && rm -rf /var/lib/apt/lists/ \
    && \
    docker-php-ext-configure gd \
      --disable-gd-jis-conv \
      --with-freetype=/usr \
      --with-jpeg=/usr \
      --with-webp=/usr \
      --with-xpm=/usr \
    && docker-php-ext-install -j5 ${PHP_EXTENSIONS} \
    && \
    pecl install -o -f ${PHP_PECL_EXTENSIONS} xdebug \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable ${PHP_EXTENSIONS} ${PHP_PECL_EXTENSIONS} \
    && apk del --no-cache .php-build-deps \
# Remove default FPM pool
    && rm /usr/local/etc/php-fpm.d/www.conf \
    && rm /usr/local/etc/php-fpm.d/docker.conf \
    && rm /usr/local/etc/php-fpm.d/zz-docker.conf \
# Opcache
    && /usr/bin/install -d -m 777 /var/run/opcache \
# Install composer
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer --version=${COMPOSER_VERSION} \
# Create application user 'spryker'
    && addgroup spryker \
    && adduser -h /home/spryker -s /bin/sh -G www-data -D spryker \
    && rm /etc/group- /etc/passwd- /etc/shadow- \
    && chown spryker:spryker ${srcRoot} \
# Install hirak/prestissimo as spryker user
    && su - spryker -c 'mkdir -p /home/spryker/.composer' \
    && su - spryker -c 'bash -c '[[ $COMPOSER_VERSION == "1"* ]] &&  COMPOSER_MEMORY_LIMIT=-1 composer global require hirak/prestissimo && rm -rf /home/spryker/.composer/cache || true ' ' \
    && apk del --no-cache coreutils

# Blackfire
RUN version=$(php -r "echo PHP_MAJOR_VERSION . PHP_MINOR_VERSION;") \
    && curl -A "Docker" -o /tmp/blackfire.so -D - -L -s https://packages.blackfire.io/binaries/blackfire-php/${BLACKFIRE_VERSION}/blackfire-php-alpine_amd64-php-$version.so \
    && mv /tmp/blackfire.so $(php -r "echo ini_get ('extension_dir');")/blackfire.so

# Tideways
RUN version=$(php -r "echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;") \
    && wget https://s3-eu-west-1.amazonaws.com/tideways/extension/${TIDEWAYS_VERSION}/tideways-php-${TIDEWAYS_VERSION}-alpine.tar.gz \
    && tar xvzf tideways-php-${TIDEWAYS_VERSION}-alpine.tar.gz \
    && cp ./build/dist/tideways-php-alpine-${version}.so $(php-config --extension-dir)/tideways.so \
    && rm -rf ./build/ /var/www/html/tideways-php*

# New Relic
RUN curl -L "https://download.newrelic.com/php_agent/archive/${NEWRELIC_VERSION}/newrelic-php5-${NEWRELIC_VERSION}-linux-musl.tar.gz" | tar -C /tmp -zx \
  && export NR_INSTALL_USE_CP_NOT_LN=1 \
  && export NR_INSTALL_SILENT=1 \
  && /tmp/newrelic-php5-*/newrelic-install install \
  && rm -rf /tmp/newrelic-php5-* /tmp/nrinstall* \
  && /usr/bin/install -d -m 777 /var/log/newrelic/ \
  && rm /usr/local/etc/php/conf.d/newrelic.ini \
  && ln -s /lib64/ld-linux-x86-64.so.2 /lib/ld-linux-x86-64.so.2 \
  && rm -rf /var/log/newrelic /etc/newrelic/*

# Add FPM configs and php.ini configuration
ADD context /

WORKDIR /data
