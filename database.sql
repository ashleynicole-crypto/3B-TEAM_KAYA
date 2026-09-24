CREATE DATABASE IF NOT EXISTS sun_son_solar
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sun_son_solar;

CREATE TABLE IF NOT EXISTS REGISTRATION (
  customer_id INT NOT NULL AUTO_INCREMENT,
  first_name VARCHAR(50) NOT NULL,
  middle_name VARCHAR(50) NULL,
  last_name VARCHAR(50) NOT NULL,
  birthdate DATE NOT NULL,
  gender VARCHAR(10) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone_number VARCHAR(20) NOT NULL,
  address VARCHAR(255) NOT NULL,
  username VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (customer_id),
  UNIQUE KEY uq_registration_email (email),
  UNIQUE KEY uq_registration_phone (phone_number),
  UNIQUE KEY uq_registration_username (username)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
