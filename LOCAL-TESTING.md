# Local PostgreSQL testing

The local CRM uses PostgreSQL on `127.0.0.1:5432`.

## Start

Ensure the Windows service `postgresql-x64-18` is running, then double-click `start-local.cmd`. The launcher applies pending migrations, starts Laravel's scheduler and serves the CRM at `http://127.0.0.1:8000`.

## Command line

```powershell
& .\.local\php\php.exe artisan migrate --force
& .\.local\php\php.exe artisan test
& .\.local\php\php.exe artisan schedule:list
```

The main database is `gagandeep_steels`. Automated tests may only use `gagandeep_steels_test`; the test guard refuses any database without the `_test` suffix.

PostgreSQL credentials are stored only in ignored environment files. Do not commit `.env` or `.env.testing`.
