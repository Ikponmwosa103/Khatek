# Khatek Digital Tech

Render-ready PHP website for Khatek Digital Tech Nig.

## Render setup

1. Create a Render Web Service from this folder/repository.
2. Keep the service runtime set to Docker so Render uses the included
   `Dockerfile`.
3. Create or link a Render PostgreSQL database.
4. Add the database's connection string as the service environment variable
   `DATABASE_URL`.
5. Deploy. Render supplies `PORT` automatically; the included entrypoint
   configures Apache to use it.

The PHP API also supports the `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and
`DB_PASSWORD` variables for local PostgreSQL setups, and the `MYSQL*` variables
for local or legacy MySQL setups.

## Database setup

Run `schema.sql` once against the Render PostgreSQL database before using
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