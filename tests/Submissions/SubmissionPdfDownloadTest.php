<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\PdfEvent;
use verbb\formie\Formie;
use verbb\formie\models\Notification;
use verbb\formie\models\PdfTemplate;
use verbb\formie\services\PdfTemplates;

use yii\base\Event;

function submissionPdfDownloadFixture(bool $attachPdf = true): array
{
    $pdfTemplate = new PdfTemplate([
        'name' => 'Submission PDF',
        'handle' => 'submissionPdf' . uniqid(),
        'template' => '_pdf/submission.twig',
        'filenameFormat' => 'Submission-{submission.id}',
    ]);

    expect(Formie::$plugin->getPdfTemplates()->saveTemplate($pdfTemplate, false))->toBeTrue();

    $form = formie()
        ->form(['title' => 'PDF Download'])
        ->singleLineTextField('fullName')
        ->create();

    $notification = new Notification([
        'name' => 'Admin Notification',
        'handle' => 'adminNotification' . uniqid(),
        'enabled' => true,
        'subject' => 'New submission',
        'to' => 'admin@example.test',
        'from' => 'from@example.test',
        'content' => 'Hello',
        'attachPdf' => $attachPdf,
        'pdfTemplateId' => $pdfTemplate->id,
    ]);

    $form->setNotifications([$notification]);

    $submission = formie()->submission($form)->with(['fullName' => 'Test User'])->save();
    $submission->setForm($form);

    return [
        'pdfTemplate' => $pdfTemplate,
        'form' => $form,
        'notification' => $notification,
        'submission' => $submission,
    ];
}

it('resolves a submission pdf template from attached notifications', function (): void {
    ['submission' => $submission, 'pdfTemplate' => $pdfTemplate] = submissionPdfDownloadFixture();

    $resolved = Formie::$plugin->getPdfTemplates()->resolveSubmissionPdfTemplate($submission);

    expect($resolved?->id)->toBe($pdfTemplate->id);
});

it('resolves a submission pdf template when attachPdf is disabled but a template is configured', function (): void {
    ['submission' => $submission, 'pdfTemplate' => $pdfTemplate] = submissionPdfDownloadFixture(attachPdf: false);

    $resolved = Formie::$plugin->getPdfTemplates()->resolveSubmissionPdfTemplate($submission);

    expect($resolved?->id)->toBe($pdfTemplate->id);
});

it('returns a download url when a pdf template can be resolved', function (): void {
    ['submission' => $submission] = submissionPdfDownloadFixture();

    $url = Formie::$plugin->getSubmissions()->getSubmissionPdfDownloadUrl($submission);

    expect($url)->toBeString()
        ->and($url)->toContain('submissionId=' . $submission->id)
        ->and($url)->toContain('download-pdf');
});

it('returns null download url when no pdf template is configured', function (): void {
    $form = formie()
        ->form(['title' => 'No PDF'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()->submission($form)->with(['fullName' => 'Test User'])->save();

    expect(Formie::$plugin->getSubmissions()->getSubmissionPdfDownloadUrl($submission))->toBeNull();
});

it('generates submission pdfs through the shared render pipeline', function (): void {
    ['submission' => $submission] = submissionPdfDownloadFixture();

    $handler = static function(PdfEvent $event): void {
        $event->pdf = 'stub-pdf';
    };

    Event::on(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_RENDER_PDF, $handler);

    try {
        $pdf = Formie::$plugin->getSubmissions()->generateSubmissionPdf($submission);
    } finally {
        Event::off(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_RENDER_PDF, $handler);
    }

    expect($pdf)->toBe('stub-pdf');
});

it('exposes submission pdf helpers on the submission element', function (): void {
    ['submission' => $submission] = submissionPdfDownloadFixture();

    expect($submission->getPdfDownloadUrl())->toBeString()
        ->and(Formie::$plugin->getSubmissions()->getSubmissionPdfDownloadUrl($submission))->toBe($submission->getPdfDownloadUrl());
});
