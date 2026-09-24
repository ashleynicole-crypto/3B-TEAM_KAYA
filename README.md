# Sun Son Solar authentication

The registration and login forms use PHP endpoints and MySQL. phpMyAdmin is the database administration page; the site connects to the MySQL service that phpMyAdmin manages.

## Run locally with XAMPP

1. Copy this project folder into XAMPP's `htdocs` directory, for example `C:\xampp\htdocs\3B-TEAM_KAYA`.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`, choose **Import**, select this project's `database.sql`, and import it. The script creates the `sun_son_solar` database and the `REGISTRATION` table.
4. If your local MySQL uses the standard XAMPP `root` account with no password, the API is ready to connect. Otherwise set `SUNSON_DB_HOST`, `SUNSON_DB_PORT`, `SUNSON_DB_NAME`, `SUNSON_DB_USER`, and `SUNSON_DB_PASSWORD` in the Apache/PHP environment to match your MySQL account. Do not put production database credentials in browser JavaScript.
5. Open the site through Apache, for example `http://localhost/3B-TEAM_KAYA/`. Do not open `index.html` as a `file://` URL; PHP endpoints need Apache.

The `REGISTRATION` table uses `customer_id` as its primary key and stores the profile fields shown in the form. Email, phone number, and username are unique. The `password` column contains a PHP password hash, never the original password. The PHP installation needs PHP 8.1 or later with PDO MySQL (`pdo_mysql`) enabled. Registration is cleared only after the API confirms the database insert, and login succeeds only for a matching account in `REGISTRATION`.
