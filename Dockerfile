FROM ubuntu:latest

MAINTAINER Berkhan Berkdemir <berkberkdemir@gmail.com>

LABEL title="Dockerfile for Bolt CMS"
LABEL version="1.00.0"
LABEL description="Powered by Ubuntu 16.04, PHP7 and Apache HTTP Web Server"
LABEL liscence="MIT"
LABEL status="Production"

# ARG about which do they use RDBMS? Take this answare with ARG

# localization
ENV LANG en_US.utf-8

# Versions
ENV PHP_VERSION 7.0.0
ENV BOLT_VERSION 3.3

# Paths
ENV WEB_ROOT /var/www/html
ENV PHP_INI /etc/php/7.0/cli/php.ini
ENV APACHE_CONF /etc/apache2/apache2.conf

# Software installation and update
RUN apt update -y && \
    apt upgrade -y && \
    apt install -yq git \
        curl \
        apache2 \
        php \
        php-mysql \
        php-sqlite3 \
        php-gd \
        php-curl \
        php-xml \
        php-zip \
        php-mbstring \
        php-intl \
        php-mcrypt && \
    rm -rf /var/lib/apt/lists/*

# Composer installation
RUN curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/local/bin --filename=composer

# Cloning
RUN cd /home && \
    git clone https://github.com/bolt/bolt.git && \
    cd bolt && \
    composer update && \
    rm -rf .github .git .gitignore .gitattributes \
         composer.json composer.lock \
         README.md LICENSE.md CONTRIBUTING.md \
         Dockerfile .travis.yml \
         tests /usr/local/bin/composer /var/www/html && \
    cd .. && \
    mv bolt /var/www/html && \
    chown www-data:www-data -R /var/www/html && \
    chmod 755 -R /var/www/html

# EDIT VIRTUALHOST OR MOVE TO /VAR/WWW/HTML FILES FROM BOLT FILE

EXPOSE 80
WORKDIR /var/www/html
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
