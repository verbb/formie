# Configuration
Create a `formie.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these settings per environment.

The example below shows the defaults already used by Formie, so you only need to add the settings you want to change.

```php
<?php

return [
    '*' => [
        'pluginName' => 'Formie',
        'defaultPage' => 'forms',
        'compatibilityMode' => true,
        'staticCacheRefreshOnLoad' => false,
        'allowedSubmitMethods' => 'both', // `both`, `ajax`, or `page-reload`

        // Forms
        'validateCustomTemplates' => true,
        'defaultFormTemplate' => '',
        'defaultFormStencil' => '',
        'defaultEmailTemplate' => '',
        'formDefaults' => [
            'defaultStatus' => '',
            'submissionTitleFormat' => '{timestamp}',
            'collectIp' => false,
            'collectUser' => false,
            'submitMethod' => 'page-reload',
            'dataRetention' => 'forever',
            'dataRetentionValue' => null,
            'fileUploadsAction' => 'retain',
            'displayFormTitle' => false,
            'displayCurrentPageTitle' => false,
            'displayPageTabs' => false,
            'displayPageProgress' => false,
            'progressCalculation' => 'completion',
            'progressPosition' => 'end',
            'scrollToTop' => true,
            'requiredIndicator' => 'asterisk',
        ],
        'fieldDefaults' => [
            \verbb\formie\fields\FileUpload::class => [
                'uploadLocationSource' => '',
            ],
            \verbb\formie\fields\Date::class => [
                'displayType' => '',
                'defaultOption' => '',
                'defaultValue' => null,
            ],
        ],
        'notificationDefaults' => [
            'fromName' => null,
            'from' => null,
            'replyTo' => null,
            'replyToName' => null,
            'subject' => null,
            'attachFiles' => null,
            'attachPdf' => null,
            'pdfTemplateId' => null,
            'enabled' => null,
        ],
        'integrationDefaults' => [
            'captchas' => [],
        ],
        'enableUnloadWarning' => true,
        'errorAriaLive' => 'polite',
        'enableBackSubmission' => true,
        'enableMultiPageForms' => true,
        'ajaxTimeout' => 10,
        'filterIntegrationMapping' => true,
        'includeDraftElementUsage' => false,
        'includeRevisionElementUsage' => false,
        'outputConsoleMessages' => true,

        // General Fields
        'disabledFields' => [],
        'defaultLabelPosition' => \verbb\formie\positions\AboveInput::class,
        'defaultInstructionsPosition' => \verbb\formie\positions\AboveInput::class,

        // Fields
        'allowPublicVolumes' => true,
        'allowMultiSelectDropdowns' => true,
        'allowPhoneCountrySelector' => true,
        'enableLargeFieldStorage' => false,
        'includeFlatpickrCss' => true,
        'plainTextHtmlSanitizationMode' => 'preserve',

        // Submissions
        'maxIncompleteSubmissionAge' => 30,
        'enableCsrfValidationForGuests' => true,
        'useQueueForNotifications' => true,
        'useQueueForIntegrations' => true,
        'queuePriority' => null,
        'redirectUri' => null,
        'paymentWebhookProxyUrl' => null,
        'setOnlyCurrentPagePayload' => false,
        'submissionsBehaviour' => 'all',
        'submissionStateRetentionDays' => 30,
        'saveResumeTokenTtlDays' => 14,
        'maxSavedDraftsPerSession' => 10,
        'anonymousClientBootstrapRateLimit' => 30,
        'anonymousClientRefreshRateLimit' => 120,
        'anonymousClientRateWindowSeconds' => 60,

        // Sent Notifications
        'sentNotifications' => true,
        'maxSentNotificationsAge' => 30,

        // Spam
        'saveSpam' => true,
        'spamLimit' => 500,
        'spamEmailNotifications' => false,
        'spamBehaviour' => 'showSuccess',
        'spamKeywords' => '',
        'spamBehaviourMessage' => '',

        // Email Notifications
        'sendEmailAlerts' => false,
        'alertEmails' => null,
        'alertEmailsUserGroup' => null,
        'emptyValuePlaceholder' => 'No response.',

        // PDFs
        'pdfPaperSize' => 'letter',
        'pdfPaperOrientation' => 'portrait',

        // Theme
        'themeConfig' => [],
        'useCssLayers' => false,

        // Captchas
        'captchas' => [],

        // Export
        'defaultExportFolder' => '@storage/formie-export',
    ],
];
```

## Configuration options
- `pluginName` sets a custom name for the plugin.
- `defaultPage` sets the default Formie control panel page when clicking Formie in the main navigation.
- `compatibilityMode` enables compatibility shims for older Formie APIs during an upgrade.
- `staticCacheRefreshOnLoad` allows rendered forms to refresh request-specific values when initialized on statically cached pages. Formie also treats this as enabled when Blitz is installed and enabled.
- `allowedSubmitMethods` restricts which submission methods are available in the form builder: `both` (default), `ajax`, or `page-reload`. Payment integrations that require Ajax still force Ajax when applicable.

### Forms
- `validateCustomTemplates` checks that custom form template paths exist before they are saved.
- `defaultFormTemplate` sets the default form template handle used for new forms.
- `defaultFormStencil` sets a stencil handle to apply automatically when new forms are created without an explicit stencil.
- `defaultEmailTemplate` sets the default email template handle used for new email notifications.
- `formDefaults` sets structured defaults applied to new forms and stencils, including default submission status, submission title format, privacy settings, submission method, data retention, file-upload deletion behavior, and appearance settings. Leave a value empty or `null` to inherit Formie’s built-in behaviour.
- `fieldDefaults` sets per-field-type defaults applied when new fields are added to a form. Keys are field class names; values are arrays of setting handles and values. Field types opt in via `supportedDefaults()`. Leave a value empty or `null` to inherit Formie’s built-in behaviour. Third-party field authors should see [Field Defaults](/developers/field-defaults).
- `notificationDefaults` sets defaults applied when a new email notification is created. Leave a value empty or `null` to inherit Formie’s built-in behaviour.
- `integrationDefaults` controls default captcha integration states for new forms and stencils. Use `captchas[handle]` with `null` to inherit each integration’s global enabled state, or `true`/`false` to force enable or disable.
- `enableUnloadWarning` shows an unload warning when a user changes a front-end form and tries to leave without submitting.
- `errorAriaLive` controls how front-end validation and submit errors are announced to screen readers. Use `polite` (default), `assertive`, or `off` for visual-only errors. Live validation while typing always uses polite announcements; submit-time errors use this setting.
- `enableBackSubmission` submits the current page content when a user clicks the Back button on a multi-page form.
- `enableMultiPageForms` controls whether forms can contain multiple pages in the form builder. When disabled, authors cannot add pages and forms with more than one page cannot be saved.
- `ajaxTimeout` sets the timeout in seconds for Ajax requests made by Formie’s front-end JavaScript.
- `filterIntegrationMapping` filters field-mapping options shown in integrations to fields that are usually suitable for the target setting.
- `includeDraftElementUsage` includes draft elements when Formie checks where a form is used.
- `includeRevisionElementUsage` includes revision elements when Formie checks where a form is used.
- `outputConsoleMessages` controls whether Formie’s front-end JavaScript can output console messages.

### General Fields
- `disabledFields` is an array of field classes that should be disabled and unavailable in the form builder.
- `defaultLabelPosition` sets the default label position for new forms and fields.
- `defaultInstructionsPosition` sets the default instruction position for new forms and fields.

### Fields
- `fieldDefaults` stores per-field-type default settings. For example, set File Upload, Date, Phone, Agree, Email, Number, element field, and other supported field defaults with their field class names as keys. Legacy settings such as `defaultFileUploadVolume`, `defaultDateDisplayType`, `defaultDateValueOption`, and `defaultDateTime` are migrated automatically into `fieldDefaults`. Custom field types can opt in via [Field Defaults](/developers/field-defaults).
- `allowPublicVolumes` allows File Upload fields to use public asset volumes, and controls whether “Public URL” is available as an email summary value. Configure in **Settings → Fields**.
- `allowMultiSelectDropdowns` controls whether form editors can enable “Allow Multiple” on Dropdown and element fields using a dropdown display type. When disabled, the setting is hidden in the form builder and existing values are forced off. Configure in **Settings → Fields**.
- `allowPhoneCountrySelector` controls whether form editors can enable the country code selector on Phone Number fields. When disabled, the setting is hidden in the form builder and existing values are forced off. For default-off behaviour on new phone fields without hiding the setting, use [Field Defaults](/developers/field-defaults) (`countryEnabled: false`). Configure in **Settings → Fields**.
- `enableLargeFieldStorage` stores field content in large-text database columns for projects that expect very large submission payloads.
- `includeFlatpickrCss` controls whether Formie injects Flatpickr styles for Calendar (Advanced) date fields. Set to `false` when your project already provides its own Flatpickr stylesheet.
- `plainTextHtmlSanitizationMode` controls how plain-text input values are handled when HTML is submitted. Use `preserve` or `sanitize`.

### Submissions
- `maxIncompleteSubmissionAge` sets the maximum age of incomplete submissions in days before they are deleted during garbage collection. Set to `0` to disable automatic deletion.
- `enableCsrfValidationForGuests` enables Craft’s CSRF validation checks for anonymous form submissions.
- `useQueueForNotifications` sends email notifications through Craft’s queue. This is recommended for production sites so form submissions are not slowed down by email delivery.
- `useQueueForIntegrations` sends integrations through Craft’s queue. This is recommended for production sites so form submissions are not slowed down by third-party APIs.
- `queuePriority` sets the Craft queue priority for notification and integration jobs.
- `redirectUri` overrides the OAuth redirect URI for integration connections. When omitted, Formie uses an action URL (`actions/formie/integrations/callback`). Environment variables are supported.
- `paymentWebhookProxyUrl` controls the dev-mode proxy used for payment webhook and return URLs (for example Mollie webhooks and redirect status pages). When omitted in dev mode, Formie uses `https://proxy.verbb.io?return=...`. Set to a custom base URL to use your own tunnel/proxy, or set to an empty string to disable the proxy and use local URLs directly. Ignored when Craft dev mode is off. Environment variables are supported.
- `setOnlyCurrentPagePayload` limits multi-page form payloads to the current page when processing a page request.
- `submissionsBehaviour` controls which submissions are saved. The default is `all`.
- `submissionStateRetentionDays` sets how long incomplete submission state can be kept for save-and-resume and front-end submission state.
- `saveResumeTokenTtlDays` sets how long a save-and-resume token remains valid.
- `maxSavedDraftsPerSession` limits how many saved drafts can be created in one browser session.
- `anonymousClientBootstrapRateLimit` limits anonymous client bootstrap requests within the configured rate window. Set to `0` to disable the limit.
- `anonymousClientRefreshRateLimit` limits anonymous token-refresh requests within the configured rate window. Set to `0` to disable the limit.
- `anonymousClientRateWindowSeconds` sets the rate-limit window used by anonymous client bootstrap and token-refresh requests.

