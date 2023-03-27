# PHP Food CMS Web Application
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
$ git clone https://github.com/Milosz08/food-delivery-cms
```

<a name="prepare-runtime-configuration-for-unix"></a>
## Prepare runtime configuration for UNIX
0. If you don't have homebrew, install via:
```
$ /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```
1. Downlad and install PHP, PHP composer, UFW and PhpMyAdmin:
```
$ brew install php
$ brew install composer
$ sudo apt-get install ufw
$ sudo apt install phpmyadmin php-mbstring php-zip php-gd php-json php-curl
```
2. Download, install and turn on Apache WebServer (for Ubuntu and Debian):
```
$ sudo apt-get install apache2   # install apache webserver
$ sudo systemctl start apache2   # start webserver
$ sudo ufw enable                # enable UFW
$ sudo ufw allow 8080/tcp        # add port 8080 to the list
```
3. Configure MySQL database:
```
$ sudo mysql
mysql> ALTER USER 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY 'yourPassword';
```
3. Move all files from cloned repo into `/var/www/html` directory.
4. Go to the project path in shell and install all dependencies via:
```
$ php composer install
```
5. Create `.env` in project root directory and fill with propriet values:
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
6. Before you will run application, migrate `m1428_si_proj.sql` file into `127.0.0.1:8080/phpmyadmin`.
7. Congrats, your app will be available on `127.0.0.1:8080`.

<a name="prepare-runtime-configuration-for-windows"></a>
## Prepare runtime configuration for Windows
1. Download and install XAMPP [from here](https://www.apachefriends.org/)
2. Download and install PHP Composer [from here](https://getcomposer.org/Composer-Setup.exe)
3. Add to path variable path to your PHP pre-installed directory (for the most common installations, path will be `C:\xampp\php`)
4. Move your cloned project into `/xampp/htdocs` location.
5. Do 4, 5 and 6 points from installation for UNIX.
6. Congrats, your app will be available on `127.0.0.1:8080`.

<a name="application-stack"></a>
## Application stack
* [PHP](https://www.php.net/)
* [Mustache Template Engine](https://github.com/bobthecow/mustache.php)
* [PHP Mailer](https://github.com/PHPMailer/PHPMailer)
* [Bootstrap](https://getbootstrap.com/)

<a name="project-status"></a>
## Project status
Project is finished.
