<?php

use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\events\PdfEvent;
use verbb\formie\helpers\Table;
use verbb\formie\models\Notification;
use verbb\formie\models\PdfTemplate;
use verbb\formie\services\PdfTemplates;

use craft\db\Query;

use yii\base\Event;

it('records a failed notification when its PDF filename cannot be rendered', function (): void {
    $form = formie()->form()->singleLineTextField('company')->create();
    $saved = formie()->submission($form)->with(['company' => 'S Group'])->save();
    $submission = Submission::find()->id($saved->id)->status(null)->one();
    $notification = new Notification([
        'formId' => $form->id,
        'name' => 'With PDF',
        'handle' => 'withPdf',
        'to' => 'staff@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Enquiry',
        'content' => 'Hello',
        'attachPdf' => '1',
        'enabled' => true,
    ]);
    expect(Formie::$plugin->getNotifications()->saveNotification($notification))->toBeTrue();
    $notification->setPdfTemplate(new PdfTemplate(['filenameFormat' => 'Sticker-{submission.form.title}']));
    $handler = static function(PdfEvent $event): void {
        $event->pdf = '%PDF-1.4';
    };
    Event::on(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_RENDER_PDF, $handler);

    try {
        $result = Formie::$plugin->getEmails()->sendEmail($notification, $submission);
    } finally {
        Event::off(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_RENDER_PDF, $handler);
    }

    expect($result)->toHaveKey('error')
        ->and($result)->not->toHaveKey('deliveryOutcomeUnknown')
        ->and((new Query())->select('success')->from(Table::FORMIE_SENT_NOTIFICATIONS)->where(['submissionId' => $submission->id])->scalar())->toBe(0);
});

it('returns an error when a sender cannot be rendered', function (): void {
    $form = formie()->form()->singleLineTextField('company')->create();
    $saved = formie()->submission($form)->with(['company' => 'S Group'])->save();

    $result = Formie::$plugin->getEmails()->renderEmail(new Notification([
        'name' => 'Bad sender',
        'handle' => 'badSender',
        'to' => 'staff@example.test',
        'from' => '{% if form %}sender@example.test{% endif %}',
        'subject' => 'Enquiry',
        'content' => 'Hello',
    ]), Submission::find()->id($saved->id)->status(null)->one());

    expect($result)->toHaveKey('error')
        ->and($result['error'])->toContain('From');
});
