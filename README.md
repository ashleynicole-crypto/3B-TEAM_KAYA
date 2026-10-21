Sun Son Solar

Team Name
KAYA

Team Members
- Ando, Vivian P.
- Cruz, Ashley Nicole S.
- Cruz, Mark Kevin P.
- Delim, Jeremy V.

Description
Sun Son Solar is a web-based application designed to monitor, manage, and optimize solar power generation and system performance. The project aims to provide users with real-time data tracking, energy consumption insights, and database management for solar panel installations.

## Company and service information

The public homepage presents Sun Son Solar as a Pasig City solar provider and describes its products (panels, inverters, batteries, mounting, and wires) and services (consultation, design, permitting, installation, maintenance, repair, and monitoring). It highlights energy savings, sustainability, and energy independence and includes a service sign-up call to action and company contact details.

## Database setup

Import `database.sql` into MySQL/phpMyAdmin. It keeps the existing customer registration table and adds departments, employee account records, a configurable service catalog, bookings, site check-ins with coordinates, announcements, and technician service reports. Employee records can hold admin, dispatcher, and technician roles. Employee accounts are intended to be created by HR or an administrator.

No starter accounts or passwords are inserted. Create accounts through the secure account-provisioning flow and store password hashes, never the passwords from interview notes.

## CodeIgniter 4 application

The project now uses a CodeIgniter 4 MVC app for the public page and customer/staff workflows. Routes are defined in `app/Config/Routes.php`; controllers, views, and models handle authentication, customer bookings/profile/history/support, employee account management, dispatch, announcements, service availability/pricing, payments, technician site check-ins, and service reports. The original root PHP pages and `api/` endpoints remain as legacy files; use the CodeIgniter `public/` entry point.

Requirements: PHP 8.2+, Composer 2.0.14+, MySQL, and the PHP extensions required by CodeIgniter. From this folder, run `composer install`, copy `.env.example` to `.env`, set the database credentials and `app.baseURL`, import `database.sql`, then serve the `public/` directory as the web root. With XAMPP and Apache rewrite enabled, open `http://localhost/3B-TEAM_KAYA/`; alternatively open the `public/` directory directly. Create the first admin interactively with `php spark sunson:create-admin`; this avoids putting a starter password in code or SQL. Then use the admin dashboard to provision employee accounts.

Customer accounts can be self-registered. The staff dashboard is role-based: admins manage staff, services, availability, payments, reports, and announcements; dispatchers manage bookings and tickets; technicians see only their assigned job details and can check in with device location or submit job reports; HR manages employee accounts. Check-ins require browser location permission and a secure context (HTTPS or localhost). Booking availability is enforced when weekly service hours have been configured.

The workspace does not have PHP or Composer installed, so framework dependency installation and runtime verification could not be performed here. CodeIgniter 4's official guide describes Composer installation and server requirements: https://www.codeigniter.com/user_guide/installation/index.html
