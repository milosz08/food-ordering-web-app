CREATE TABLE IF NOT EXISTS delivery_types (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  name VARCHAR(50) NOT NULL UNIQUE,

  PRIMARY KEY (id)
)
ENGINE=InnoDB;

INSERT INTO delivery_types(id, name)
VALUES (1, 'Odbiór osobisty'),
       (2, 'Dostawa kurierem');


CREATE TABLE IF NOT EXISTS weekdays (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  name VARCHAR(20) NOT NULL UNIQUE,
  name_eng VARCHAR(9) NOT NULL UNIQUE,
  alias VARCHAR(3) NOT NULL UNIQUE,

  PRIMARY KEY (id)
)
ENGINE=InnoDB;

INSERT INTO weekdays(id, name, name_eng, alias)
VALUES (1, 'poniedziałek', 'monday', 'pn'),
       (2, 'wtorek', 'tuesday', 'wt'),
       (3, 'środa', 'wednesday', 'śr'),
       (4, 'czwartek', 'thursday', 'czw'),
       (5, 'piątek', 'friday', 'pt'),
       (6, 'sobota', 'saturday', 'sb'),
       (7, 'niedziela', 'sunday', 'ndz');


CREATE TABLE IF NOT EXISTS roles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  name VARCHAR(30) NOT NULL UNIQUE,
  role_eng VARCHAR(5) NOT NULL UNIQUE,

  PRIMARY KEY (id)
)
ENGINE=InnoDB;

INSERT INTO roles(id, name, role_eng)
VALUES (1, 'klient', 'user'),
       (2, 'właściciel', 'owner'),
       (3, 'administrator', 'admin');


CREATE TABLE IF NOT EXISTS order_statuses (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  name VARCHAR(30) NOT NULL UNIQUE,

  PRIMARY KEY (id)
)
ENGINE=InnoDB;

INSERT INTO order_statuses(id, name)
VALUES (1, 'W trakcie realizacji'),
       (2, 'Gotowe'),
       (3, 'Anulowane');


CREATE TABLE IF NOT EXISTS ota_token_types (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  type VARCHAR(20) NOT NULL UNIQUE,

  PRIMARY KEY (id)
)
ENGINE=InnoDB;

INSERT INTO ota_token_types(id, type)
VALUES (1, 'change password'),
       (2, 'activate account');


CREATE TABLE IF NOT EXISTS notifs_grade_delete_types (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  name VARCHAR(100) NOT NULL UNIQUE,

  PRIMARY KEY (id)
)
ENGINE=InnoDB;

INSERT INTO notifs_grade_delete_types(id, name)
VALUES (4, 'Inny powód'),
       (1, 'Opinia okazała się nieadekwatna'),
       (3, 'Opinia w inny sposób niż powyższe łamie regulamin '),
       (2, 'Opinia zawiera niecenzuralny kontekst wypowiedzi');


CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  first_name VARCHAR(50) NOT NULL,
  last_name VARCHAR(50) NOT NULL,
  login VARCHAR(30) NOT NULL UNIQUE,
  password CHAR(72) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  phone_number VARCHAR(9) NOT NULL UNIQUE,
  photo_url VARCHAR(500),
  is_activated BOOL NOT NULL DEFAULT false,
  role_id BIGINT UNSIGNED,

  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  PRIMARY KEY (id)
)
ENGINE=InnoDB;

INSERT INTO users(id, first_name, last_name, login, password, email, phone_number, role_id, is_activated)
VALUES (1, 'User', 'Test', 'user123', '$2y$10$50xuXULao/W6HcXATy7Dqe6Z6AYtCJBqJyb7cCLB/mCzmZq6HLcse', 'user123@food-ordering.com', '123456789', 1, 1),
       (2, 'Owner', 'Test', 'owner123', '$2y$10$50xuXULao/W6HcXATy7Dqe6Z6AYtCJBqJyb7cCLB/mCzmZq6HLcse','owner123@food-ordering.com', '549812130', 2, 1),
       (3, 'Admin', 'Test', 'admin123', '$2y$10$50xuXULao/W6HcXATy7Dqe6Z6AYtCJBqJyb7cCLB/mCzmZq6HLcse', 'admin123@food-ordering.com', '289999999', 3, 1);


CREATE TABLE IF NOT EXISTS user_addresses (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  street VARCHAR(100) NOT NULL,
  building_nr VARCHAR(5) NOT NULL,
  locale_nr VARCHAR(5) DEFAULT NULL,
  post_code VARCHAR(6) NOT NULL,
  city VARCHAR(60) NOT NULL,
  is_prime BOOL NOT NULL DEFAULT false,
  user_id BIGINT UNSIGNED,

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  PRIMARY KEY (id)
)
ENGINE=InnoDB;

INSERT INTO user_addresses(id, street, building_nr, locale_nr, post_code, city, user_id, is_prime)
VALUES (1, 'Bolesława Krzywoustego', '2b', NULL, '43-410', 'Katowice', 1, b'1'),
       (2, 'Akademicka', '10', '42', '42-289', 'Gliwice', 2, b'1'),
       (3, 'Łużycka', '9a', '42', '44-222', 'Gliwice', 3, b'1');


