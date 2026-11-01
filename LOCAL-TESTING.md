# Gagandeep Steels CRM — local testing

Open **http://127.0.0.1:8000** on this computer. Sign in using the credentials in .local/FIRST-LOGIN.txt. See AUTH-AND-PERMISSIONS.md for account setup, administrator-only password resets and terminal recovery.

## Start after restarting Windows

Double-click **start-local.cmd** in this project folder. Keep its terminal open during the demo, then open the address above in your browser. Press Ctrl+C to stop the web server. MySQL stays running until Windows shuts down or you run the stop command below.

If the CRM is already open and working, you do not need a second launcher.

## Run from VS Code

Open this project folder in VS Code and press **Ctrl+Shift+B**. The default task, **Run Gagandeep Steels CRM**, starts MySQL if needed and runs Laravel in the integrated terminal. Open **http://127.0.0.1:8000** and keep that terminal running. Press **Ctrl+C** in the terminal to stop Laravel.

Alternatively, choose **Terminal > New Terminal** and run:

```powershell
.\start-local.cmd
```

Use one launcher at a time. No global PHP, Composer, npm, or Live Server extension is needed. Refresh the browser after editing Blade, CSS or JavaScript files.

## Installed locally

- PHP 8.4.26: `.local/php/php.exe`
- MySQL 8.4.9: `.local/mysql-8.4.9-winx64`, listening on `127.0.0.1:3307`
- Laravel dependencies: `vendor/`, with versions recorded in `composer.lock`
- Client demo database: `gagandeep_steels`
- Separate disposable automated-test database: `gagandeep_steels_test`

The `.env` file contains the generated app key and local database credentials. MySQL data is stored in `.local/mysql-data`. Keep that directory and `.env`; deleting them can lose your data or configuration. The `.local` directory and environment secrets are excluded from Git. This setup uses paths specific to this computer; moving the folder requires updating `.local/my.ini`.

The launcher never resets your database. Rates remain saved after closing the browser or restarting Windows. Export CSV from the directory for a copy of your rates.

## Transporters

Open **Transporters** in the sidebar to add transporter name, contact person, phone number, optional GST number and address. Search the directory by any of these details. Duplicate names are rejected. Existing names from freight rates were imported automatically.

On the freight form, open **Transporter**, type part of a name, then click a result or use the arrow keys and Enter. The directory's transporter filter has the same search. The **Add transporter** link opens the separate page in another tab; reopen the dropdown after adding a name to load it without losing your form entries.

## Tests

With MySQL running, execute from the project folder:

```powershell
& .\.local\php\php.exe vendor/bin/phpunit
```

All 30 tests passed with 267 assertions after adding authentication and access control, using only the separate test database. Browser checks covered adding a transporter, partial-name search, case-insensitive matching, keyboard selection and no-match feedback.

## Stop MySQL gracefully

After stopping the web server, run:

```powershell
& .\.local\mysql-8.4.9-winx64\bin\mysqladmin.exe --defaults-extra-file=.local/mysql-admin.ini shutdown
```

## Demo scope

This is the freight-rate module: add, edit, delete, search, filter and export rates. Access requires login and is controlled by user roles and permissions. This setup is accessible only on this computer; you can demonstrate it in person or through screen sharing. A client opening the address on another device will not reach your CRM.

## Shared theme and responsive layouts

Both pages and the freight edit form use the shared blue, steel-grey and white stylesheet in public/css/app.css. Browser checks covered 320px and 390px phone widths, 768px tablet and desktop layouts. Mobile navigation has a close button, backdrop, Escape support and keyboard focus handling. Wide tables scroll within their panels.


## Edit and delete transporters

Use the Actions column in the transporter directory. Edit pre-fills all five fields; Save changes also updates the name on linked freight rates. Cancel returns to the directory. Delete opens a confirmation dialog. A transporter used by freight rates cannot be deleted until those rates are assigned to another transporter. Stale edit/delete submissions are rejected; reopen the record to use the latest version.


