# Khatek Digital Tech

PHP website and API for Khatek Digital Tech Nig.

## Deployment

The backend is designed to run on Render using the included Dockerfile.

1. Connect this repository to a Render Web Service.
2. Select Docker as the runtime and use the repository root as the Docker context.
3. Set the Render service environment variable `DATABASE_URL` to the Neon PostgreSQL connection string. Keep the value in Render's Environment settings; never commit it.
4. The Docker image installs PHP's `pdo_pgsql` extension and Composer dependencies.
5. Render supplies `PORT`; `docker-entrypoint.sh` configures Apache to listen on it.
6. Configure the following environment variables if the contact form should send email:
   `MAILTRAP_HOST`, `MAILTRAP_PORT`, `MAILTRAP_USERNAME`,
   `MAILTRAP_PASSWORD`, `MAILTRAP_ENCRYPTION`,
   `MAILTRAP_FROM_EMAIL`, and `MAILTRAP_TO_EMAIL`.
   Keep SMTP credentials in Render Environment settings, not in source files.

## Database setup

The application uses PostgreSQL through PDO.

Run `schema.sql` against the Neon database before enabling registration. It creates the `users` table and the `updated_at` trigger. The case-insensitive email index is also included.

## Frontend API routing

The Netlify frontend proxies requests under `/Api/*` to the Render backend. Browser requests therefore use the Netlify site origin, and PHP session cookies are configured as Secure, HttpOnly, and SameSite=Lax.

Do not change the Netlify API rewrite when deploying this backend unless the frontend routing is intentionally redesigned.

## Included routes

- `/index.html` — main site
- `/kids-coding.html` — kids coding bootcamp
- `/auth.html` — registration and login
- `/account-index.html` — signed-in account page
- `/Api/register.php` — create an account
- `/Api/login.php` — sign in
- `/Api/account.php` — read the signed-in account
- `/Api/delete-account.php` — delete the signed-in account
- `/Api/logout.php` — end the current session
- `/Api/contact.php` — send a contact-form email through configured SMTP
