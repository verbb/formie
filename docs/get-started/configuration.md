# Configuration

You can customise Formie’s settings using a PHP configuration file. This is optional: each setting has a default, so you only need to include the values you want to change.

To override a setting, create `formie.php` in your Craft project’s `/config` directory and return an array of setting names and values. For example, the following will set the Ajax request timeout to 30 seconds:

```php
<?php

return [
    'ajaxTimeout' => 30,
];
```

All other settings keep their defaults. Add any further settings you want to change to the same array. The options below explain the available settings and their defaults.

::: tip
For project config, environment variables, and control panel settings across staging and production, see [Project config, environment, and control panel settings](/guides/configuration/project-config-environment-and-control-panel-settings).
:::

## Configuration Options

::: reference
### `pluginName`

**Type:** `string` · **Default:** `'Formie'`

Sets a custom name for the plugin.
:::


::: reference
### `defaultPage`

**Type:** `string` · **Default:** `'forms'`

Sets the default Formie control panel page when clicking Formie in the main navigation.
:::


::: reference
### `compatibilityMode`

**Type:** `bool` · **Default:** `true`

Enables compatibility shims for older Formie APIs during an upgrade.
:::


::: reference
### `staticCacheRefreshOnLoad`

**Type:** `bool` · **Default:** `false`

Allows rendered forms to refresh request-specific values when initialised on statically cached pages. Formie also treats this as enabled when Blitz is installed and enabled.
:::


::: reference
### `allowedSubmitMethods`

**Type:** `string` · **Default:** `self::ALLOWED_SUBMIT_METHODS_BOTH`

Restricts which submission methods are available in the form builder: `both` (default), `ajax`, or `page-reload`. Payment integrations that require Ajax still force Ajax when applicable.
:::


### Forms

::: reference
#### `validateCustomTemplates`

**Type:** `bool` · **Default:** `true`

Checks that custom form template paths exist before they are saved.
:::


::: reference
#### `defaultFormTemplate`

**Type:** `string` · **Default:** `''`

Sets the default form template handle used for new forms.
:::


::: reference
#### `defaultFormStencil`

**Type:** `string` · **Default:** `''`

Sets a stencil handle to apply automatically when new forms are created without an explicit stencil.
:::


::: reference
#### `defaultEmailTemplate`

**Type:** `string` · **Default:** `''`

Sets the default email template handle used for new email notifications.
:::


::: reference
#### `formDefaults`

**Type:** `array` · **Default:** `[]`

Sets structured defaults applied to new forms and stencils, including default submission status, submission title format, privacy settings, submission method, data retention, file-upload deletion behaviour, and appearance settings. Leave a value empty or `null` to inherit Formie’s built-in behaviour.
:::





::: reference
#### `notificationDefaults`

**Type:** `array` · **Default:** `[]`

Sets defaults applied when a new email notification is created. Leave a value empty or `null` to inherit Formie’s built-in behaviour.
:::


::: reference
#### `integrationDefaults`

**Type:** `array` · **Default:** `[]`

Controls default captcha integration states for new forms and stencils. Use `captchas[handle]` with `null` to inherit each integration’s global enabled state, or `true`/`false` to force enable or disable.
:::


::: reference
#### `enableUnloadWarning`

**Type:** `bool` · **Default:** `true`

Shows an unload warning when a user changes a front-end form and tries to leave without submitting.
:::


::: reference
#### `errorAriaLive`

**Type:** `string` · **Default:** `self::ERROR_ARIA_LIVE_POLITE`

Controls how front-end validation and submit errors are announced to screen readers. Use `polite` (default), `assertive`, or `off` for visual-only errors. Live validation while typing always uses polite announcements; submit-time errors use this setting.
:::


::: reference
#### `enableBackSubmission`

**Type:** `bool` · **Default:** `true`

Submits the current page content when a user clicks the Back button on a multi-page form.
:::


::: reference
#### `enableMultiPageForms`

**Type:** `bool` · **Default:** `true`

Controls whether forms can contain multiple pages in the form builder. When disabled, authors cannot add pages and forms with more than one page cannot be saved.
:::


::: reference
#### `ajaxTimeout`

**Type:** `int` · **Default:** `10`

Sets the timeout in seconds for Ajax requests made by Formie’s front-end JavaScript.
:::


::: reference
#### `filterIntegrationMapping`

**Type:** `bool` · **Default:** `true`

Filters field-mapping options shown in integrations to fields that are usually suitable for the target setting.
:::


::: reference
#### `includeDraftElementUsage`

**Type:** `bool` · **Default:** `false`

Includes draft elements when Formie checks where a form is used.
:::


::: reference
#### `includeRevisionElementUsage`

**Type:** `bool` · **Default:** `false`

