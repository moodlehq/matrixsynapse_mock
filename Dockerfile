FROM php:8.0-apache-bullseye

LABEL maintainer="Andrew Lyons <andrew@nicols.co.uk>" \
    org.opencontainers.image.source="https://github.com/andrewnicols/bigbluebutton_mock"

ARG TARGETPLATFORM
ENV TARGETPLATFORM=${TARGETPLATFORM:-linux/amd64}
RUN echo "Building for ${TARGETPLATFORM}"

EXPOSE 80

RUN apt-get update \
    && apt-get install -y zlib1g-dev g++ git libicu-dev zip libzip-dev gnupg apt-transport-https \
    && curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash \
    && apt-get update \
    && apt-get install -y symfony-cli \
    && apt-get purge -y --auto-remove -o APT:::AutoRemove::RecommendsImportant=false

WORKDIR /var/www

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN symfony check:requirements

COPY docker/entrypoint.sh /entrypoint.sh
COPY application /var/www

RUN composer install -n \
    && rm -rf /root/.composer

CMD ["symfony", "server:start", "--port=80", "--no-tls", "--allow-http"]
ENTRYPOINT ["/entrypoint.sh"]
