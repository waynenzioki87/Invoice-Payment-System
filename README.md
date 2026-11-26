# INVOICEX

INVOICEX is a Laravel invoicing and payment application for managing customers, invoices, payment records, receipts, and business reports.

## Features

- Role-based admin, staff, and customer dashboards.
- Customer and invoice management, invoice duplication, PDF download/print, and payment history.
- Manual and partial payments with balance validation and automatic invoice status updates.
- Safaricom Daraja sandbox STK Push support with callback confirmation.
- Payment receipt PDFs and queued customer email notifications.
- Revenue, payment-method, outstanding-invoice, overdue, and customer reports with CSV export.
- Customer portal access scoped to the signed-in customer.

## Technology

- PHP 8.2+
- Laravel 12
- MySQL/MariaDB or PostgreSQL for application data; SQLite is used by the automated tests.
- Blade, Bootstrap, Vite, and Dompdf

## Local setup

1. Install PHP 8.2+, Composer, Node.js/npm, and MySQL/MariaDB. Start MySQL in XAMPP if using the default local stack.
2. Install PHP packages and create the environment file:

   ```sh
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

   On Windows PowerShell, use `Copy-Item .env.example .env` instead of `cp`.

3. Configure `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`, then run:

   ```sh
   php artisan migrate
   npm install
   npm run build
   ```

4. Configure a real admin and staff user through your deployment's approved account-provisioning process. The default database seeder creates only a test user; it is not a production admin setup.
5. Start the application and a queue worker:

   ```sh
   php artisan serve
   php artisan queue:work
   ```

Set `APP_URL` to the URL used to access the application. Do not commit `.env` or paste credentials into source control.

## Roles and access

- **Admin:** `/admin/dashboard`; manages customers, invoices, payments, reports, and settings.
- **Staff:** `/staff/dashboard`; manages operational customer, invoice, and payment workflows.
- **Customer:** `/customer/dashboard`; accesses only invoices and payments linked to their customer account.

Routes are protected by authentication and role middleware. Customer ownership is checked server-side, including invoice and receipt downloads.

## Email and reminders

Set `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME`, and `MAIL_FROM_ADDRESS` in `.env` for SMTP. The example configuration uses the log mailer and will not send external email.

Run a queue worker for welcome, invoice, reminder, and payment receipt messages. Invoice reminders are eligible for unpaid invoices due within three days or overdue, and are limited to one every seven days per invoice. Add Laravel's scheduler to the host's scheduled tasks (run `php artisan schedule:run` every minute) for the daily reminder schedule.

## Safaricom Daraja sandbox

Set `MPESA_BASE_URL`, `MPESA_CONSUMER_KEY`, `MPESA_CONSUMER_SECRET`, `MPESA_SHORTCODE`, `MPESA_PASSKEY`, and `MPESA_CALLBACK_URL` in `.env`. Use sandbox credentials from Safaricom's Daraja portal. The callback URL must be a publicly reachable HTTPS endpoint; Safaricom cannot call a local `localhost` URL. Never use live credentials in local development.

## Tests

Run the test suite with:

```sh
php artisan test
```

The feature tests use SQLite in memory and fake Daraja HTTP responses and queued mail; they do not prove live Safaricom or SMTP connectivity.

## Deploying to Render

The included `Dockerfile` builds the Vite assets and runs Laravel through Apache. The Render Blueprint in `render.yaml` defines a web service, PostgreSQL database, queue worker, and a once-per-minute scheduler for Laravel's scheduled reminders. The database migration runs before each web deploy. All services use the Frankfurt region.

1. Connect the GitHub repository to Render and select the `render.yaml` Blueprint.
2. Review resource plans and costs before syncing. The Blueprint uses paid plans so the database persists and queued email, SMTP, and scheduled tasks can run reliably. Render's free database expires after 30 days, and free web services cannot send SMTP traffic on ports 465/587.
3. Configure the prompted `APP_KEY`, SMTP, and Daraja values in Render's environment settings. Generate the Laravel key with `php artisan key:generate --show`; enter it only in Render, never in source control or chat.
4. Confirm `APP_URL` and `MPESA_CALLBACK_URL` match the actual Render service URL (the callback path is `/mpesa/callback`) if Render assigns a different hostname.
5. After the first successful deploy, provision the initial admin account through a secure production process. The default seeder is for tests and must not be used to create production credentials.

Render's filesystem is ephemeral; do not rely on files written to the container surviving restarts or deploys. The app currently generates invoices and receipts on demand rather than storing uploaded documents. Never commit `.env` or paste credentials into source control.
