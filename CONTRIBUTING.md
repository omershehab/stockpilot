# Contributing to StockPilot

Thanks for helping improve StockPilot. Bug fixes, tests, documentation, translations, accessibility improvements, and focused feature proposals are welcome.

## Development setup

1. Fork and clone the repository.
2. Install PHP 8.2+, Composer, and Node.js 20.19+ or 22.12+.
3. Run `composer run setup`.
4. Optionally run `php artisan db:seed` for demo data.
5. Start development with `composer run dev`.

Never commit `.env`, databases, uploaded files, dependency directories, logs, or real business/customer data.

## Before opening a pull request

```bash
vendor/bin/pint --test
composer test
npm run build
```

- Keep each pull request focused on one change.
- Add or update tests for behavior changes.
- Update English and Arabic copy together when a user-facing string changes.
- Preserve tenant isolation by resolving company-owned records through the active company.
- Explain any migration, security, or inventory-ledger implications in the pull request.

## Issues and security

Search existing issues before opening a new one and include reproducible steps for bugs. Do not disclose security vulnerabilities in a public issue; follow [SECURITY.md](SECURITY.md) instead.
