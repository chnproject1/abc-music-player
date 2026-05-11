FROM php:8.2-apache
 
# Habilitar mod_rewrite para URLs limpas
RUN a2enmod rewrite
 
# Copiar arquivos do app
COPY . /var/www/html/
 
# Permitir .htaccess
RUN echo '<Directory /var/www/html>\n\
    Options -Indexes\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/app.conf \
 && a2enconf app
 
EXPOSE 80
 

