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
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  promotional_opt_in TINYINT(1) NOT NULL DEFAULT 1,
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

SET @add_registration_promotional_opt_in = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='REGISTRATION' AND column_name='promotional_opt_in') = 0,
  'ALTER TABLE REGISTRATION ADD COLUMN promotional_opt_in TINYINT(1) NOT NULL DEFAULT 1', 'SELECT 1'
);
PREPARE add_registration_promotional_opt_in_stmt FROM @add_registration_promotional_opt_in;
EXECUTE add_registration_promotional_opt_in_stmt;
DEALLOCATE PREPARE add_registration_promotional_opt_in_stmt;

SET @add_registration_account_status = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='REGISTRATION' AND column_name='is_active') = 0,
  'ALTER TABLE REGISTRATION ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER password', 'SELECT 1'
);
PREPARE add_registration_account_status_stmt FROM @add_registration_account_status;
EXECUTE add_registration_account_status_stmt;
DEALLOCATE PREPARE add_registration_account_status_stmt;

SET @add_registration_password_flag = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='REGISTRATION' AND column_name='must_change_password') = 0,
  'ALTER TABLE REGISTRATION ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active', 'SELECT 1'
);
PREPARE add_registration_password_flag_stmt FROM @add_registration_password_flag;
EXECUTE add_registration_password_flag_stmt;
DEALLOCATE PREPARE add_registration_password_flag_stmt;

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
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
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

SET @add_employee_must_change_password = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='EMPLOYEE' AND column_name='must_change_password') = 0,
  'ALTER TABLE EMPLOYEE ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1'
);
PREPARE add_employee_must_change_password_stmt FROM @add_employee_must_change_password;
EXECUTE add_employee_must_change_password_stmt;
DEALLOCATE PREPARE add_employee_must_change_password_stmt;

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
  image_url VARCHAR(500) NULL,
  price DECIMAL(10,2) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (product_id),
  UNIQUE KEY uq_product_name (product_name)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @add_product_image_url = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='PRODUCT' AND column_name='image_url') = 0,
  'ALTER TABLE PRODUCT ADD COLUMN image_url VARCHAR(500) NULL AFTER description', 'SELECT 1'
);
PREPARE add_product_image_url_stmt FROM @add_product_image_url;
EXECUTE add_product_image_url_stmt;
DEALLOCATE PREPARE add_product_image_url_stmt;
SET @add_product_price = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='PRODUCT' AND column_name='price') = 0,
  'ALTER TABLE PRODUCT ADD COLUMN price DECIMAL(10,2) NULL AFTER image_url', 'SELECT 1'
);
PREPARE add_product_price_stmt FROM @add_product_price;
EXECUTE add_product_price_stmt;
DEALLOCATE PREPARE add_product_price_stmt;
SET @add_product_active = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='PRODUCT' AND column_name='is_active') = 0,
  'ALTER TABLE PRODUCT ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER price', 'SELECT 1'
);
PREPARE add_product_active_stmt FROM @add_product_active;
EXECUTE add_product_active_stmt;
DEALLOCATE PREPARE add_product_active_stmt;

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
  reschedule_requested_at DATETIME NULL,
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

SET @add_booking_reschedule = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='SERVICE_BOOKING' AND column_name='reschedule_requested_at') = 0,
  'ALTER TABLE SERVICE_BOOKING ADD COLUMN reschedule_requested_at DATETIME NULL AFTER scheduled_at', 'SELECT 1'
);
PREPARE add_booking_reschedule_stmt FROM @add_booking_reschedule;
EXECUTE add_booking_reschedule_stmt;
DEALLOCATE PREPARE add_booking_reschedule_stmt;

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
  announcement_type ENUM('update','promotion','system','tip') NOT NULL DEFAULT 'update',
  title VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  publish_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NULL,
  PRIMARY KEY (announcement_id),
  CONSTRAINT fk_announcement_author FOREIGN KEY (created_by) REFERENCES EMPLOYEE(employee_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @add_announcement_type = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='ANNOUNCEMENT' AND column_name='announcement_type') = 0,
  "ALTER TABLE ANNOUNCEMENT ADD COLUMN announcement_type ENUM('update','promotion','system','tip') NOT NULL DEFAULT 'update' AFTER audience", 'SELECT 1'
);
PREPARE add_announcement_type_stmt FROM @add_announcement_type;
EXECUTE add_announcement_type_stmt;
DEALLOCATE PREPARE add_announcement_type_stmt;

ALTER TABLE ANNOUNCEMENT MODIFY announcement_type ENUM('update','promotion','system','tip') NOT NULL DEFAULT 'update';

INSERT INTO ANNOUNCEMENT (created_by, audience, announcement_type, title, message)
SELECT NULL, 'customers', 'promotion', 'Free solar site assessments', 'Schedule a no-obligation visit. Our team will review your roof, energy use, and system sizing options to help you plan a solution for your budget.'
WHERE NOT EXISTS (SELECT 1 FROM ANNOUNCEMENT WHERE audience = 'customers' AND title = 'Free solar site assessments');

INSERT INTO ANNOUNCEMENT (created_by, audience, announcement_type, title, message)
SELECT NULL, 'customers', 'tip', 'How much can solar really save?', 'System size, sunlight, electricity use, and utility rates all affect potential savings. A site assessment can help estimate the right system size for your property.'
WHERE NOT EXISTS (SELECT 1 FROM ANNOUNCEMENT WHERE audience = 'customers' AND title = 'How much can solar really save?');