Includes revision elements when Formie checks where a form is used.
:::


::: reference
#### `outputConsoleMessages`

**Type:** `bool` · **Default:** `true`

Controls whether Formie’s front-end JavaScript can output console messages.
:::


### General Fields

::: reference
#### `disabledFields`

**Type:** `array` · **Default:** `[]`

Is an array of field classes that should be disabled and unavailable in the form builder.
:::


::: reference
#### `defaultLabelPosition`

**Type:** `string` · **Default:** `AboveInput::class`

Sets the default label position for new forms and fields.
:::


::: reference
#### `defaultInstructionsPosition`

**Type:** `string` · **Default:** `AboveInput::class`

Sets the default instruction position for new forms and fields.
:::


### Fields

::: reference
#### `fieldDefaults`

**Type:** `array` · **Default:** `[]`

Sets per-field-type defaults applied when new fields are added to a form. Keys are field class names; values are arrays of setting handles and values. Field types opt in via `supportedDefaults()`. Leave a value empty or `null` to inherit Formie’s built-in behaviour. For example, set File Upload, Date, Phone, Agree, Email, Number, element field, and other supported field defaults with their field class names as keys. Custom field types can opt in via [Field Defaults](/developers/field-defaults).
:::


::: reference
#### `allowPublicVolumes`

**Type:** `bool` · **Default:** `true`

Allows File Upload fields to use public asset volumes, and controls whether “Public URL” is available as an email summary value. Configure in **Settings → Fields**.
:::


::: reference
#### `allowMultiSelectDropdowns`

**Type:** `bool` · **Default:** `true`

Controls whether form editors can enable “Allow Multiple” on Dropdown and element fields using a dropdown display type. When disabled, the setting is hidden in the form builder and existing values are forced off. Configure in **Settings → Fields**.
:::


::: reference
#### `allowPhoneCountrySelector`

**Type:** `bool` · **Default:** `true`

Controls whether form editors can enable the country code selector on Phone Number fields. When disabled, the setting is hidden in the form builder and existing values are forced off. For default-off behaviour on new phone fields without hiding the setting, use [Field Defaults](/developers/field-defaults) (`countryEnabled: false`). Configure in **Settings → Fields**.
:::


::: reference
#### `enableLargeFieldStorage`

**Type:** `bool` · **Default:** `false`

Stores field content in large-text database columns for projects that expect very large submission payloads.
:::


::: reference
#### `includeFlatpickrCss`

**Type:** `bool` · **Default:** `true`

Controls whether Formie injects Flatpickr styles for Calendar (Advanced) date fields. Set to `false` when your project already provides its own Flatpickr stylesheet.
:::


::: reference
#### `plainTextHtmlSanitizationMode`

**Type:** `string` · **Default:** `self::PLAIN_TEXT_HTML_SANITIZATION_MODE_PRESERVE`

Controls how plain-text input values are handled when HTML is submitted. Use `preserve` or `sanitize`.
:::


### Submissions

::: reference
#### `maxIncompleteSubmissionAge`

**Type:** `int` · **Default:** `30`

Sets the maximum age of incomplete submissions in days before they are deleted by scheduled cleanup. Set to `0` to disable automatic deletion.
:::


::: reference
#### `enableCsrfValidationForGuests`

**Type:** `bool` · **Default:** `true`

Enables Craft’s CSRF validation checks for anonymous form submissions.
:::


::: reference
#### `useQueueForNotifications`

**Type:** `bool` · **Default:** `true`

Sends email notifications through Craft’s queue. This is recommended for production sites so form submissions are not slowed down by email delivery.
:::


::: reference
#### `useQueueForIntegrations`

**Type:** `bool` · **Default:** `true`

Sends integrations through Craft’s queue. This is recommended for production sites so form submissions are not slowed down by third-party APIs.
:::


::: reference
#### `queuePriority`

**Type:** `int|null` · **Default:** `null`

Sets the Craft queue priority for notification and integration jobs.
:::


::: reference
#### `redirectUri`

**Type:** `string|null` · **Default:** `null`

Overrides the OAuth redirect URI for integration connections. When omitted, Formie uses an action URL (`actions/formie/integrations/callback`). Environment variables are supported.
:::


::: reference
#### `paymentWebhookProxyUrl`

**Type:** `string|null` · **Default:** `null`

Controls the dev-mode proxy used for payment webhook and return URLs (for example Mollie webhooks and redirect status pages). When omitted in dev mode, Formie uses `https://proxy.verbb.io?return=...`. Set to a custom base URL to use your own tunnel/proxy, or set to an empty string to disable the proxy and use local URLs directly. Ignored when Craft dev mode is off. Environment variables are supported.
:::


