# Lead workspace

Sidebar: Leads, Pipeline, Follow-ups, Tasks & Reminders.

## Contact lists and list automations

Open **List Manager** in the sidebar or lead navigation, then click **+ New List** to open the creation dialog. The manager shows a searchable list directory with a settings menu on every row. From any Lead Details page, **+ New list** opens the same dialog and adds that lead automatically. The required description is the message body sent to contacts. The list name is the email subject. Search available leads by name, company or email, then add or remove members at any time. On Lead Details, **Lists > Choose a list** shows saved names and marks lists containing the lead. Access to lists and sending is controlled separately with Leads list management, broadcast and automation permissions.

In a list, select one or more visible contacts (up to 50 per quick send). Manual WhatsApp sends open `wa.me` chats with the message prefilled; staff press Send in WhatsApp. Manual Email sends use SMTP. The automatic scheduler sends Email through SMTP and WhatsApp through Meta Cloud API. The administrator must save a phone-number ID, Graph API version, access token, approved template name and language in **WhatsApp connection**. The approved template must have one body text variable for the list description. Indian contact numbers are normalized to the `91` country code. A provider-accepted send is recorded as **Accepted**, which is not proof of final delivery; uncertain attempts are **Unconfirmed** so staff can check before retrying. The list page shows setup state and a recent send log.

The automation form supports once, daily, weekly and monthly schedules at an IST time. The minute scheduler runs `crm:list-automations` and sends the current list description to current accessible members; `crm:list-queue` processes queued bulk Email sends. Pause or resume schedules from the list. The bundled `start-local.ps1` process runs the Laravel scheduler; production must run `schedule:run` every minute. Legacy WhatsApp reminder schedules are paused by migration and require deliberate resumption after Meta setup. Test the approved template with a small permitted list before scheduling broad sends.

1. Add a lead with contact details, stage, priority and estimated value.
2. Open the lead to transfer it to an active team member who has Leads view permission. The current owner can transfer their own lead; administrators and staff with the transfer permission can transfer any lead they can view. The new owner sees it in Leads, while the former owner loses access unless they can view all leads. Editing, activities and email still follow the new owner's role permissions.
3. Admins assign Follow-ups, Tasks and Reminders to users separately. Transferring a lead does not change existing work assignments.
4. Complete, cancel, reopen or reschedule work from the lead or the work page. Record the outcome for the activity history.
5. Move stages using Pipeline or the lead details. Dashboard counts respect the current user's lead visibility.

Admins have full access. Grant staff Leads view permission in Users & Permissions > roles to show the Leads workspace. Grant create, contact edit, stage change, assigned follow-ups, assigned tasks, assigned reminders, activities and send email separately as needed; sending email also needs Activity access. Existing roles with legacy Work access retain the matching task, follow-up, reminder and activity access at runtime. Grant Stage separately, including to existing roles whose members should move leads. No existing role records are rewritten. The lead owner can transfer their lead without the separate transfer permission; grant that permission for transferring other visible leads. Without View all, users see only their own leads.

The Leads list supports selecting up to 50 visible, transferable leads on the current page and transferring them together. The server checks every selected lead and its version before moving any. The new owner receives an in-app notification for each transferred lead. Administrators see the most recent team member to open each lead, along with the latest transfer actor, in Leads and Dashboard Contacts. The lead detail page shows every team member's last open time. Opens from either list use the same detail-page tracking; opening a list without opening the lead does not count as a view. View timestamps are saved as system entries in the existing lead activities table and hidden from the normal activity timeline.

Due notifications are delivered once per work item. Rescheduling or reopening allows another reminder. Completed and cancelled work does not generate due notifications. Active browser notification polling catches up reminders for the current user. The local start-local.ps1 launcher also starts Laravel's minute scheduler and stops it when the launcher exits. Restart the launcher to enable background delivery for this new module. Production should run `php artisan schedule:run` every minute. All dates use application IST time.

Validation: tests/Feature/LeadManagementTest.php covers lead ownership isolation, assignment, stale edits, pipeline, page rendering, work completion, rescheduling, and one-time due notification delivery.


## Contact score and activity overview

Open a lead for its initials avatar, contact information, score and activity timeline. Admins set field weights in Lead settings; every weight is optional (0�100), and blank weights count as zero. The total need not equal 100. Score is the sum of the points assigned to filled fields, capped at 100; all-zero weights give score 0. Existing leads and dashboard scores are calculated from the current settings without rewriting lead data.

Notes, Call logs and Customer notes save typed activities. Email sends a plain-text message to the lead's saved email using the SMTP connection in Lead settings. Staff need both manage activity and send-email permissions. SMTP passwords are encrypted at rest and never filled back into the form. Sent means accepted by SMTP, not a guaranteed inbox delivery. Unconfirmed means check the sender's mailbox before attempting another email. Repeated form submissions use an idempotency key to prevent duplicate sends.

## Provider connections

Admin: Leads > Lead connections. Configure a default active lead owner and enable the provider. Imported leads are shared through the same owner permissions as manual leads. Provider inquiry IDs prevent duplicates. Existing contacts are not overwritten on a repeated inquiry.

- IndiaMART: CRM Pull API v2 key. Uses the CRM listing v2 endpoint and UNIQUE_QUERY_ID/SENDER fields.
- TradeIndia: API key, User ID and Profile ID. Uses my_inquiry.html with a date range; expects a JSON inquiry list (or RESPONSE/data list) containing a unique rfi_id/query_id/id and sender contact fields.
- Justdial: register the generated public HTTPS webhook endpoint with the provider. Authenticate with X-Lead-Key (or key query parameter). Receives leadid/name/mobile/email/company fields. Saving the settings alone cannot activate the provider's push service. Localhost cannot accept internet callbacks.

The scheduler runs crm:sync-leads every 15 minutes. Save & connect attempts the first pull immediately; subsequent checks respect the 15-minute rate interval. Initial import starts today. Credentials are encrypted. Connection status becomes Connected only after a successful provider response or valid webhook. On failure, the cursor does not advance. The current adapters have mock-response tests; live account credentials and provider payloads must be verified before relying on production imports. No credentials were used or messages sent during development tests.


## Updated assignment and contact workflow

Only admins create and assign scheduled actions. Each action has its own assignee, independent of the lead owner; changing a lead owner does not reassign existing work. Staff see and update their assigned actions from the work page. Due notifications go to that assignee. A task assignment does not grant access to edit the whole lead.

Imported inquiries stay as leads until Save as contact is selected (on lead details or Dashboard). This marks the existing record without duplicating it. Dashboard defaults to All leads, with a Saved contacts filter. Existing and newly entered manual leads are saved contacts.

Activity history supports edits and soft deletion with stale-version checks. Editing email history does not resend an email; deleting its record does not recall delivery. The original sent email cannot be modified through activity history.

Valid optional weightage changes auto-save regardless of their total, including clearing every field. The form reports saving errors explicitly. An open lead refreshes its score on a saved-weight notification, focus, and every ten seconds. Empty contact fields earn no points. No default weights were fabricated for existing leads.
