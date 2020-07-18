# Configuration

Create an `formie.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

```php
<?php

return [
    '*' => [
        'pluginName' => 'Formie',
        'defaultPage' => 'forms',
        'maxIncompleteSubmissionAge' => 30,

        // Spam
        'saveSpam' => false,
        'spamLimit' => 500,
        'spamBehaviour' => 'showSuccess',
        'spamKeywords' => '',
        'spamBehaviourMessage' => '',
    ]
];
```

### Configuration options

- `pluginName` - Set a custom name for the plugin.
- `defaultPage` - Set the default sub-page navigated to when clicking "Formie" in the main menu.
- `maxIncompleteSubmissionAge` - The maximum age of an incomplete submission in days before it is deleted in garbage collection. Set to 0 to disable automatic deletion.

#### Spam

- `saveSpam` - Whether to save spam submissions to the database.
- `spamLimit` - If saving spam, set a suitable limit for how many to keep. Spam submissions past this limit will be deleted.
- `spamBehaviour` - Set to either `showSuccess` or `showMessage` to set the submission behaviour when a spam submission is detected.
- `spamKeywords` - Set keywords that if matched in the submission, will be marked as spam.
- `spamBehaviourMessage` - This text will be shown as an error after submission. HTML and Markdown is supported.

## Control Panel

You can also manage configuration settings through the Control Panel by visiting Settings → Formie.
