# Login, users and administrator recovery

Start the CRM from VS Code using **Ctrl+Shift+B** and open **http://127.0.0.1:8000/login**.

## Reset a user's password

1. Sign in as an Administrator.
2. Open **Users & Permissions**, find the user and click **Edit**.
3. Update their name, login email, role or active status as needed.
4. Enter **New password** and **Confirm new password**, then **Save user**.

Leave both password fields blank to preserve the current password. New passwords must contain at least 8 characters with letters and numbers. The eye buttons reveal only what you enter. Existing passwords are hashed and cannot be read back. Share the new password privately with the intended user.

Only Administrators can reset an existing user's password, including when a custom role has the obsolete reset permission. Password-reset email actions and public forgot/reset URLs have been removed. SMTP is not required for this workflow. Users can still change their own password in **My account**, using their current password.

Saving a user's details invalidates their sessions and existing reset tokens. Editing your own account signs you out and sends you to the login page with a confirmation.

## If the Administrator forgets their email or password

On the computer hosting this project, open its folder in VS Code, then open the terminal and run:

```powershell
.\.local\php\php.exe artisan crm:recover-admin
```

The command lists Administrator IDs, names, login emails and active status. Choose the ID, keep or replace the login email, enter and confirm a new password, and confirm recovery. Password input is hidden. Recovery reactivates the selected Administrator, invalidates old sessions and reset tokens, and saves the new password as a hash. It does not print, email or store the new password as plaintext. Invalid input or cancellation leaves the account unchanged.

This is a local server-owner recovery command, not a public web endpoint. Access to the project, database and operating-system account must be restricted to trusted maintainers. There is no universal master password or hidden web login.

The historical `.local/FIRST-LOGIN.txt` file is a private initial setup note. It is not updated by normal password changes and must not be treated as the current password database. Remove it when no longer needed.

To create an additional administrator:

```powershell
.\.local\php\php.exe artisan crm:create-admin
```

## Session policy and permissions

- Login expires at the next **00:00 Asia/Kolkata** and after **30 minutes of inactivity**, enforced on both server and browser.
- Background session polling does not extend inactivity. Multiple tabs coordinate activity and logout.
- Default roles: **Administrator, Manager, Operator, Viewer**. Custom roles support freight, transporter, user and role management permissions.
- User directory supports search, role/status filtering, pagination, edit, activation/deactivation and deletion.
- Administrator role is protected. You cannot delete/deactivate yourself or change your own role. At least one active Administrator must remain.
- Roles with assigned users cannot be deleted. Delegated account managers cannot grant permissions beyond their own.
- Passwords are hashed; forms use CSRF protection; login attempts are rate limited. Stale edits are rejected.

## Interface feedback

All screens share toast notifications using the actual action success or validation message. All toast types (success, notice, warning and error) close automatically after five seconds. They can also be dismissed manually. Toasts are accessible to screen readers and adapt to mobile screens. Native form validation, transporter refresh failures, connection changes, CSV export and session expiry have relevant feedback.

Every password input includes a show/hide eye button and hides again on form submission or when the browser tab is hidden. All pages retain the Gagandeep Steels logo/theme and **Managed & crafted with care by Logic Integratorz** footer.

## Verification

Feature coverage includes existing freight/transporter CRUD, permission checks, idle/midnight expiry, administrator-only reset, unchanged blank passwords, password confirmation validation, revoked sessions/tokens, removed public recovery URLs, and terminal administrator recovery. Tests use only the separate database ending in `_test`.
## Email validation and notifications

New/edited user emails are trimmed and normalized to lowercase. Server-side validation checks email syntax, a complete dotted domain, and uniqueness. The form also validates the address format in the browser. Local development addresses such as `admin@gagandeepsteels.local` remain supported. Format validation does not prove mailbox ownership or delivery; no verification emails are sent.

The bell in the header and **Notifications** in the sidebar open a persistent notification center. It includes All/Unread/Read filters, mark as read, mark all as read, dismiss, pagination and timestamps in India time. The bell refreshes its unread count every 30 seconds while the tab is visible. Background polling never extends the inactivity deadline.

Successful freight/transporter/user/role changes notify the acting user and active Administrators. New users get a welcome notification. Account/role changes notify affected users; personal password changes are visible only to the account owner. Notification content never contains passwords, password hashes or reset tokens. Each user can read or modify only their own notifications, with historical module notifications hidden if the user no longer has the required permission. Notifications begin with new activity after this feature is installed; historical actions are not reconstructed. These are in-app notifications, not email, SMS or operating-system push messages.

Validation: **50 feature tests verified across the full suite and targeted checks; the JavaScript five-second toast timing test also passed**, including notification ownership, permission filtering, bulk reads, password length, duplicate/invalid emails, activity generation and non-extending background polls.
## Send and manage team notifications

Open **Notifications → Send notification**. Enter a title and message, then choose **Selected users**, **Selected roles**, or **All active users**. Selected users can be searched by name or role. Delivery excludes inactive accounts and duplicate submissions do not send a second message.

Only Administrator has management access by default. Under **Users & Permissions → Create/Edit role → Notifications**, an Administrator can grant:

- **View sent notification history** (`notifications.view`).
- **Compose & send notifications** (`notifications.send`).
- **Recall sent notifications** (`notifications.recall`).

Selecting send or recall automatically includes sent-history access. Only an Administrator can change these permissions or assign roles carrying these permissions. Delegates cannot regrant their access to other people.

Administrators see and can recall all sent messages. Delegated roles can see and recall only their own sent messages, subject to their permissions. **Sent notifications** shows delivery and read/dismissed counts. Recall requires a confirmation and hides the message from all recipients while retaining sent history; it cannot undo a message already read. Dismissing a notification preserves its read/dismissed receipt for sent-history reporting. Every user retains access to their own inbox regardless of send permissions.