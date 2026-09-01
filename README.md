# Khatek Digital Tech

Railway-ready PHP website for Khatek Digital Tech Nig.

## Railway setup

1. Create a Railway service from this folder/repository.
2. Keep the service on the Dockerfile deployment path.
3. Add or link a Railway MySQL service to the web service.
4. Make sure the web service receives these Railway variables:
   `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, and
   `MYSQLDATABASE`.
5. Railway supplies `PORT` automatically; the included entrypoint configures
   Apache to use it.

The API also accepts `MYSQL_URL` or `MYSQL_PUBLIC_URL` when Railway provides a
complete MySQL connection URL. `DATABASE_URL` is accepted only when its scheme
is `mysql`.

## Database setup

Run `schema.sql` once against the Railway MySQL database before using
registration or login.

## Included routes

- `/index.html` — main site
- `/kids-coding.html` — kids coding bootcamp
- `/auth.html` — registration and login
- `/account-index.html` — signed-in account page
- `/Api/register.php` — create an account
- `/Api/login.php` — sign in
- `/Api/account.php` — read or delete the signed-in account
- `/Api/delete-account.php` — delete the signed-in account
- `/Api/logout.php` — end the current session