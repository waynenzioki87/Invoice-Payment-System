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
- MySQL/MariaDB for local or production data; SQLite is used by the automated tests.
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

Set `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_FROM_ADDRESS` in `.env` for SMTP. The example configuration uses the log mailer and will not send external email.

Run a queue worker for welcome, invoice, reminder, and payment receipt messages. Invoice reminders are eligible for unpaid invoices due within three days or overdue, and are limited to one every seven days per invoice. Add Laravel's scheduler to the host's scheduled tasks (run `php artisan schedule:run` every minute) for the daily reminder schedule.

## Safaricom Daraja sandbox

Set `MPESA_BASE_URL`, `MPESA_CONSUMER_KEY`, `MPESA_CONSUMER_SECRET`, `MPESA_SHORTCODE`, `MPESA_PASSKEY`, and `MPESA_CALLBACK_URL` in `.env`. Use sandbox credentials from Safaricom's Daraja portal. The callback URL must be a publicly reachable HTTPS endpoint; Safaricom cannot call a local `localhost` URL. Never use live credentials in local development.

## Tests

Run the test suite with:

```sh
php artisan test
```

The feature tests use SQLite in memory and fake Daraja HTTP responses and queued mail; they do not prove live Safaricom or SMTP connectivity.

## Project status and deployment notes

This repository currently has no Git remote configured. Set up and verify the intended GitHub repository before pushing. Keep `.env` and all credentials out of commits, and configure production database, mail, queue, HTTPS, and scheduled-task services before deployment.

No project screenshots or developer contact details are included yet.
