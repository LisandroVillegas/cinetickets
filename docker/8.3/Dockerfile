FROM node:22-bookworm-slim AS node
FROM composer:2 AS composer
FROM ubuntu:24.04

ARG WWWGROUP=1000
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=America/Bogota
ENV SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_USER="sail"

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates curl git gosu supervisor unzip zip libcap2-bin \
        php8.3-cli php8.3-bcmath php8.3-curl php8.3-intl php8.3-mbstring \
        php8.3-mysql php8.3-sqlite3 php8.3-xml php8.3-zip mysql-client \
    && setcap 'cap_net_bind_service=+ep' /usr/bin/php8.3 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

RUN userdel -r ubuntu \
    && groupadd --force -g "$WWWGROUP" sail \
    && useradd -ms /bin/bash --no-user-group -g "$WWWGROUP" -u 1337 sail \
    && git config --system --add safe.directory /var/www/html

# Published Sail entrypoint and process configuration.
COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.3/cli/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container

EXPOSE 80
ENTRYPOINT ["start-container"]
