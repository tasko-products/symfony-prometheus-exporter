ARG PHP_VERSION=8.1

FROM php:${PHP_VERSION}-fpm-alpine AS php

RUN set -eux; \
	apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
	; \
	\
	runDeps="$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	)"; \
	apk add --no-cache --virtual .api-phpexts-rundeps $runDeps; \
	\
	apk del .build-deps

COPY www.conf /usr/local/etc/php-fpm.d/