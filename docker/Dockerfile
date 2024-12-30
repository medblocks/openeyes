FROM ubuntu:20.04

ENV OE_MODE=BUILD
ENV DOCKER_CONTAINER=TRUE
ENV NODE_VERSION 16.20.2
RUN apt update
RUN  apt -y install software-properties-common \
    && add-apt-repository "ppa:ondrej/php" -y \
    && apt update -y \
    && apt install -y php7.4

RUN apt install -y \
    curl \
    git \
    php7.4-curl \
    php7.4-mysql \
    php7.4-xml \
    php7.4-gd \
    php7.4-mbstring \
    php7.4-zip \
    php7.4-gd \
    phpunit \
    libnss3 \
    libxss1 \
    libasound2 \
    php7.4-soap \
    php7.4-imagick
RUN  apt install -y nodejs unzip
RUN apt-get install -y npm nodejs
RUN apt install apache2 -y
RUN apt install -y mariadb-client-core-10.*
RUN curl -sS https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer | php -- --quiet
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.1/install.sh | bash \
    && . $NVM_DIR/nvm.sh \
    && nvm install $NODE_VERSION \
    && nvm alias default $NODE_VERSION \
    && nvm use default \
    && npm cache clean --force

COPY . /var/www/openeyes
RUN ls -la /var/www/openeyes
RUN cd /var/www/openeyes \
    && npm install

RUN mv composer.phar /usr/local/bin/composer

RUN cd /var/www/openeyes && composer install --no-interaction

RUN mkdir -p /var/www/openeyes/cache \
    && mkdir -p /var/www/openeyes/protected/cache \
    && mkdir -p /var/www/openeyes/assets \
    && chmod -R 755 /var/www/openeyes/assets

# Install eyedraw
RUN cd /var/www/openeyes/protected \
    && git submodule init \
    && git submodule update
ENV NVM_DIR /root/.nvm


RUN git clone https://github.com/AppertaFoundation/eyedraw.git /var/www/openeyes/protected/modules/eyedraw \
    && cd /var/www/openeyes/protected/modules/eyedraw \
    && git checkout v6.7.19


RUN  cd /var/www/openeyes/protected/modules/eyedraw \
    && npm install sortablejs

ENV NODE_PATH $NVM_DIR/v$NODE_VERSION/lib/node_modules
ENV PATH $NVM_DIR/versions/node/v$NODE_VERSION/bin:$PATH
RUN ls -la /var/www/openeyes/protected/config/core
RUN cp -Rv /var/www/openeyes/protected/config/core/* /var/www/openeyes/protected/config/local/
RUN ls -la /var/www/openeyes/protected/config/local

RUN mkdir -p /var/www/openeyes/cache
RUN mkdir -p /var/www/openeyes/assets
RUN mkdir -p /var/www/openeyes/protected/cache
RUN mkdir -p /var/www/openeyes/protected/cache/events
RUN mkdir -p /var/www/openeyes/protected/files
RUN mkdir -p /var/www/openeyes/protected/runtime
RUN mkdir -p /var/www/openeyes/protected/runtime/cache

RUN chmod 777 /var/www/openeyes/cache
RUN chmod 777 /var/www/openeyes/assets
RUN chmod 777 /var/www/openeyes/protected/cache
RUN chmod 777 /var/www/openeyes/protected/cache/events
RUN chmod 777 /var/www/openeyes/protected/files
RUN chmod 777 /var/www/openeyes/protected/runtime
RUN chmod 777 /var/www/openeyes/protected/runtime/cache

RUN grep -q -e "umask 001" /etc/apache2/envvars || bash -c 'echo "umask 001" >> /etc/apache2/envvars'

RUN /var/www/openeyes/protected/scripts/oe-fix.sh --no-compile --no-clear --no-assets --no-migrate --no-dependencies --no-eyedraw
RUN /var/www/openeyes/protected/scripts/set-profile.sh
RUN /var/www/openeyes/protected/scripts/oe-which.sh

RUN a2enmod headers
RUN a2enmod rewrite
CMD ["/usr/sbin/apache2ctl -D FOREGROUND"]