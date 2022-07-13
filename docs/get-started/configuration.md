# Configuration
Create a `formie.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these settings per-environment.

The below shows the defaults already used by Formie, so you don't need to add these options unless you want to modify the values.

```php
<?php

return [
    '*' => [
        'pluginName' => 'Formie',
        'defaultPage' => 'forms',

        // Forms
        'defaultFormTemplate' => '',
        'defaultEmailTemplate' => '',
        'enableUnloadWarning' => true,
        'enableBackSubmission' => true,
        'ajaxTimeout' => 10,

        // General Fields
        'disabledFields' => [],
        'defaultLabelPosition' => 'above-input',
        'defaultInstructionsPosition' => 'below-input',

        // Fields
        'defaultFileUploadVolume' => '',
        'defaultDateDisplayType' => '',
        'defaultDateValueOption' => '',
        'defaultDateTime' => '',

        // Submissions
        'maxIncompleteSubmissionAge' => 30,
        'enableCsrfValidationForGuests' => true,
        'useQueueForNotifications' => true,
        'useQueueForIntegrations' => true,
        'queuePriority' => null,

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

        // Alerts
        'sendEmailAlerts' => false,
        'alertEmails' => [],

        // PDFs
        'pdfPaperSize' => 'letter',
        'pdfPaperOrientation' => 'portrait',

        // Theme
        'themeConfig' = [],
    ]
];
```

## Configuration options
- `pluginName` - Set a custom name for the plugin.
- `defaultPage` - Set the default sub-page navigated to when clicking "Formie" in the main menu.

### Forms
- `defaultFormTemplate` - The handle for the default form template used for new forms. Formie‘s defaults will be used if not specified.
- `defaultEmailTemplate` - The handle for the default email template used for new forms. Formie‘s defaults will be used if not specified.
- `enableUnloadWarning` - Whether front-end forms should trigger an "unload" warning when a form‘s content has changed and the user tries to navigate away without submitting.
- `enableBackSubmission` - Whether clicking the "Back" button on front-end forms should submit the current page content. Disabling this will show an "unload" warning and discard any un-saved content.
- `ajaxTimeout` - Set the timeout in seconds for Ajax/XHR requests when using the front-end JS. Default to 10 seconds.

### General Fields
- `disabledFields` - An array of field classes that should be disabled, and un-selectable in the form builder.
- `defaultLabelPosition` - The default label position for new forms and fields.
- `defaultInstructionsPosition` - The default instruction position for new forms and fields.

### Fields
- `defaultFileUploadVolume` - The asset volume to be used as the default for all new file upload fields. Must be in the format `folder:uid`.
- `defaultDateDisplayType` - The display type to be used as the default for all new date fields. Can be `calendar`, `dropdowns`, `inputs`. 
- `defaultDateValueOption` - The default value option to be used as the default for all new date fields. Can be `today`, `date`. 
- `defaultDateTime` - When `defaultDateValueOption` is set to `date`, this date will be used as the default value. Must be a valid datetime.

### Submissions
- `maxIncompleteSubmissionAge` - The maximum age of an incomplete submission in days before it is deleted in garbage collection. Set to 0 to disable automatic deletion.
- `enableCsrfValidationForGuests` - Whether to enable Craft‘s CSRF validation checks for anonymous form submissions.
- `useQueueForNotifications` - Whether to use Craft‘s queue system to trigger emails. This is highly, **highly** recommended, to prevent slow submissions for your users. This may be useful to disable for local development.
- `useQueueForIntegrations` - Whether to use Craft‘s queue system to trigger integrations. This is highly, **highly** recommended, to prevent slow submissions for your users. This may be useful to disable for local development.
- `queuePriority` - Set the queue job priority, to determine if it should run with a different priority compared to other jobs. Default to the [Craft default](https://craftcms.com/docs/4.x/extend/queue-jobs.html#specifying-priority) of `1024`.

### Sent Notifications
- `sentNotifications` - Whether to enable Sent Notifications functionality.
- `maxSentNotificationsAge` - The number of days to keep sent notifications before they are deleted permanently. Set to 0 to disable automatic deletion.

### Spam
- `saveSpam` - Whether to save spam submissions to the database.
- `spamLimit` - If saving spam, set a suitable limit for how many to keep. Spam submissions past this limit will be deleted.
- `spamEmailNotifications` - Whether submissions marked as spam should still trigger email notifications.
- `spamBehaviour` - Set to either `showSuccess` or `showMessage` to set the submission behaviour when a spam submission is detected.
- `spamKeywords` - Set keywords that if matched in the submission, will be marked as spam.
- `spamBehaviourMessage` - This text will be shown as an error after submission. HTML and Markdown is supported.

### Alerts
- `sendEmailAlerts` - Whether an email alert should be sent to a nominated email when an email notification fails to send.
- `alertEmails` - A collection of emails that alerts should be sent to. See below for an example.

### PDFs
- `pdfPaperSize` - Sets the paper size for the PDF used in Email Notifications.
- `pdfPaperOrientation` - Sets the paper orientation for the PDF used in Email Notifications.

### Theme
- `themeConfig` - Sets the configuration for theming your form and fields. See below for an example.

## Control Panel

You can also manage configuration settings through the Control Panel by visiting Settings → Formie.

### Alerts Configuration
Supply a nested array for the name and email of each contact to receive alert notifications. The first index should contain the name, with the second index the email address.

```php
'alertEmails' => [
    ['Primary Name', 'admin@site.com'],
    ['Secondary Admin Name', 'admin-alt@site.com'],
],
```

### Theme Configuration
Supply a nested array for the configuration form and fields should use when rendering.

```php
'themeConfig' => [
    'form' => [
        'class' => 'border border-red-500',
    ],
    'field' => [
        'class' => 'uppercase',
    ],
],
```

Continue reading the [theming](docs:theming) docs for more.

## Rich Text Configuration
Formie uses a Rich Text field for numerous settings for forms, notifications and more. This field is powered by [TipTap](https://tiptap.scrumpy.io/). You have control over the configuration of these Rich Text fields, by providing a `.json` file with its configurations, very similar to how the [Redactor](https://plugins.craftcms.com/redactor) plugin works.

For example, create a `formie` folder in your `/config` directory, and inside that, create a `rich-text.json` file. Place the following content into that file:

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

Here, we're setting the `forms.errorMessage` field config to provide a single button for Bold, and the number of rows the field should show. There are a number of available fields to configure, shown by the default config below:

```json
{
    "forms": {
        "submitActionMessage": {
            "buttons": ["bold", "italic"],
            "rows": 3
        },
        "errorMessage": {
            "buttons": ["bold", "italic"],
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
As shown above, your config can provide an array of button configs to include or exclude certain buttons from the Rich Text field interface. It's a good idea to only allow the types of formatting and functionality you want users to have access to.

Button | Description
--- | ---
`h1` | Allow the use of `<h1>` heading tags.
`h2` | Allow the use of `<h2>` heading tags.
`h3` | Allow the use of `<h3>` heading tags.
`h4` | Allow the use of `<h4>` heading tags.
`h5` | Allow the use of `<h5>` heading tags.
`h6` | Allow the use of `<h6>` heading tags.
`bold` | Allow text to be bold.
`italic` | Allow text to be italic.
`underline` | Allow text to be underlined.
`strikethrough` | Allow text to have a strikethrough.
`subscript` | Allow text to be subscript.
`superscript` | Allow text to be superscript.
`unordered-list` | Allow the use of `<ul>` elements for an unordered list.
`ordered-list` | Allow the use of `<ol>` elements for an unordered list.
`blockquote` | Allow text to be shown as a blockquote.
`highlight` | Allow text to be highlighted.
`code` | Allow text to be shown as inline code.
`code-block` | Allow text to be shown as a code blocks.
`hr` | Allow the use of a `<hr>` element for a horizontal rule.
`line-break` | Allow the use of a `<br>` element for a horizontal rule.
`align-left` | Allow text to be left-aligned.
`align-center` | Allow text to be center-aligned.
`align-right` | Allow text to be right-aligned.
`align-justify` | Allow text to be justify.
`clear-format` | Allow all formatting to be cleared.
`undo` | Allow undo functionality.
`redo` | Allow redo functionality.
`link` | Allow text to be shown as a link.
`variableTag` | Allow the use of Variable Tags, to pick variables from form items or global variables.
