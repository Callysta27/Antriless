# PHP 8.2 + Apache, pengganti Apache dan PHP di XAMPP
FROM php:8.2-apache

# Ekstensi mysqli dibutuhkan untuk koneksi ke MySQL
RUN docker-php-ext-install mysqli

# Salin kode aplikasi ke folder web Apache
COPY baru/admin/ /var/www/html/

# Folder upload foto menu harus bisa ditulis oleh Apache
RUN mkdir -p /var/www/html/dashboard_temp/uploads/menu \
    && chown -R www-data:www-data /var/www/html/dashboard_temp/uploads

# Halaman pertama yang dibuka saat mengakses website: login.php
RUN echo "DirectoryIndex login.php index.php index.html" > /etc/apache2/conf-enabled/antriless.conf