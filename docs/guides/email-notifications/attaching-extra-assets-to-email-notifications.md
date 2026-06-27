# Attaching extra assets to Email Notifications

A major part of Formie is email notifications — being able to be notified via email when someone fills out a form. This also includes attachments in three forms:
- When using a File Upload field, you can choose to include any files users have uploaded as attachments.
- PDFs! If you want to custom-design a [PDF with Twig](https://verbb.io/craft-plugins/formie/docs/template-guides/pdf-templates), that can be done too.
- Select any Craft Assets to include as an attachment.

If any of the above doesn't quite cut it, we'll walk through how you can hook into the email-sending process to add your own attachments to the email. This does require knowledge of custom modules, so you'll need to get familiar with [creating a module](/blog/everything-you-need-to-know-about-modules). The code we will be writing will be in PHP and added to our custom module. 

Place the below in your module's `init()` method.

```php
use verbb\formie\events\MailEvent;
use verbb\formie\services\Emails;
use yii\base\Event;

public function init(): void
{
    // ...

    // When Formie is about to send the email, through Craft's mailer service
    Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_MAIL, function(MailEvent $event) {
        $path = '/path/to/my/file';
        $filename = 'My-File.ext';

        // Attach the file to the email
        $event->email->attach($path, ['fileName' => $filename]);
    });

    // ...
}
```

Here, we hook into the `Emails::EVENT_BEFORE_SEND_MAIL` event and attach a file. To add an attachment, we only require two things; the file path and the filename of the file.

If you wanted to use assets, you can fetch them like you normally would.

```php
use craft\elements\Asset;

Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_MAIL, function(MailEvent $event) {
    $asset = Asset::find()->id(1234)->one();

    // Attach the file to the email
    $event->email->attach($asset->path, ['fileName' => $asset->filename]);
});
```

As you might be able to tell, we have access to the `$event->email` which is a [Craft message](https://github.com/craftcms/cms/blob/develop/src/mail/Message.php) which you could use to modify anything else about the email, from the `from`, `to`, `cc`, even the `htmlBody` — but that's for another guide!