CREATE TABLE IF NOT EXISTS ota_user_tokens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  ota_token VARCHAR(10) NOT NULL UNIQUE,
  expiration_date DATETIME NOT NULL,
  is_used BOOL NOT NULL DEFAULT false,
  user_id BIGINT UNSIGNED,
  type_id BIGINT UNSIGNED,

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  FOREIGN KEY (type_id) REFERENCES ota_token_types(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  PRIMARY KEY (id)
)
ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS restaurants (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  name VARCHAR(50) NOT NULL,
  street VARCHAR(100) NOT NULL,
  building_locale_nr VARCHAR(10) NOT NULL,
  post_code VARCHAR(6) NOT NULL,
  city VARCHAR(60) NOT NULL,
  phone_number VARCHAR(9) NOT NULL,
  banner_url VARCHAR(1000),
  profile_url VARCHAR(1000),
  delivery_price DECIMAL(10, 2) UNSIGNED,
  min_price DECIMAL(10, 2) UNSIGNED,
  description VARCHAR(600) NOT NULL DEFAULT '',
  accept BOOL NOT NULL DEFAULT false,
  user_id BIGINT UNSIGNED,

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  PRIMARY KEY (id)
)
ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS restaurant_hours (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  open_hour TIME NOT NULL,
  close_hour TIME NOT NULL,
  weekday_id BIGINT UNSIGNED,
  restaurant_id BIGINT UNSIGNED,

  FOREIGN KEY (weekday_id) REFERENCES weekdays(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE ON UPDATE CASCADE,
  PRIMARY KEY (id)
)
ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS discounts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  code VARCHAR(20) NOT NULL,
  description VARCHAR(200),
  percentage_discount DECIMAL(10, 2) UNSIGNED NOT NULL,
  usages INTEGER UNSIGNED NOT NULL DEFAULT '0',
  max_usages INTEGER UNSIGNED NOT NULL,
  expired_date DATE,
  restaurant_id BIGINT UNSIGNED,

  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE ON UPDATE CASCADE,
  PRIMARY KEY (id)
)
ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS dish_types (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  name VARCHAR(50) NOT NULL,
  user_id BIGINT UNSIGNED,

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  PRIMARY KEY (id)
)
ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS dishes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  name VARCHAR(100) NOT NULL,
  description VARCHAR(200),
  photo_url VARCHAR(500),
  price DECIMAL(10, 2) NOT NULL,
  prepared_time INTEGER UNSIGNED NOT NULL DEFAULT '5',
  dish_type_id BIGINT UNSIGNED,
  restaurant_id BIGINT UNSIGNED,

  FOREIGN KEY (dish_type_id) REFERENCES dishes(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE ON UPDATE CASCADE,
  PRIMARY KEY (id)
)
ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS orders (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  price DECIMAL(10, 2) NOT NULL,
  estimate_time TIME NOT NULL,
  date_order DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finish_order DATETIME,
  user_id BIGINT UNSIGNED,
  order_address_id BIGINT UNSIGNED,
  discount_id BIGINT UNSIGNED,
  restaurant_id BIGINT UNSIGNED,
  status_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
  delivery_type_id BIGINT UNSIGNED NOT NULL,

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE SET NULL,
  FOREIGN KEY (order_address_id) REFERENCES user_addresses(id) ON DELETE SET NULL ON UPDATE SET NULL,
  FOREIGN KEY (discount_id) REFERENCES discounts(id) ON DELETE SET NULL ON UPDATE SET NULL,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL ON UPDATE SET NULL,
  FOREIGN KEY (status_id) REFERENCES order_statuses(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  FOREIGN KEY (delivery_type_id) REFERENCES delivery_types(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
  PRIMARY KEY (id)
)
ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS restaurants_grades (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  restaurant_grade TINYINT UNSIGNED NOT NULL DEFAULT 5,
  delivery_grade TINYINT UNSIGNED NOT NULL DEFAULT 5,
  description VARCHAR(200),
  give_on DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  anonymously BOOL NOT NULL DEFAULT false,
  pending_to_delete BOOL NOT NULL DEFAULT false,
  order_id BIGINT UNSIGNED UNIQUE,

  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL ON UPDATE SET NULL,
  PRIMARY KEY (id)
)
ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS notifs_grades_to_delete (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

  description VARCHAR(350),
  send_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  type_id BIGINT UNSIGNED,
  grade_id BIGINT UNSIGNED,

  FOREIGN KEY (type_id) REFERENCES notifs_grade_delete_types(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (grade_id) REFERENCES restaurants_grades(id) ON DELETE CASCADE ON UPDATE CASCADE,
  PRIMARY KEY (id)
)
ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS orders_with_dishes (
  order_id bigint UNSIGNED,
  dish_id  bigint UNSIGNED,

  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE CASCADE ON UPDATE CASCADE
)
ENGINE=InnoDB;


CREATE EVENT IF NOT EXISTS remove_not_activated_user_scheduler
  ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL 12 HOUR
  DO
  DELETE FROM users WHERE is_activated = false;

CREATE EVENT IF NOT EXISTS remove_expired_ota_token_scheduler
  ON SCHEDULE AT CURRENT_TIMESTAMP + INTERVAL 12 HOUR
  DO
  DELETE FROM ota_user_tokens WHERE expiration_date < NOW() AND is_used = false;