### Security-sensitive runtime settings
- Keep `allowedGraphqlOrigins` as narrow as possible when using headless/runtime forms. Avoid wildcard or broad origins when credentialed requests are allowed.
- Public GraphQL schemas should only include the Formie form and submission scopes required by the front-end consuming them.
- Keep `enableCsrfValidationForGuests` enabled unless you have a specific headless/runtime integration that cannot submit CSRF tokens. Client REST transports should send the CSRF token from `session.tokens.csrf` in the JSON request body when this setting is enabled.
- Store integration API keys and secrets in environment variables (for example `$STRIPE_SECRET_KEY`) rather than plaintext in the database when possible. Formie persists integration settings as JSON in `formie_integrations`; values that use Craft's env syntax are resolved at runtime and are not stored in project config exports.

### Sent Notifications
- `sentNotifications` enables Sent Notifications.
- `maxSentNotificationsAge` sets the number of days to keep sent notifications before they are deleted permanently. Set to `0` to disable automatic deletion.

### Spam
- `saveSpam` saves spam submissions to the database.
- `spamLimit` limits how many saved spam submissions are kept.
- `spamEmailNotifications` allows submissions marked as spam to still trigger email notifications.
- `spamBehaviour` controls what the user sees when a spam submission is detected. Use `showSuccess` or `showMessage`.
- `spamKeywords` marks a submission as spam when the submitted content matches the configured keywords.
- `spamBehaviourMessage` sets the message shown when `spamBehaviour` is `showMessage`. HTML and Markdown are supported.

