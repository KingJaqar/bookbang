# BookBang Setup

## Requirements

- PHP 8.1 or newer with `mysqli` and `mysqlnd` enabled
- MySQL 5.7+ or a compatible MariaDB release
- Apache/XAMPP, with this project directory served as `/Bookbang`
- Composer dependencies are already included in `vendor/`; rerun `composer install` after changing `composer.json`

## Database

Import `database/schema.sql` into MySQL. It creates the `bookbang_db` database, the tables used by the bookstore flows, and the 25-book starter catalogue with local cover paths.

```sh
mysql -u root -p < database/schema.sql
```

The default connection is `localhost`, user `root`, blank password, database `bookbang_db`, which matches a default local XAMPP installation. Set these environment variables for other installations; do not put production credentials in PHP files:

- `DB_HOST`
- `DB_USER`
- `DB_PASSWORD`
- `DB_NAME`

The web server process must receive the variables. For Apache, configure them in its service environment or Apache configuration, then restart Apache.
The SQL file creates `bookbang_db`; if you set a different `DB_NAME`, change the database name at the top of `database/schema.sql` to match before importing.

## Run

Place the project folder under the web server document root so it is reachable at `http://localhost/Bookbang/`. Open `http://localhost/Bookbang/Homepage.php`.

Google and Facebook sign-in additionally require valid provider credentials and callback URLs in their respective config files. Email/password signup, catalogue browsing, cart, checkout, and order history use the local MySQL database.

`Shop.php` is a separate legacy motorcycle-products page, not part of the bookstore schema. It uses `SHOP_DB_HOST`, `SHOP_DB_USER`, `SHOP_DB_PASSWORD`, and `SHOP_DB_NAME` and expects a `products` table in that database.