INSERT INTO ANNOUNCEMENT (created_by, audience, announcement_type, title, message)
SELECT NULL, 'customers', 'update', 'Keep your panels performing', 'Routine inspections can help identify dirt, shading, or equipment issues. Follow your equipment guidance and contact a qualified technician when you need service.'
WHERE NOT EXISTS (SELECT 1 FROM ANNOUNCEMENT WHERE audience = 'customers' AND title = 'Keep your panels performing');

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
  ticket_type ENUM('support','quote') NOT NULL DEFAULT 'support',
  subject VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  response TEXT NULL,
  assigned_employee_id INT NULL,
  responded_at DATETIME NULL,
  status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (ticket_id),
  CONSTRAINT fk_ticket_customer FOREIGN KEY (customer_id) REFERENCES REGISTRATION(customer_id),
  CONSTRAINT fk_ticket_employee FOREIGN KEY (assigned_employee_id) REFERENCES EMPLOYEE(employee_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @add_ticket_response = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='SUPPORT_TICKET' AND column_name='response') = 0,
  'ALTER TABLE SUPPORT_TICKET ADD COLUMN response TEXT NULL AFTER message', 'SELECT 1'
);
PREPARE add_ticket_response_stmt FROM @add_ticket_response;
EXECUTE add_ticket_response_stmt;
DEALLOCATE PREPARE add_ticket_response_stmt;
SET @add_ticket_type = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='SUPPORT_TICKET' AND column_name='ticket_type') = 0,
  "ALTER TABLE SUPPORT_TICKET ADD COLUMN ticket_type ENUM('support','quote') NOT NULL DEFAULT 'support' AFTER customer_id", 'SELECT 1'
);
PREPARE add_ticket_type_stmt FROM @add_ticket_type;
EXECUTE add_ticket_type_stmt;
DEALLOCATE PREPARE add_ticket_type_stmt;
SET @add_ticket_assignee = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='SUPPORT_TICKET' AND column_name='assigned_employee_id') = 0,
  'ALTER TABLE SUPPORT_TICKET ADD COLUMN assigned_employee_id INT NULL AFTER response', 'SELECT 1'
);
PREPARE add_ticket_assignee_stmt FROM @add_ticket_assignee;
EXECUTE add_ticket_assignee_stmt;
DEALLOCATE PREPARE add_ticket_assignee_stmt;
SET @add_ticket_responded = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='SUPPORT_TICKET' AND column_name='responded_at') = 0,
  'ALTER TABLE SUPPORT_TICKET ADD COLUMN responded_at DATETIME NULL AFTER assigned_employee_id', 'SELECT 1'
);
PREPARE add_ticket_responded_stmt FROM @add_ticket_responded;
EXECUTE add_ticket_responded_stmt;
DEALLOCATE PREPARE add_ticket_responded_stmt;
SET @add_ticket_employee_fk = IF(
  (SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema=DATABASE() AND constraint_name='fk_ticket_employee') = 0,
  'ALTER TABLE SUPPORT_TICKET ADD CONSTRAINT fk_ticket_employee FOREIGN KEY (assigned_employee_id) REFERENCES EMPLOYEE(employee_id)', 'SELECT 1'
);
PREPARE add_ticket_employee_fk_stmt FROM @add_ticket_employee_fk;
EXECUTE add_ticket_employee_fk_stmt;
DEALLOCATE PREPARE add_ticket_employee_fk_stmt;

CREATE TABLE IF NOT EXISTS ACTIVITY_LOG (
  activity_id BIGINT NOT NULL AUTO_INCREMENT,
  actor_type ENUM('employee','customer','system') NOT NULL DEFAULT 'system',
  actor_id BIGINT NULL,
  employee_id INT NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id VARCHAR(80) NULL,
  details TEXT NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (activity_id),
  KEY ix_activity_created (created_at),
  KEY ix_activity_actor (employee_id, created_at),
  CONSTRAINT fk_activity_employee FOREIGN KEY (employee_id) REFERENCES EMPLOYEE(employee_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @add_activity_actor_type = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='ACTIVITY_LOG' AND column_name='actor_type') = 0,
  "ALTER TABLE ACTIVITY_LOG ADD COLUMN actor_type ENUM('employee','customer','system') NOT NULL DEFAULT 'system' AFTER activity_id", 'SELECT 1'
);
PREPARE add_activity_actor_type_stmt FROM @add_activity_actor_type;
EXECUTE add_activity_actor_type_stmt;
DEALLOCATE PREPARE add_activity_actor_type_stmt;
SET @add_activity_actor_id = IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='ACTIVITY_LOG' AND column_name='actor_id') = 0,
  'ALTER TABLE ACTIVITY_LOG ADD COLUMN actor_id BIGINT NULL AFTER actor_type', 'SELECT 1'
);
PREPARE add_activity_actor_id_stmt FROM @add_activity_actor_id;
EXECUTE add_activity_actor_id_stmt;
DEALLOCATE PREPARE add_activity_actor_id_stmt;

CREATE TABLE IF NOT EXISTS CUSTOMER_TESTIMONIAL (
  testimonial_id BIGINT NOT NULL AUTO_INCREMENT,
  customer_id INT NOT NULL,
  quote TEXT NOT NULL,
  consent_to_publish TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (testimonial_id),
  KEY ix_testimonial_status_date (status, created_at),
  CONSTRAINT fk_testimonial_customer FOREIGN KEY (customer_id) REFERENCES REGISTRATION(customer_id)
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

ALTER TABLE EMPLOYEE MODIFY role ENUM('admin','hr','dispatcher','technician','employee') NOT NULL DEFAULT 'employee';
