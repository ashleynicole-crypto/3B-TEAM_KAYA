CREATE DATABASE IF NOT EXISTS sun_son_solar
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sun_son_solar;

CREATE TABLE IF NOT EXISTS employees (
    employee_id INT NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) NULL,
    last_name VARCHAR(50) NOT NULL,
    birthdate DATE NOT NULL,
    gender VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    address VARCHAR(255) NOT NULL,
    department VARCHAR(50) NOT NULL, -- Administration, IT, Dispatch, Accounting, HR, Marketing, Sales, Customer Service
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    PRIMARY KEY (employee_id),
    UNIQUE KEY uq_employee_email (email),
    UNIQUE KEY uq_employee_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT NOT NULL AUTO_INCREMENT,
    customer_id INT NOT NULL,
    service_type VARCHAR(100) NOT NULL, -- Consultation, Installation, Maintenance, Repair, etc.
    scheduled_time DATETIME NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending', -- Pending, Approved, Completed, Declined
    PRIMARY KEY (booking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS field_checkins (
    checkin_id INT NOT NULL AUTO_INCREMENT,
    technician_id INT NOT NULL,
    booking_id INT NOT NULL,
    checkin_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    PRIMARY KEY (checkin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;