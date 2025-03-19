# Food ordering web app (with CMS)

[[Docker image](https://hub.docker.com/r/milosz08/food-ordering-web-app)] |
[[About project](https://miloszgilga.pl/project/food-ordering-web-app)]

A CMS (Content Management System) web application to support restaurant management, customer contact and product ordering from created
restaurants. For obvious reasons, the application does not have a payment system. Build on top of my own bare-bone PHP MVC framework.

## Table of content

* [About the project](#about-the-project)
* [Clone and install](#clone-and-install)
* [Run project with Docker](#run-project-with-docker)
* [Tech stack](#tech-stack)
* [License](#license)

## About the project

This project was created with the cooperation of six people, one of whom was the main leader (Project Manager). The main core of the MVC
application was written from scratch the most for of performance reasons. Application works with MySQL database version 8. and higher.

This application allows to create a user and restaurant owner account. User can add new products from multiple restaurants to the cart and
the owner (after the system administrator approves the created restaurant) can add, modify and remove dishes from the created restaurant.

## Clone and install

To install the program on your computer, use the command below (or use the build-in GIT system in your IDE environment):

```
$ git clone https://github.com/milosz08/food-ordering-web-app
```

## Run project with Docker

1. Make sure you have `docker` and `docker compose` on your machine (you don't need PHP or PHP composer in your system path).
2. Alternatively, change application ports (if some are not available or blocked on your machine) or MySQL database configuration in `.env`
   file:

```properties
# ports
FOOD_ORDERING_MYSQL_PORT=8560
FOOD_ORDERING_PHPMYADMIN_PORT=8561
FOOD_ORDERING_MAILHOG_SERVER_PORT=8562
FOOD_ORDERING_MAILHOG_UI_PORT=8563
FOOD_ORDERING_PHP_SERVER_PORT=8564
# mysql database
FOOD_ORDERING_MYSQL_DB_NAME=food-ordering-db
FOOD_ORDERING_MYSQL_PASSWORD=admin
```

3. To run all containers in daemonized mode, type:

```bash
$ docker compose up -d
```

This command should create 4 Docker containers:

| Container name           | Port(s)                                                      | Description                         |
|--------------------------|--------------------------------------------------------------|-------------------------------------|
| food-ordering-mysql-db   | [8560](http://localhost:8560)                                | MySQL database.                     |
| food-ordering-phpmyadmin | [8561](http://localhost:8561)                                | MySQL database client (optional).   |
| food-ordering-mailhog    | [8562](http://localhost:8562), [8563](http://localhost:8563) | Fake mail server.                   |
| food-ordering-app        | [8564](http://localhost:8564)                                | Apache server with PHP application. |

> [!NOTE]
> If you have already MySQL database client, you can omit creating `food-ordering-phpmyadmin` container. To omit, create only MySQL db
> container via: `$ docker compose up -d food-ordering-mysql-db food-ordering-mailhog food-ordering-app`.

If you have follow container logs in current terminal process, run without `-d` (daemon) flag.

In `food-ordering-app` project files was mounted, so when you run container and make some changes in project files, app will automatically
reload.

> [!TIP]
> If you want the browser tab to automatically refresh when you change a project file, install the *Live Server Web Extension* (available
> for most popular Chromium-based browsers).

Default login credentials for admin, user and owner accounts you will find in `.volumes/mysql/init/init.sql` file.

## Tech stack

* PHP,
* Mustache Template Engine,
* PHP Mailer,
* Bootstrap,
* Docker containers.

## License

This project is licensed under the Apache 2.0 License.
