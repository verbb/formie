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
            'enabled' => null,
        ],
        'enableUnloadWarning' => true,
        'enableBackSubmission' => true,
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
        'enableLargeFieldStorage' => false,
        'plainTextHtmlSanitizationMode' => 'preserve',

        // Submissions
        'maxIncompleteSubmissionAge' => 30,
        'enableCsrfValidationForGuests' => true,
        'useQueueForNotifications' => true,
        'useQueueForIntegrations' => true,
        'queuePriority' => null,
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

### Forms
- `validateCustomTemplates` checks that custom form template paths exist before they are saved.
- `defaultFormTemplate` sets the default form template handle used for new forms.
- `defaultFormStencil` sets a stencil handle to apply automatically when new forms are created without an explicit stencil.
- `defaultEmailTemplate` sets the default email template handle used for new email notifications.
- `formDefaults` sets structured defaults applied to new forms and stencils, including default submission status, submission title format, privacy settings, submission method, data retention, file-upload deletion behavior, and appearance settings. Leave a value empty or `null` to inherit Formie’s built-in behaviour.
- `fieldDefaults` sets per-field-type defaults applied when new fields are added to a form. Keys are field class names; values are arrays of setting handles and values. Field types opt in via `supportedDefaults()`. Leave a value empty or `null` to inherit Formie’s built-in behaviour.
- `notificationDefaults` sets defaults applied when a new email notification is created. Leave a value empty or `null` to inherit Formie’s built-in behaviour.
- `enableUnloadWarning` shows an unload warning when a user changes a front-end form and tries to leave without submitting.
- `enableBackSubmission` submits the current page content when a user clicks the Back button on a multi-page form.
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
- `fieldDefaults` stores per-field-type default settings. For example, set File Upload and Date defaults with `\verbb\formie\fields\FileUpload::class` and `\verbb\formie\fields\Date::class` as keys. Legacy settings such as `defaultFileUploadVolume`, `defaultDateDisplayType`, `defaultDateValueOption`, and `defaultDateTime` are migrated automatically into `fieldDefaults`.
- `allowPublicVolumes` allows File Upload fields to use public asset volumes.
- `enableLargeFieldStorage` stores field content in large-text database columns for projects that expect very large submission payloads.
- `plainTextHtmlSanitizationMode` controls how plain-text input values are handled when HTML is submitted. Use `preserve` or `sanitize`.

### Submissions
- `maxIncompleteSubmissionAge` sets the maximum age of incomplete submissions in days before they are deleted during garbage collection. Set to `0` to disable automatic deletion.
- `enableCsrfValidationForGuests` enables Craft’s CSRF validation checks for anonymous form submissions.
- `useQueueForNotifications` sends email notifications through Craft’s queue. This is recommended for production sites so form submissions are not slowed down by email delivery.
- `useQueueForIntegrations` sends integrations through Craft’s queue. This is recommended for production sites so form submissions are not slowed down by third-party APIs.
- `queuePriority` sets the Craft queue priority for notification and integration jobs.
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
- Keep `enableCsrfValidationForGuests` enabled unless you have a specific headless/runtime integration that cannot submit CSRF tokens.

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
- `alertEmails` sets the name and email address pairs that should receive alert emails.
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

## Control Panel
You can also manage many configuration settings through the control panel by visiting **Formie → Settings**. Form, field, and notification defaults are managed on the dedicated **Defaults** settings page.

### Alerts Configuration
Supply a nested array for the name and email of each contact to receive alert notifications. The first value should contain the name, and the second value should contain the email address.

```php
'alertEmails' => [
    ['Primary Name', 'admin@site.com'],
    ['Secondary Admin Name', 'admin-alt@site.com'],
],
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
