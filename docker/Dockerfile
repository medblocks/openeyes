FROM ubuntu:20.04

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

RUN git clone https://github.com/AppertaFoundation/openeyes.git /var/www/openeyes

RUN cd /var/www/openeyes \
    && git checkout release/v6.8.x \
    && npm install
RUN curl -sS https://raw.githubusercontent.com/composer/getcomposer.org/76a7060ccb93902cd7576b67264ad91c8a2700e2/web/installer | php -- --quiet
RUN mv composer.phar /usr/local/bin/composer

RUN cd /var/www/openeyes && composer install --no-interaction

RUN mkdir -p /var/www/openeyes/cache \
    && mkdir -p /var/www/openeyes/protected/cache \
    && mkdir -p /var/www/openeyes/assets \
    && chmod -R 755 /var/www/openeyes/assets

RUN apt install -y mariadb-client-core-10.*

# Install eyedraw
RUN cd /var/www/openeyes/protected \
    && git submodule init \
    && git submodule update
ENV NVM_DIR /root/.nvm
ENV NODE_VERSION 16.20.2

RUN git clone https://github.com/AppertaFoundation/eyedraw.git /var/www/openeyes/protected/modules/eyedraw \
    && cd /var/www/openeyes/protected/modules/eyedraw \
    && git checkout v6.7.19

RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.1/install.sh | bash \
    && . $NVM_DIR/nvm.sh \
    && nvm install $NODE_VERSION \
    && nvm alias default $NODE_VERSION \
    && nvm use default \
    && npm cache clean --force \
    && cd /var/www/openeyes/protected/modules/eyedraw \
    && npm install sortablejs

ENV NODE_PATH $NVM_DIR/v$NODE_VERSION/lib/node_modules
ENV PATH $NVM_DIR/versions/node/v$NODE_VERSION/bin:$PATH
RUN ls -la /var/www/openeyes/protected/config/core
RUN cp -Rv /var/www/openeyes/protected/config/core/* /var/www/openeyes/protected/config/local/
RUN ls -la /var/www/openeyes/protected/config/local
CMD ["/usr/sbin/apache2ctl -D FOREGROUND"]