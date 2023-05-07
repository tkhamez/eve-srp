# Creates an image for production


FROM php:8.2-apache-bullseye AS build

COPY eve-srp-*.tar.gz /var/www/eve-srp.tar.gz
RUN tar -xf /var/www/eve-srp.tar.gz -C /var/www


FROM php:8.2-apache-bullseye

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update && \
    apt-get install -y --no-install-recommends libpq5 libpq-dev libgmp-dev && \
    docker-php-ext-install pdo_mysql pdo_pgsql gmp opcache && \
    apt-get remove --purge -y libpq-dev libgmp-dev && \
    apt-get autoremove --purge -y && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" # /usr/local/etc/php
RUN echo 'expose_php = Off' > "$PHP_INI_DIR/conf.d/x-eve-srp.ini"

RUN a2enmod rewrite headers
RUN echo 'Header always set Strict-Transport-Security "max-age=31536000"' > /etc/apache2/conf-enabled/x-eve-srp.conf && \
    echo "Header always set Content-Security-Policy \"default-src 'none'; style-src 'self'; script-src 'self'; font-src 'self'; img-src 'self' data: https://images.evetech.net; connect-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'; sandbox allow-downloads allow-forms allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts allow-top-navigation;\"" >> /etc/apache2/conf-enabled/x-eve-srp.conf && \
    echo 'Header always set X-Frame-Options "DENY"'                      >> /etc/apache2/conf-enabled/x-eve-srp.conf && \
    echo 'Header always set X-Content-Type-Options "nosniff"'            >> /etc/apache2/conf-enabled/x-eve-srp.conf && \
    echo 'ServerSignature Off'                                           >> /etc/apache2/conf-enabled/x-eve-srp.conf && \
    echo 'ServerTokens Prod'                                             >> /etc/apache2/conf-enabled/x-eve-srp.conf && \
    echo 'AddOutputFilterByType DEFLATE text/plain text/html font/ttf image/svg+xml image/x-icon' >> /etc/apache2/conf-enabled/x-eve-srp.conf

RUN ln -s /var/www/html /var/www/web
COPY --from=build /var/www/eve-srp/bin       /var/www/bin
COPY --from=build /var/www/eve-srp/config    /var/www/config
COPY --from=build /var/www/eve-srp/src       /var/www/src
COPY --from=build /var/www/eve-srp/storage   /var/www/storage
COPY --from=build /var/www/eve-srp/templates /var/www/templates
COPY --from=build /var/www/eve-srp/vendor    /var/www/vendor
COPY --from=build /var/www/eve-srp/web       /var/www/web
RUN chown www-data /var/www/storage
