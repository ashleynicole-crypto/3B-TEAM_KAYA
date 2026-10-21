# Sun Son Solar Management System

CodeIgniter 4 application for the Sun Son Solar public website and customer and employee portals.

## Main features

- Public company, solar benefits, product, service, FAQ, approved testimonial, and news sections.
- Customer registration, service bookings, appointment reschedule requests, history, notifications, support tickets, product quote requests, and profile/password management.
- Employee portals for administration, HR, dispatch, technicians, and general employee announcements.
- Staff account search, editing, activation, deactivation, and one-time temporary password resets. Employee accounts are created by HR or an administrator; temporary passwords must be changed at first sign-in.
- Shared booking calendar, technician assignment with schedule conflict checks, service availability/pricing, support responses, payments/receipt references, CSV reports, and technician check-ins/service reports.
- Admin activity history for account, booking, service, product, announcement, settings, payment, support, check-in, and report actions.

Online product orders, inventory, payment processing, email/SMS delivery, check-out attendance, and detailed department-specific permissions are not enabled because the interview notes leave those requirements unconfirmed.

## Database setup

Import `database.sql` into MySQL/phpMyAdmin. The script creates the tables, includes idempotent upgrades for databases created by earlier versions, and seeds the preregistered Katherine Sinagaraw and Sol Solis employee accounts and the Ashley Nicole Cruz customer account. Passwords are stored as hashes; plaintext passwords are not included in the repository. Create additional employee accounts through the authorized admin or HR workflow.

## Run locally

Requirements: PHP 8.2+, Composer, MySQL, and the PHP extensions required by CodeIgniter 4.

1. Run `composer install`.
2. Configure `.env` with the database credentials and `app.baseURL`.
3. Import `database.sql`.
4. Serve the `public/` directory as the web root. With XAMPP/Apache and rewrite enabled, open `http://localhost/3B-TEAM_KAYA/`.

For the current development preview, use `php -S 127.0.0.1:8080 -t . dev-router.php` and open `http://127.0.0.1:8080/`.

## Code layout

- `app/Config/Routes.php` defines explicit public, customer, and staff routes. Auto-routing is disabled.
- `app/Controllers/` contains the `Home`, `Auth`, and `Portal` request handlers.
- `app/Libraries/ActivityLogger.php` records important actions in `ACTIVITY_LOG`.
- `app/Models/` contains the customer registration model.
- `app/Views/` contains the public, authentication, customer, and role-specific portal pages.
- `database.sql` defines the database and repeatable schema upgrades.

The single login page accepts customer and employee credentials, then redirects to the correct portal based on account type and employee role. Technicians can see details only for jobs assigned to their account. Check-ins use browser geolocation and require location permission in a secure context (HTTPS or localhost).
