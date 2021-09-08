FROM bitnami/wordpress:5

# Required to perform privileged actions
USER 0

# install apt packages
RUN install_packages php-pear autoconf build-essential lsyncd subversion vim
# Use development config
ENV PHP_DEV_CONF_FILE "/opt/bitnami/php/etc/php.ini-development"
RUN ln -sf "$PHP_DEV_CONF_FILE" /opt/bitnami/php/lib/php.ini
RUN pear config-set php_ini /opt/bitnami/php/lib/php.ini
# install xdebug using pecl
RUN pecl install xdebug
# install redis using pecl
RUN pecl install redis
# allow dependency execution
ENV PATH /redis-cache/bin:/redis-cache/vendor/bin:$PATH

ADD ./app-entrypoint-custom.sh /
RUN chmod +x /app-entrypoint-custom.sh

# Revert to the original non-root user
USER 1001

RUN rm -r /opt/bitnami/wordpress/wp-content/plugins/*

ENTRYPOINT [ "/app-entrypoint-custom.sh" ]
CMD [ "/opt/bitnami/scripts/apache/run.sh" ]
