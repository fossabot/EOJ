FROM ubuntu:20.04

RUN apt-get -y update  && \
    apt-get -y upgrade && \
    DEBIAN_FRONTEND=noninteractive \
    apt-get -y install --no-install-recommends \
        nginx \
        mariadb-server \
        mariadb-client \
        memcached \
        libmariadb-dev \
        php-common \
        php-fpm \
        php-mysql \
        php-memcached \
        php-gd \
        php-zip \
        php-mbstring \
        php-xml \
        make \
        flex \
        gcc \
        g++ \
        openjdk-11-jdk

COPY . /trunk/

COPY docker/ /opt/docker/

RUN bash /opt/docker/setup.sh

# VOLUME [ "/volume", "/home/judge/backup", "/home/judge/data", "/home/judge/etc", "/home/judge/web", "/var/lib/mysql" ]
VOLUME [ "/volume" ]

ENTRYPOINT [ "/bin/bash", "/opt/docker/entrypoint.sh" ]
