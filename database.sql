CREATE DATABASE IF NOT EXISTS sun_son_solar
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sun_son_solar;

-- 1. Customers Table
CREATE TABLE IF NOT EXISTS registration (
    customer_id INT NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) NULL,
    last_name VARCHAR(50) NOT NULL,
    birthdate DATE NOT NULL,
    gender VARCHAR(20) NOT NULL,
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

-- 2. Employees Table
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
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (employee_id),
    UNIQUE KEY uq_employee_email (email),
    UNIQUE KEY uq_employee_username (username)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 3. Bookings / Services Table
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT NOT NULL AUTO_INCREMENT,
    customer_id INT NOT NULL,
    service_type VARCHAR(100) NOT NULL, -- Consultation, Permitting, Installation, Maintenance, Repair, Monitoring
    scheduled_time DATETIME NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending', -- Pending, Approved, Completed, Declined
    PRIMARY KEY (booking_id),
    FOREIGN KEY (customer_id) REFERENCES registration(customer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 4. Field Check-ins Table
CREATE TABLE IF NOT EXISTS field_checkins (
    checkin_id INT NOT NULL AUTO_INCREMENT,
    technician_id INT NOT NULL,
    booking_id INT NOT NULL,
    checkin_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    PRIMARY KEY (checkin_id),
    FOREIGN KEY (technician_id) REFERENCES employees(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
