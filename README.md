# StockPilot

[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

StockPilot is a bilingual, multi-tenant barcode and inventory management application for growing teams. It connects product records, barcodes, warehouses, batches, stock movements, physical counts, and print-ready labels in one Laravel application.

## Why StockPilot?

- **Barcode-first workflows:** create and scan Code 128, retail, QR, and Data Matrix codes.
- **Reliable inventory:** an immutable movement ledger updates stock snapshots transactionally and prevents negative stock.
- **Multi-warehouse control:** manage locations, transfers, counts, batches, expiry dates, and reorder levels.
- **Label designer:** drag, resize, style, and print custom labels at arbitrary millimetre dimensions.
- **Team-ready tenancy:** isolated company workspaces, invitations, roles, custom permissions, and usage limits.
- **Arabic and English:** first-class RTL/LTR interfaces with Saudi Riyal support.
- **Import friendly:** upload CSV/XLSX product catalogs with all-or-nothing validation.

## Tech stack

- PHP 8.2+, Laravel 12, Blade, and Laravel Breeze
- SQLite by default; Laravel-supported database drivers can be configured through `.env`
- Alpine.js, Tailwind CSS, and Vite 7
- PHPUnit 11

## Quick start

Prerequisites: PHP 8.2+, Composer, and Node.js 20.19+ or 22.12+.

```bash
git clone <your-fork-or-repository-url>
cd stockpilot
composer run setup
php artisan db:seed
php artisan serve
```

Open <http://127.0.0.1:8000> and use the seeded demo account:

```text
Email: demo@stockpilot.test
Password: password
```

The setup command installs dependencies, creates `.env` and the SQLite database, generates an application key, runs migrations, and builds frontend assets. The demo credentials are for local development only.

Windows users can start the server with `./start.ps1` after setup. The script uses the optional project-local `.runtime` PHP executable when present and otherwise falls back to the system PHP installation.

## Development

```bash
# Run the application, queue, logs, and Vite together
composer run dev

# Run the test suite
composer test

# Check PHP formatting
vendor/bin/pint --test

# Build production assets
npm run build
```

## Architecture and data integrity

Tenant data lives in a shared database and is scoped by `company_id`. Workspace URLs include the active company slug, middleware rejects non-members, and controllers resolve tenant-owned records through the active company. Feature tests cover cross-company access boundaries.

Product quantities are never edited directly. `StockMovementService` records ledger lines and updates stock snapshots in a single database transaction with row locking and negative-stock protection. Finalized inventory counts create traceable adjustment transactions.

## Project status

StockPilot is an early-stage project ready for experimentation and community feedback. Before using it for production inventory, review the deployment, backup, mail, queue, security, and database configuration for your environment.

Ideas for future releases include scanner-focused mobile flows, richer reporting, public APIs, and additional import/export integrations. See an opportunity? Open a feature request after the repository is published.

## Contributing

Contributions are welcome. Read [CONTRIBUTING.md](CONTRIBUTING.md) for the local workflow and pull-request expectations. Please follow our [Code of Conduct](CODE_OF_CONDUCT.md) and report vulnerabilities according to [SECURITY.md](SECURITY.md).

## License

StockPilot is open source under the [MIT License](LICENSE).
