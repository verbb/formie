<?php

use verbb\formie\events\PdfEvent;
use verbb\formie\Formie;
use verbb\formie\models\Notification;
use verbb\formie\models\PdfTemplate;
use verbb\formie\services\Emails;
use verbb\formie\services\PdfTemplates;

use yii\base\Event;

it('uses the rendered email subject in PDF attachment filenames without reparsing submitted text', function (string $firstName): void {
    $form = formie()->form(['title' => 'PDF Subject'])->nameField('contact')->create();
    $submission = formie()->submission($form)->with(['contact' => ['firstName' => $firstName, 'lastName' => 'Example']])->save();
    $notification = new Notification([
        'name' => 'PDF Notice', 'handle' => 'pdfNotice',
        'to' => 'recipient@example.test', 'from' => 'sender@example.test',
        'subject' => 'Registration - {field:contact}', 'content' => 'Thank you',
    ]);
    $notification->setPdfTemplate(new PdfTemplate(['filenameFormat' => '{notification.subject}-{submission.id}']));
    $originalSubject = $notification->subject;
    $emails = new Emails();
    $result = $emails->renderEmail($notification, $submission);

    expect($result)->not->toHaveKey('error');
    $message = $result['email'];
    expect($message->getSubject())->toBe('Registration - ' . $firstName . ' Example');

    $handler = static function(PdfEvent $event): void {
        $event->pdf = 'fixture-pdf';
    };
    Event::on(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_RENDER_PDF, $handler);

    try {
        (new ReflectionMethod(Emails::class, '_attachPdfToEmail'))->invoke($emails, $notification, $message, $submission);
        $attachments = $message->getSymfonyEmail()->getAttachments();
        expect($attachments)->toHaveCount(1)
            ->and($attachments[0]->getFilename())->toBe('Registration - ' . $firstName . " Example-{$submission->id}.pdf")
            ->and($attachments[0]->getBody())->toBe('fixture-pdf')
            ->and($notification->subject)->toBe($originalSubject);
    } finally {
        Event::off(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_RENDER_PDF, $handler);
        foreach ((new ReflectionProperty(Emails::class, '_tempAttachments'))->getValue($emails) as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
})->with(['Jane', '{{ 7 * 7 }}-{submission:id}-${LITERAL_ENV}']);
