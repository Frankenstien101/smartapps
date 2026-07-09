# BSPI IT Asset Manager - PHP + MS SQL

This package is a PHP application backed by Microsoft SQL Server.

## Included

- PHP partials for the UI.
- `fetch('api.php')` for server requests.
- SQL Server schema and seed data.

## Requirements

- PHP 8+
- Microsoft SQL Server
- PHP SQL Server driver: `pdo_sqlsrv`

## Setup

1. Run `database/01_schema.sql` in SQL Server Management Studio.
2. Run `database/02_seed_data.sql`.
3. Edit `config.php` with your SQL Server username and password.
4. Put the folder in your web root.
5. Open `index.php` in your browser.

Example local URL:

`http://localhost/bspi_asset_php_mssql_from_gs/index.php`

## Main files

- `index.php` - main page
- `api.php` - PHP API for SQL Server-backed app logic
- `db.php` - SQL Server connection helpers
- `partials/` - HTML templates
- `database/` - schema and seed scripts
