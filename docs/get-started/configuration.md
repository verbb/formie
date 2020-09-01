# Configuration

Create an `formie.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

The below shows the defaults already used by Formie.

```php
<?php

return [
    '*' => [
        'pluginName' => 'Formie',
        'defaultPage' => 'forms',

        // Submissions
        'maxIncompleteSubmissionAge' => 30,
        'useQueueForNotifications' => true,
        'useQueueForIntegrations' => true,

        // Spam
        'saveSpam' => false,
        'spamLimit' => 500,
        'spamBehaviour' => 'showSuccess',
        'spamKeywords' => '',
        'spamBehaviourMessage' => '',

        // Alerts
        'sendEmailAlerts' => false,
        'alertEmails',
    ]
];
```

### Configuration options
- `pluginName` - Set a custom name for the plugin.
- `defaultPage` - Set the default sub-page navigated to when clicking "Formie" in the main menu.

#### Submissions
- `maxIncompleteSubmissionAge` - The maximum age of an incomplete submission in days before it is deleted in garbage collection. Set to 0 to disable automatic deletion.
- `useQueueForNotifications` - Whether to use Craft‘s queue system to trigger emails. This is highly, **highly** recommended to prevent slow submissions for your users. This may be useful to disable for local development.
- `useQueueForIntegrations` - Whether to use Craft‘s queue system to trigger integrations. This is highly, **highly** recommended to prevent slow submissions for your users. This may be useful to disable for local development.

#### Spam
- `saveSpam` - Whether to save spam submissions to the database.
- `spamLimit` - If saving spam, set a suitable limit for how many to keep. Spam submissions past this limit will be deleted.
- `spamBehaviour` - Set to either `showSuccess` or `showMessage` to set the submission behaviour when a spam submission is detected.
- `spamKeywords` - Set keywords that if matched in the submission, will be marked as spam.
- `spamBehaviourMessage` - This text will be shown as an error after submission. HTML and Markdown is supported.

### Alerts
- `sendEmailAlerts` - Whether an email alert should be sent to a nominated email when an email notification fails to send.
- `alertEmails` - A collection of emails that alerts should be sent to.

## Control Panel

You can also manage configuration settings through the Control Panel by visiting Settings → Formie.

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
`unorderedlist` | Allow the use of `<ul>` elements for an unordered list.
`orderedlist` | Allow the use of `<ol>` elements for an unordered list.
`quote` | Allow text to be shown as a quote.
`link` | Allow text to be shown as a link.
`variableTag` | Allow the use of Variable Tags, to pick variables from form items or global variables.