### Email Notifications
- `sendEmailAlerts` sends an alert email when an email notification fails to send.
- `alertEmails` sets additional email addresses that should receive alert emails. Each entry should be an array with an `email` key. Environment variables are supported.
- `alertEmailsUserGroup` optionally sends alert emails to every user in a Craft user group. Additional `alertEmails` are still sent when configured. At least one of `alertEmails` or `alertEmailsUserGroup` is required when `sendEmailAlerts` is enabled.
- `emptyValuePlaceholder` sets the placeholder used when a field has no submitted value in email output.

### PDFs
- `pdfPaperSize` sets the paper size for generated PDFs.
- `pdfPaperOrientation` sets the paper orientation for generated PDFs.

### Theme
- `themeConfig` sets the default theme configuration used when rendering forms and fields.
- `useCssLayers` outputs Formie’s front-end CSS inside a CSS cascade layer.

### Captchas
- `captchas` stores project-config-backed captcha settings.

### Export
- `defaultExportFolder` sets the default folder used by form export console commands.

### Form groups (project config)

Form groups are managed in the control panel under **Formie → Settings → Form Groups**, but their definitions are stored in project config under `formie.formGroups.{uid}`:

```yaml
formie:
  formGroups:
    7f3e2a1b-0000-4000-8000-000000000001:
      name: Marketing
      handle: marketing
      sortOrder: 1
```

