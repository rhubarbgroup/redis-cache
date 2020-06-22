FROM bitnami/wordpress

ENV WORDPRESS_USERNAME="wordpress" \
    WORDPRESS_PASSWORD="wordpress" \
    WORDPRESS_BLOG_NAME="WP dev installation"

# Required to perform privileged actions
USER 0

RUN install_packages vim php-pear autoconf build-essential lsyncd \
    && pear config-set php_ini /opt/bitnami/php/lib/php.ini \
    && pecl install redis

ADD ./app-entrypoint-custom.sh /
RUN chmod +x /app-entrypoint-custom.sh

# Revert to the original non-root user
USER 1001

RUN rm -r /opt/bitnami/wordpress/wp-content/plugins/*

ENTRYPOINT [ "/app-entrypoint-custom.sh" ]
CMD [ "httpd", "-f", "/opt/bitnami/apache/conf/httpd.conf", "-DFOREGROUND" ]
