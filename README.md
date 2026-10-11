# BookBang

BookBang is a PHP/MySQL bookstore with search, accounts, saved books, reviews,
profiles, carts, Cash on Delivery orders and an admin dashboard.

## Production

The PHP application is hosted at https://bookbang.xo.je/. The Vercel address
redirects to that application so accounts and carts use the same origin/session.
The static build is retained as a fallback; it never publishes PHP or secrets.

Google and Facebook sign-in are intentionally disabled. Checkout accepts **COD
only**, requires a delivery address, and never requests card or wallet credentials.
New orders are pending and unpaid. The admin can mark them confirmed, shipped,
delivered or cancelled; delivered COD orders are marked paid.
Physical delivery is handled by the shop, not by a courier API.

## PHP setup

Use PHP 8+ with mysqli/mysqlnd, fileinfo and sessions, and MySQL 5.7+ or MariaDB.
Import `database/schema.sql`. On InfinityFree, first remove its CREATE DATABASE
and USE statements and select the account-prefixed database in phpMyAdmin.
New recovery, order-request and newsletter tables are created additively by
`bb_schema()` before the relevant flows run. Existing users/books/orders are kept.

Copy `hosting-config.example.php` to the ignored `hosting-config.php` on the host.
Set DB_HOST, DB_USER, DB_PASSWORD and DB_NAME. Environment variables override that
file; DB_PORT is optional (default 3306). Never commit populated credentials.

Upload the application to `htdocs`. Include `.htaccess` and `uploads/.htaccess`.
The first blocks direct access to private helpers/configuration and directory
listings; the second blocks executable files in uploaded-picture directories.
Upload only public application files, not Git data, tests, SQL, vendor, or scripts.

## Accounts and recovery

Sign-up requires a unique email/username and matching passwords of 12–72 characters.
Sign-in accepts email or username. Guest carts, saved books and guest orders from
the current session transfer to the account on sign-in or sign-up.

Customers receive a private recovery code after sign-up. They save it themselves
and use it with their email at `forgot-password.php`; no email-sending service is
required. Successful reset rotates the recovery code and invalidates older
signed-in sessions. Signed-in customers can regenerate a code with their current
password on their profile page. Keep codes and passwords out of screenshots/logs.

Admin access uses `users.role='admin'`. Assign it only to the shop owner's specified
account through the database panel. There are no public/default admin credentials.
The dashboard manages the catalogue, customers, orders and opted-in subscribers.
Newsletter signup saves subscriptions; automatic email delivery is not configured.

## Validation

`tests/integration.py` exercises the actual PHP endpoints and checks database
results in a disposable local database. It refuses production by using fixed
localhost addresses. Synthetic accounts/orders never contact the live site.

Start an isolated MariaDB instance on port 3311 and import the schema. Then:

```powershell
$env:DB_HOST='127.0.0.1'
$env:DB_PORT='3311'
C:/xampp/php/php.exe -S 127.0.0.1:8090 -t .
# In another terminal:
python tests/integration.py
```

The suite covers search/filtering, registration/login, duplicate identities,
CSRF checks, cart merging and ownership, discounts, avatars and profile changes,
saved books/reviews, recovery, COD validation and persistence, Buy Now refreshes,
idempotent submissions, order ownership/history, admin CRUD/fulfilment and
subscription/unsubscribe. Run `npm run build` to validate the Vercel fallback.

Legacy HTML/account/shop links redirect to the current PHP flows. The old
motorcycle-products database is no longer required.
