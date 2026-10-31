# Development image for the Laravel app and queue worker.
# Ubuntu 26.04 LTS ships PHP 8.5; Node.js 26 comes from NodeSource.
FROM ubuntu:26.04

ARG UID=1000
ARG GID=1000
ARG NODE_MAJOR=26

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_HOME=/home/ubuntu/.composer \
    COMPOSER_ALLOW_SUPERUSER=0

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates curl git unzip gnupg \
        php8.5-cli php8.5-pgsql php8.5-sqlite3 php8.5-mbstring php8.5-xml \
        php8.5-curl php8.5-zip php8.5-intl php8.5-bcmath \
        postgresql-client \
    && curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Match the host user so files written through the bind mount keep the right owner.
RUN groupmod -o -g "${GID}" ubuntu \
    && usermod -o -u "${UID}" -g "${GID}" ubuntu \
    && mkdir -p /var/www/html "${COMPOSER_HOME}" \
    && chown -R ubuntu:ubuntu /var/www/html /home/ubuntu

WORKDIR /var/www/html
USER ubuntu

EXPOSE 8000 5173

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