::: reference
#### `setOnlyCurrentPagePayload`

**Type:** `bool` · **Default:** `false`

Limits multi-page form payloads to the current page when processing a page request.
:::


::: reference
#### `submissionsBehaviour`

**Type:** `string|array` · **Default:** `'all'`

Controls which submissions are saved. The default is `all`.
:::


::: reference
#### `submissionStateRetentionDays`

**Type:** `int` · **Default:** `30`

Sets how long incomplete submission state can be kept for save-and-resume and front-end submission state.
:::


::: reference
#### `saveResumeTokenTtlDays`

**Type:** `int` · **Default:** `14`

Sets how long a save-and-resume token remains valid.
:::


::: reference
#### `maxSavedDraftsPerSession`

**Type:** `int` · **Default:** `10`

Limits how many saved drafts can be created in one browser session.
:::


::: reference
#### `anonymousClientBootstrapRateLimit`

**Type:** `int` · **Default:** `30`

Limits anonymous client bootstrap requests within the configured rate window. Set to `0` to disable the limit.
:::


::: reference
#### `anonymousClientRefreshRateLimit`

**Type:** `int` · **Default:** `120`

Limits anonymous token-refresh requests within the configured rate window. Set to `0` to disable the limit.
:::


::: reference
#### `anonymousClientRateWindowSeconds`

**Type:** `int` · **Default:** `60`

Sets the rate-limit window used by anonymous client bootstrap and token-refresh requests.
:::


### Security-Sensitive Settings
- Keep `allowedGraphqlOrigins` as narrow as possible when using headless forms. Avoid wildcard or broad origins when credentialed requests are allowed.
- Public GraphQL schemas should only include the Formie form and submission scopes required by the front-end consuming them.
- Keep `enableCsrfValidationForGuests` enabled unless you have a specific headless integration that cannot submit CSRF tokens. Client REST transports should send the CSRF token from `session.tokens.csrf` in the JSON request body when this setting is enabled.
- Store integration API keys and secrets in environment variables (for example `$STRIPE_SECRET_KEY`) rather than plaintext in the database when possible. Formie persists integration settings as JSON in `formie_integrations`; values that use Craft's env syntax are resolved when settings are loaded and are not stored in project config exports.

### Sent Notifications

::: reference
#### `sentNotifications`

**Type:** `bool` · **Default:** `true`

Enables Sent Notifications.
:::


::: reference
#### `maxSentNotificationsAge`

**Type:** `int` · **Default:** `30`

Sets the number of days to keep sent notifications before they are deleted by scheduled cleanup. Set to `0` to disable automatic deletion.
:::


### Spam

::: reference
#### `saveSpam`

**Type:** `bool` · **Default:** `true`

Saves spam submissions to the database.
:::


::: reference
#### `spamLimit`

**Type:** `int` · **Default:** `500`

Limits how many saved spam submissions are kept.
:::


::: reference
#### `spamEmailNotifications`

**Type:** `bool` · **Default:** `false`

Allows submissions marked as spam to still trigger email notifications.
:::


::: reference
#### `spamBehaviour`

**Type:** `string` · **Default:** `self::SPAM_BEHAVIOUR_SUCCESS`

Controls what the user sees when a spam submission is detected. Use `showSuccess` or `showMessage`.
:::


::: reference
#### `spamKeywords`

**Type:** `string` · **Default:** `''`

Marks a submission as spam when the submitted content matches the configured keywords.
:::


::: reference
#### `spamBehaviourMessage`

**Type:** `string` · **Default:** `''`

Sets the message shown when `spamBehaviour` is `showMessage`. HTML and Markdown are supported.
:::


### Email Notifications

::: reference
#### `sendEmailAlerts`

**Type:** `bool` · **Default:** `false`

Sends an alert email when an email notification fails to send.
:::


::: reference
#### `alertEmails`

**Type:** `array|null` · **Default:** `null`

Sets additional email addresses that should receive alert emails. Each entry should be an array with an `email` key. Environment variables are supported.
:::


::: reference
#### `alertEmailsUserGroup`

**Type:** `string|null` · **Default:** `null`

Optionally sends alert emails to every user in a Craft user group. Additional `alertEmails` are still sent when configured. At least one of `alertEmails` or `alertEmailsUserGroup` is required when `sendEmailAlerts` is enabled.
:::


::: reference
#### `emptyValuePlaceholder`

**Type:** `string` · **Default:** `'No response.'`

Sets the placeholder used when a field has no submitted value in email output.
:::


### PDFs

::: reference
#### `pdfPaperSize`

**Type:** `string` · **Default:** `'letter'`

Sets the paper size for generated PDFs.
:::


::: reference
#### `pdfPaperOrientation`

