# Attaching Extra Assets to Email Notifications

Sometimes a notification needs a file that is not tied to a form field — a terms PDF, a static brochure, or a generated document from elsewhere in Craft. Formie covers the common attachment cases built in; this guide shows how to attach your own files when you need something more custom.

Before writing a module, check the attachment settings in [Email Notifications](/forms/email-notifications) to see whether Formie already covers your case:

## Built-in Attachment Options

- **File Upload fields** — include uploaded files as attachments
- **PDF templates** — generate a PDF with Twig and attach it
- **Craft Assets** — pick assets to attach in the notification settings

## Attach a File in a Module

Add this to your module's `init()` method:

```php
use verbb\formie\events\MailEvent;
use verbb\formie\services\Emails;
use yii\base\Event;

public function init(): void
{
    parent::init();

    Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_MAIL, function(MailEvent $event) {
        $path = '/path/to/my/file';
        $filename = 'My-File.ext';

        $event->email->attach($path, ['fileName' => $filename]);
    });
}
```

You need the file path and the filename Formie should use in the attachment.

## Attach a Craft Asset

```php
use craft\elements\Asset;
use verbb\formie\events\MailEvent;
use verbb\formie\services\Emails;
use yii\base\Event;

Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_MAIL, function(MailEvent $event) {
    $asset = Asset::find()->id(1234)->one();

    if (!$asset) {
        return;
    }

    $event->email->attach($asset->getCopyOfFile(), ['fileName' => $asset->filename]);
});
```

## What Else You Can Change

`$event->email` is Craft's message object. You can also adjust **from**, **to**, **cc**, or **htmlBody** in the same event if needed.
