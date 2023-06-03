# PHP Food Ordering Web Application (with CMS)
[![Generic badge](https://img.shields.io/badge/Made%20with-PHP%207.4-1abc9c.svg)](https://www.php.net/)&nbsp;&nbsp;
[![Generic badge](https://img.shields.io/badge/Package%20Manager-PHP%20Composer-green.svg)](https://getcomposer.org/)&nbsp;&nbsp;
<br><br>
A CMS (Content Management System) web application to support restaurant management, customer contact and product ordering from created restaurants. For obvious reasons, the application does not have a payment system.

See live demo on: [restaurants.miloszgilga.pl](https://restaurants.miloszgilga.pl/)

## Table of content
* [About the project](#about-the-project)
* [Clone and install](#clone-and-install)
* [Prepare runtime configuration for UNIX](#prepare-runtime-configuration-for-unix)
* [Prepare runtime configuration for Windows](#prepare-runtime-configuration-for-windows)
* [Application stack](#application-stack)
* [Project status](#project-status)

<a name="about-the-project"></a>
## About the project
This project was created with the cooperation of six people, one of whom was the main leader (Project Manager). The main core of the MVC application was written from scratch the most for of performance reasons. Application works with MySQL database version 7.4 and higher.<br><br>
This application allows to create a user and restaurant owner account. User can add new products from multiple restaurants to the cart and the owner (after the system administrator approves the created restaurant) can add, modify and remove dishes from the created restaurant.

<a name="clone-and-install"></a>
## Clone and install

To install the program on your computer, use the command below (or use the build-in GIT system in your IDE environment):
```
$ git clone https://github.com/Milosz08/food-ordering-web-app
```

<a name="prepare-runtime-configuration-for-unix"></a>
1. Configure Apache web server:
* download and install:
```
$ sudo apt update
$ sudo apt install apache2
```
* go to `httpd.conf` and modify settings:
```
$ sudo nano /etc/apache2/apache2.conf
```
```xml
<!-- /etc/apache2/apache2.conf -->
<Directory /var/www/>
   Options Indexes FollowSymLinks
   AllowOverride All <!-- change this line from None to All -->
   Require all granted
</Directory>
```
* move project files into `/var/www/html` via:
```
$ sudo cp [projectDir] /var/www/html
```
2. Configure PHP and PDO extension:
* download and install PHP, PHP MySQL driver and PHP composer:
```
$ sudo apt update
$ sudo apt install php7.4 php-mysql composer
```
* enable PDO:
```
$ sudo nano /etc/php/7.4/apache2/php.ini
```
```properties
# /etc/php/7.4/apache2/php.ini
;extension=pdo_firebird
extension=pdo_mysql # <-- uncomment this line
;extension=pdo_oci
```
3. Configure MySQL database:
* install, start and login into the MySQL database:
```
$ sudo apt update
$ sudo apt-get install mysql-server
$ sudo service mysql start
$ mysql -u root -p
```
* create new database `rest_db` and migrate data from `m1428_si_proj.sql` file:
```
mysql> CREATE DATABASE rest_db;
mysql> USE rest_db;
mysql> SOURCE [projectDir]/m1428_si_proj.sql;
```
4. Go to the project path and install all dependencies via:
```
$ php composer install
```
5. Create `.env` via this command (only for UNIX, for Windows create manually):
```
$ grep -vE '^\s*$|^#' .env.sample > .env
```
and fill with propriet values:
```properties
# database connection
DB_DSN          = 'mysql:host=[hostName];dbname=[dbName]'
DB_USERNAME     = '[databaseUsername]'
DB_PASSWORD     = '[databasePassword]'

# smtp mail server connection
SMTP_HOST       = '[smtpHost, ex. aws54.example.net]'
SMTP_USERNAME   = '[smtpResponsed, ex. noreply@example.net]'
SMTP_PASSWORD   = '[smtpPassword]'
SMTP_LOOPBACK   = '[smtpLoopbackResponder, ex. info@example.net]'
```
6. Run Apache server via:
```
$ sudo systemctl start apache2
```
8. Congrats, your app will be available on `http://localhost:80`.

<a name="prepare-runtime-configuration-for-windows"></a>
## Prepare runtime configuration for Windows
1. Download and install XAMPP [from here](https://www.apachefriends.org/)
2. Download and install PHP Composer [from here](https://getcomposer.org/Composer-Setup.exe)
3. Add to path variable path to your PHP pre-installed directory (for the most common installations, path will be `C:\xampp\php`)
4. Move your cloned project into `/xampp/htdocs` location.
5. Do 4 and 5 points from installation for UNIX.
6. Congrats, your app will be available on `127.0.0.1:80`.

<a name="application-stack"></a>
## Application stack
* PHP
* Mustache Template Engine
* PHP Mailer
* Bootstrap

<a name="project-status"></a>
## Project status
Project is finished.