**Type:** `string` · **Default:** `'portrait'`

Sets the paper orientation for generated PDFs.
:::


### Theme

::: reference
#### `themeConfig`

**Type:** `array` · **Default:** `[]`

Sets the default theme configuration used when rendering forms and fields.
:::


::: reference
#### `useCssLayers`

**Type:** `bool` · **Default:** `false`

Outputs Formie’s front-end CSS inside a CSS cascade layer.
:::


### Captchas

::: reference
#### `captchas`

**Type:** `array` · **Default:** `[]`

Stores project-config-backed captcha settings.
:::


### Export

::: reference
#### `defaultExportFolder`

**Type:** `string` · **Default:** `'@storage/formie-export'`

Sets the default folder used by form export console commands.
:::


### Form Groups (Project Config)

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

### Reports (Project Config)

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

Each report entry stores its analytical settings. Scheduled report entries store delivery configuration; the linked report is resolved by UID in each environment. Database-only fields such as `lastSentAt` are stored in the database only.

See [Reports](/reports/reports) and [Scheduled reports](/reports/scheduled-reports) for control panel behaviour and cron setup.

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

Scheduled email delivery requires a cron schedule. Use `./craft formie/cron/run` (recommended) or `./craft formie/reports/run-scheduled`. See [Scheduled reports](/reports/scheduled-reports).

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
        "question": {
            "buttons": ["bold", "italic", "link", "unordered-list", "ordered-list"],
            "rows": 4
        },
        "content": {
            "buttons": ["bold", "italic", "underline", "link", "unordered-list", "ordered-list", "h2", "h3", "paragraph"],
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
`h1`–`h6` | Applies the corresponding heading level.
`paragraph` | Allows Paragraph formatting.
`blockquote` | Allows blockquote formatting.
`ordered-list` | Allows ordered lists.
`unordered-list` | Allows unordered lists.
`code` | Allows inline code formatting.
`code-block` | Allows code-block formatting.
`subscript` | Applies subscript formatting.
`superscript` | Applies superscript formatting.
`small-caps` | Applies small caps through TipTap's TextStyle mark.
`highlight` | Highlights text.
`hr` | Inserts a horizontal rule.
`line-break` | Inserts a hard line break.
`link` | Allows links.
`table` | Inserts a table.
`align-left` | Allows left alignment.
`align-center` | Allows center alignment.
`align-right` | Allows right alignment.
`align-justify` | Allows justified alignment.
`clear-format` | Clears formatting.
`undo` | Undoes the latest change.
`redo` | Redoes the latest undone change.
`font-family` | Opens the font-family TextStyle menu.
`font-size` | Opens the font-size TextStyle menu.
`text-color` | Opens text and background color TextStyle menus.
`line-height` | Opens the line-height TextStyle menu.
`variableTag` | Allows variable tags where the field supports them.

```json
{
    "buttons": ["bold", "italic", "link", "variableTag"],
    "rows": 4
}
```

The `fields.content` key controls the **Rich Text** cosmetic field toolbar and height.

The `fields.builderNote` key controls the **Editor Note** rich-text field on the field editor **Advanced** tab.

### Text Styles

Font family, font size, text color, background color, line height, and small caps are built into Formie's rich-text schema. They are opt-in toolbar controls, so adding them does not change existing Formie toolbars:

```json
{
    "fields": {
        "content": {
            "buttons": [
                "font-family",
                "font-size",
                "bold",
                "italic",
                "small-caps",
                "text-color",
                "line-height",
                "link"
            ],
            "textStyleOptions": {
                "fontFamilies": [
                    { "label": "Default font", "value": null },
                    { "label": "Brand Sans", "value": "Brand Sans, sans-serif" },
                    { "label": "Georgia", "value": "Georgia, serif" }
                ],
                "fontSizes": [
                    { "label": "Default", "value": null },
                    { "label": "Small", "value": "14px" },
                    { "label": "Body", "value": "16px" },
                    { "label": "Large", "value": "24px" }
                ]
            }
        }
    }
}
```

Omit `textStyleOptions` to use Plugin Kit's default choices. Any option family you provide replaces that family of defaults for the configured Formie field.

### Extending the Rich-text Editor

Formie does not register project-specific TipTap nodes, marks, or extensions by default, but modules and plugins can register them before the form builder mounts. For a complete Formie example—including matching PHP and JavaScript extensions, a toolbar control, and asset loading—see [TipTap Extensions](/developers/tiptap-extensions).

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

### Available Settings

Setting | Description
--- | ---
`rows` | Minimum visible editor rows.
`tabSize` | Number of spaces inserted when pressing Tab.
`lineNumbers` | Whether to show a line number gutter.
`language` | Code editor language mode. Currently supports `html` or `text`.
