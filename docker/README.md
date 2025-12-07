# Food ordering web app (with CMS)

[[GitHub repository](https://github.com/milosz08/food-ordering-web-app)] |
[[About project](https://miloszgilga.pl/project/food-ordering-web-app)]

A CMS (Content Management System) web application to support restaurant management, customer contact and product ordering from created
restaurants. For obvious reasons, the application does not have a payment system. Build on top of my own bare-bone PHP MVC framework.

## Build image

```bash
docker build -t milosz08/food-ordering-web-app .
```

## Create container

* Using command:

```bash
docker run -d \
  --name food-ordering-web-app \
  -p 8080:8080 \
  -e DB_DSN=<database connection string, ex. mysql:host=?;dbname=?> \
  -e DB_USERNAME=<database username> \
  -e DB_PASSWORD=<database password> \
  -e SMTP_HOST=<SMTP server host> \
  -e SMTP_PORT=<SMTP server port> \
  -e SMTP_USERNAME=<SMTP server username> \
  -e SMTP_PASSWORD=<SMTP server password> \
  -e SMTP_SENDER=<SMTP sender address, ex. noreply@example.com> \
  -e SMTP_LOOPBACK=<SMTP reply address, ex. info@example.com> \
  -e SMTP_ENCRYPTION_TYPE=<takes 3 values, empty string - no encryption, tls and ssl> \
  milosz08/food-ordering-web-app:latest
```

* Using `docker-compose.yml` file:

```yaml
services:
  food-ordering-web-app:
    container_name: food-ordering-web-app
    image: milosz08/food-ordering-web-app:latest
    ports:
      - '8080:8080'
    environment:
      DB_DSN: <database connection string, ex. mysql:host=?;dbname=?>
      DB_USERNAME: <database username>
      DB_PASSWORD: <database password>
      SMTP_HOST: <SMTP server host>
      SMTP_PORT: <SMTP server port>
      SMTP_USERNAME: <SMTP server username>
      SMTP_PASSWORD: <SMTP server password>
      SMTP_SENDER: <SMTP sender address, ex. noreply@example.com>
      SMTP_LOOPBACK: <SMTP reply address, ex. info@example.com>
      SMTP_ENCRYPTION_TYPE: <takes 3 values, empty string - no encryption, tls and ssl>
    networks:
      - food-ordering-network

  # other containers...

networks:
  food-ordering-network:
    driver: bridge
```

## License

This project is licensed under the Apache 2.0 License.
