FROM php:8.3
ENV phpunit_version 9.6
ENV redis_version 6.0.8

RUN apt-get update && \
    apt-get install -y wget libssl-dev redis-tools bind9-dnsutils

RUN wget https://phar.phpunit.de/phpunit-${phpunit_version}.phar && \
    chmod +x phpunit-${phpunit_version}.phar && \
    mv phpunit-${phpunit_version}.phar /usr/local/bin/phpunit

# install php extension
RUN yes '' | pecl install -f redis && \
    docker-php-ext-enable redis

# install redis server
RUN wget http://download.redis.io/releases/redis-${redis_version}.tar.gz
RUN tar -xzf redis-${redis_version}.tar.gz
RUN BUILD_TLS=yes make -s -C redis-${redis_version} -j

CMD PATH=$PATH:/usr/local/bin/:/redis-${redis_version}/src/ && \
    cp -rp /src /tmp/app && \
    cd /tmp/app && \
    phpunit
