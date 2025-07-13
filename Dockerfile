# Usar imagem oficial do PHP 8.4 com Apache
FROM php:8.4-apache

# Definir diretório de trabalho
WORKDIR /var/www/html

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    unzip \
    curl \
    git \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo_mysql \
    zip \
    gd \
    intl \
    xml \
    curl \
    mbstring

# Habilitar módulos Apache
RUN a2enmod rewrite ssl

# Configurar PHP
RUN { \
    echo 'upload_max_filesize = 50M'; \
    echo 'post_max_size = 50M'; \
    echo 'max_execution_time = 300'; \
    echo 'memory_limit = 256M'; \
    echo 'date.timezone = America/Sao_Paulo'; \
} > /usr/local/etc/php/conf.d/sisreserva.ini

# Copiar arquivos do projeto
COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads /var/www/html/logs

# Definir permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/uploads \
    && chmod -R 777 /var/www/html/logs

# Configurar Apache para aceitar .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Expor porta 80
EXPOSE 80

# Comando padrão
CMD ["apache2-foreground"] 