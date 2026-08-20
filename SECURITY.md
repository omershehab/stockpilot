# Security Policy

## Supported versions

Until StockPilot publishes stable releases, security fixes are applied to the latest code on the default branch only.

## Reporting a vulnerability

Please do not open a public issue for a suspected vulnerability. Use GitHub's **Security** tab to submit a private vulnerability report. Include the affected area, reproduction steps, impact, and any suggested mitigation.

Avoid including real credentials or customer data. Maintainers will acknowledge a complete report as soon as practical, investigate it, and coordinate disclosure after a fix is available.

## Deployment responsibility

The repository ships development-friendly defaults. Production operators are responsible for disabling debug mode, protecting application keys, configuring HTTPS, queues, mail, backups, database access, file storage, and monitoring.
