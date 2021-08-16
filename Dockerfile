FROM php:8.0

EXPOSE 80

RUN apt update \
    && apt install -y git zip \
    && apt-get purge -y --auto-remove -o APT:::AutoREmove::RecommendsImportant=false \
    && rm -rf /tmp/pear /root/.pearrc


WORKDIR /var/www

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN curl -sS https://get.symfony.com/cli/installer | bash && \
  mv /root/.symfony/bin/symfony /usr/local/bin/symfony
RUN symfony check:requirements

COPY docker/entrypoint.sh /entrypoint.sh
COPY application /var/www

RUN composer install -n \
  && rm -rf /root/.composer

CMD ["symfony", "server:start", "--port=80", "--no-tls", "--allow-http"]
ENTRYPOINT ["/entrypoint.sh"]
