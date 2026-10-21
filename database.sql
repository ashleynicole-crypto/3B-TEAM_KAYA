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
  UNIQUE KEY uq_registration_username (username)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @drop_registration_phone_index = IF(
  (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='REGISTRATION' AND index_name='uq_registration_phone') > 0,
  'ALTER TABLE REGISTRATION DROP INDEX uq_registration_phone', 'SELECT 1'
);
PREPARE drop_registration_phone_stmt FROM @drop_registration_phone_index;
EXECUTE drop_registration_phone_stmt;
DEALLOCATE PREPARE drop_registration_phone_stmt;

-- Employee accounts are provisioned by HR or an administrator; no default
-- account or plaintext password is inserted by this schema.
CREATE TABLE IF NOT EXISTS DEPARTMENT (
  department_id INT NOT NULL AUTO_INCREMENT,
  department_name VARCHAR(80) NOT NULL,
  PRIMARY KEY (department_id),
  UNIQUE KEY uq_department_name (department_name)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT IGNORE INTO DEPARTMENT (department_name) VALUES
 ('Administration'), ('IT'), ('Dispatch'), ('Accounting'), ('HR'),
 ('Marketing'), ('Sales'), ('Customer Service');

CREATE TABLE IF NOT EXISTS EMPLOYEE (
  employee_id INT NOT NULL AUTO_INCREMENT,
  first_name VARCHAR(50) NOT NULL,
  middle_name VARCHAR(50) NULL,
  last_name VARCHAR(50) NOT NULL,
  birthdate DATE NOT NULL,
  gender VARCHAR(20) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone_number VARCHAR(20) NOT NULL,
  address VARCHAR(255) NOT NULL,
  username VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL COMMENT 'Store password_hash() output only',
  department_id INT NOT NULL,
  role ENUM('admin','hr','dispatcher','technician','employee') NOT NULL DEFAULT 'employee',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (employee_id),
  UNIQUE KEY uq_employee_email (email),
  UNIQUE KEY uq_employee_username (username),
  CONSTRAINT fk_employee_department FOREIGN KEY (department_id) REFERENCES DEPARTMENT(department_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @drop_employee_phone_index = IF(
  (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='EMPLOYEE' AND index_name='uq_employee_phone') > 0,
  'ALTER TABLE EMPLOYEE DROP INDEX uq_employee_phone', 'SELECT 1'
);
PREPARE drop_employee_phone_stmt FROM @drop_employee_phone_index;
EXECUTE drop_employee_phone_stmt;
DEALLOCATE PREPARE drop_employee_phone_stmt;

CREATE TABLE IF NOT EXISTS SERVICE_CATEGORY (
  service_id INT NOT NULL AUTO_INCREMENT,
  service_name VARCHAR(100) NOT NULL,
  description TEXT NULL,
  base_price DECIMAL(10,2) NULL,
  duration_minutes INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (service_id),
  UNIQUE KEY uq_service_name (service_name)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT IGNORE INTO SERVICE_CATEGORY (service_name, description) VALUES
 ('Consultation','Solar energy consultation'), ('System design','Solar system design'),
 ('Permitting','Permitting support'), ('Installation','Solar system installation'),
 ('Maintenance','Scheduled system maintenance'), ('Repair','Solar system repair'),
 ('Monitoring','System performance monitoring');

CREATE TABLE IF NOT EXISTS PRODUCT (
  product_id INT NOT NULL AUTO_INCREMENT,
  product_name VARCHAR(120) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (product_id),
  UNIQUE KEY uq_product_name (product_name)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT IGNORE INTO PRODUCT (product_name, description) VALUES
 ('Solar panels','Solar modules for residential and commercial systems'),
 ('Inverters','Inverters for converting solar power for practical use'),
 ('Batteries','Energy storage options for solar installations'),
 ('Racking and mounting','Mounting equipment for solar panels'),
 ('Wires','Solar-rated electrical wiring and accessories');

CREATE TABLE IF NOT EXISTS SERVICE_BOOKING (
  booking_id BIGINT NOT NULL AUTO_INCREMENT,
  customer_id INT NOT NULL,
  service_id INT NOT NULL,
  assigned_technician_id INT NULL,
  scheduled_at DATETIME NOT NULL,
  status ENUM('requested','approved','declined','scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'requested',
  customer_notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (booking_id),
  KEY ix_booking_schedule (scheduled_at, status),
  CONSTRAINT fk_booking_customer FOREIGN KEY (customer_id) REFERENCES REGISTRATION(customer_id),
  CONSTRAINT fk_booking_service FOREIGN KEY (service_id) REFERENCES SERVICE_CATEGORY(service_id),
  CONSTRAINT fk_booking_technician FOREIGN KEY (assigned_technician_id) REFERENCES EMPLOYEE(employee_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS TECHNICIAN_CHECKIN (
  checkin_id BIGINT NOT NULL AUTO_INCREMENT,
  employee_id INT NOT NULL,
  booking_id BIGINT NULL,
  checked_in_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  PRIMARY KEY (checkin_id),
  KEY ix_checkin_employee_date (employee_id, checked_in_at),
  CONSTRAINT fk_checkin_employee FOREIGN KEY (employee_id) REFERENCES EMPLOYEE(employee_id),
  CONSTRAINT fk_checkin_booking FOREIGN KEY (booking_id) REFERENCES SERVICE_BOOKING(booking_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ANNOUNCEMENT (
  announcement_id BIGINT NOT NULL AUTO_INCREMENT,
  created_by INT NULL,
  audience ENUM('customers','employees','all') NOT NULL DEFAULT 'all',
  title VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  publish_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NULL,
  PRIMARY KEY (announcement_id),
  CONSTRAINT fk_announcement_author FOREIGN KEY (created_by) REFERENCES EMPLOYEE(employee_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS SERVICE_REPORT (
  report_id BIGINT NOT NULL AUTO_INCREMENT,
  booking_id BIGINT NOT NULL,
  employee_id INT NOT NULL,
  report_type ENUM('issue','equipment','customer_concern','other') NOT NULL DEFAULT 'other',
  details TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (report_id),
  CONSTRAINT fk_report_booking FOREIGN KEY (booking_id) REFERENCES SERVICE_BOOKING(booking_id),
  CONSTRAINT fk_report_employee FOREIGN KEY (employee_id) REFERENCES EMPLOYEE(employee_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS PAYMENT (
  payment_id BIGINT NOT NULL AUTO_INCREMENT,
  booking_id BIGINT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_method VARCHAR(50) NULL,
  reference_number VARCHAR(100) NULL,
  status ENUM('pending','paid','refunded','failed') NOT NULL DEFAULT 'pending',
  paid_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (payment_id),
  CONSTRAINT fk_payment_booking FOREIGN KEY (booking_id) REFERENCES SERVICE_BOOKING(booking_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS CUSTOMER_NOTIFICATION (
  notification_id BIGINT NOT NULL AUTO_INCREMENT,
  customer_id INT NOT NULL,
  title VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  notification_type ENUM('reminder','schedule','promotion','system') NOT NULL DEFAULT 'system',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (notification_id),
  KEY ix_customer_notification (customer_id, is_read, created_at),
  CONSTRAINT fk_notification_customer FOREIGN KEY (customer_id) REFERENCES REGISTRATION(customer_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS SUPPORT_TICKET (
  ticket_id BIGINT NOT NULL AUTO_INCREMENT,
  customer_id INT NOT NULL,
  subject VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (ticket_id),
  CONSTRAINT fk_ticket_customer FOREIGN KEY (customer_id) REFERENCES REGISTRATION(customer_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS SERVICE_AVAILABILITY (
  availability_id INT NOT NULL AUTO_INCREMENT,
  service_id INT NOT NULL,
  weekday TINYINT UNSIGNED NOT NULL COMMENT '0 Sunday through 6 Saturday',
  starts_at TIME NOT NULL,
  ends_at TIME NOT NULL,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (availability_id),
  UNIQUE KEY uq_service_weekday (service_id, weekday),
  CONSTRAINT fk_availability_service FOREIGN KEY (service_id) REFERENCES SERVICE_CATEGORY(service_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS SYSTEM_SETTING (
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Keep this idempotent upgrade statement when re-importing into an earlier
-- database created before the HR role was added.
ALTER TABLE EMPLOYEE MODIFY role ENUM('admin','hr','dispatcher','technician','employee') NOT NULL DEFAULT 'employee';