Each entry contains `name`, `handle`, and `sortOrder`. Individual forms store an optional `groupId` in the database; that ID is resolved from the project-config group UID on each environment.

See [Form Groups](/forms/form-groups) for control panel behaviour.

### Reports (project config)

Report definitions and scheduled delivery settings are stored in project config:

```yaml
formie:
  reports:
    a1b2c3d4-0000-4000-8000-000000000001:
      name: Weekly Enquiries
      handle: weeklyEnquiries
      sortOrder: 1
      # filters, columns, display, and export settings…
  scheduledReports:
    b2c3d4e5-0000-4000-8000-000000000002:
      name: Monday summary
      enabled: true
      delivery:
        frequency: weekly
        weekday: 1
        hour: 8
        recipients:
          - team@example.com
```

Each report entry stores its analytical settings. Scheduled report entries store delivery configuration; the linked report is resolved by UID in each environment. Runtime fields such as `lastSentAt` are stored in the database only.

See [Reports](/reports/overview) and [Scheduled reports](/reports/scheduled-reports) for control panel behaviour and cron setup.

## Control Panel
You can also manage many configuration settings through the control panel by visiting **Formie → Settings**. Form, field, and notification defaults are managed on the dedicated **Defaults** settings page.

### Permissions

Formie registers Craft user permissions under **Settings → Users → {user group} → Formie**. For the **Reports** section, assign:

| Permission | Purpose |
| --- | --- |
| **Access reports** | Open **Formie → Reports** and run saved reports |
| **Manage reports** | Create, edit, and delete reports; export data |
| **Manage scheduled reports** | Configure delivery under **Settings → Scheduled Reports** and on a report’s **Scheduled** tab |

Users with **Export submissions** can export from reports without **Manage reports**.

Scheduled email delivery also requires the `./craft formie/reports/run-scheduled` console command on a cron schedule. See [Scheduled reports](/reports/scheduled-reports).

### Alerts Configuration
Supply additional email addresses to receive alert notifications, and optionally set `alertEmailsUserGroup` to a Craft user group UID to send alerts to every user in that group.

```php
'alertEmails' => [
    ['email' => 'admin@site.com'],
    ['email' => '$FORMIE_ALERT_EMAIL'],
],
'alertEmailsUserGroup' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
```

### Theme Configuration
Supply a nested array for the configuration forms and fields should use when rendering.

