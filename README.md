# Queued bulk message

Queued bulk message is a Moodle local plugin that adds a **Queue bulk message** action to the existing admin bulk user
actions page.

The plugin is intended for large recipient sets where Moodle core's synchronous bulk message action can time out. Instead
of sending during the browser request, it splits selected users into configurable chunks and queues one adhoc task per
chunk. Moodle cron then sends the messages in the background.

## Requirements

- Moodle 5.1 or later in the 5.1 branch
- Moodle messaging enabled
- Cron configured and running

## Installation

Place the plugin at:

```text
local/queuedbulkmessage
```

Then run the Moodle upgrade process from the web interface or CLI:

```bash
php admin/cli/upgrade.php
```

## Configuration

Go to **Site administration > Plugins > Local plugins > Queued bulk message** and set the batch size.

The default batch size is 100 users per adhoc task. Invalid values outside 1 to 1000 fall back to 100 at runtime.

Each queued message can be sent as **Notification** or **Private user message** from the message form. Notifications keep
HTML formatting. Private user messages are sent from the admin who queued the message, converted to plain text, and appear
in Moodle's user-to-user messaging area.

The form also supports up to five attachments. The plugin accepts image files and common document formats, stores them
with Moodle's File API, and appends download links to the queued message. Notification messages use HTML links. Private
user messages include plain URLs. Attachment URLs are generated per recipient so they can be opened from Moodle Mobile
and text-only messaging views.

## Usage

1. Go to **Site administration > Users > Accounts > Bulk user actions**.
2. Select the users.
3. Choose **Queue bulk message** from the bulk action menu.
4. Enter a subject and HTML message.
5. Submit the form.
6. Run cron normally, or manually for testing:

```bash
php admin/cli/cron.php
```

## Permissions

Only users with `moodle/site:config` can see and use the queued bulk message action.

## Privacy

The plugin does not keep its own persistent personal data tables. Selected user IDs and message content are stored only as
Moodle adhoc task custom data until cron processes the queued tasks.

## License

GNU GPL v3 or later.
