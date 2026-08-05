FROM ghcr.io/geekcodev/php:8.4-bookworm

ARG INSTALL_XDEBUG=false

RUN if [ "${INSTALL_XDEBUG}" = "true" ]; then \
      pecl install xdebug-3.4.7 && docker-php-ext-enable xdebug; \
    fi