```php
'themeConfig' => [
    'form' => [
        'attributes' => [
            'class' => 'contact-form',
        ],
    ],
    'field' => [
        'attributes' => [
            'class' => 'contact-form-field',
        ],
    ],
],
```

Continue reading [Theme Config](/theming/theme-config) for more.

## Rich Text Configuration
Formie uses rich-text fields for several form, notification, and field settings. You can control the toolbar buttons and visible rows for those fields by adding a `rich-text.json` file to a `formie` folder in your `/config` directory.

```json
{
    "forms": {
        "errorMessage": {
            "buttons": ["bold"],
            "rows": 3
        }
    }
}
```

This changes the `forms.errorMessage` rich-text field so it only shows the Bold button and uses three rows.

The default rich-text config is:

```json
{
    "forms": {
        "submitActionMessage": {
            "buttons": ["bold", "italic", "variableTag"],
            "rows": 3
        },
        "errorMessage": {
            "buttons": ["bold", "italic"],
            "rows": 3
        },
        "requireUserMessage": {
            "buttons": ["bold", "italic"],
            "rows": 3
        },
        "scheduleFormPendingMessage": {
            "buttons": ["bold", "italic"],
            "rows": 3
        },
        "scheduleFormExpiredMessage": {
            "buttons": ["bold", "italic"],
            "rows": 3
        },
        "limitSubmissionsMessage": {
            "buttons": ["bold", "italic"],
            "rows": 3
        },
        "limitSubmissionsIpAddressMessage": {
            "buttons": ["bold", "italic"],
            "rows": 3
        }
    },
    "fields": {
        "agree": {
            "buttons": ["bold", "italic", "link"],
            "rows": 3
        },
        "instructions": {
            "buttons": ["bold", "italic", "link"],
            "rows": 4
        },
        "builderNote": {
            "buttons": ["bold", "italic", "link"],
            "rows": 3
        },
        "content": {
            "buttons": ["bold", "italic", "underline", "link", "ulist", "olist", "heading2", "heading3", "paragraph"],
            "rows": 8
        },
        "calculations": {
            "buttons": ["variableTag"],
            "rows": 3
        }
    },
    "notifications": {
        "content": {
            "buttons": ["bold", "italic", "variableTag"]
        }
    }
}
```

### Available Buttons
As shown above, your config can provide an array of button names to include in the rich-text field interface.

Button | Description
--- | ---
`bold` | Allows text to be bold.
`italic` | Allows text to be italic.
`underline` | Allows text to be underlined.
`strikethrough` | Allows text to have a strikethrough.
`heading1` | Allows Heading 1 formatting.
`heading2` | Allows Heading 2 formatting.
`paragraph` | Allows Paragraph formatting.
`quote` | Allows Quote formatting.
`olist` | Allows ordered lists.
`ulist` | Allows unordered lists.
`code` | Allows code formatting.
`line` | Adds a horizontal line button.
`link` | Allows links.
`image` | Allows images.
`alignleft` | Allows left alignment.
`aligncenter` | Allows center alignment.
`alignright` | Allows right alignment.
`clear` | Clears formatting.
`variableTag` | Allows variable tags where the field supports them.

```json
{
    "buttons": ["bold", "italic", "link", "variableTag"],
    "rows": 4
}
```

The `fields.content` key controls the **Rich Text** cosmetic field toolbar and height.

The `fields.builderNote` key controls the **Editor Note** rich-text field on the field editor **Advanced** tab.

## HTML Editor Configuration

HTML cosmetic fields use a syntax-highlighted code editor in the form builder. You can control editor height and behaviour by adding an `html.json` file to a `formie` folder in your `/config` directory.

```json
{
    "fields": {
        "html": {
            "rows": 16,
            "tabSize": 4,
            "lineNumbers": true,
            "language": "html"
        }
    }
}
```

### Available settings

Setting | Description
--- | ---
`rows` | Minimum visible editor rows.
`tabSize` | Number of spaces inserted when pressing Tab.
`lineNumbers` | Whether to show a line number gutter.
`language` | Code editor language mode. Currently supports `html` or `text`.
