<?php

use Twig\Sandbox\SecurityError;
use verbb\formie\Formie;
use verbb\formie\models\Notification;
use verbb\formie\models\PdfTemplate;

beforeEach(function (): void {
    $this->filenameForm = formie()->form(['title' => 'Filename Form'])->singleLineTextField('fullName')->create();
    $this->filenameSubmission = formie()->submission($this->filenameForm)->with(['fullName' => 'Jane Doe'])->save();
    $this->filenameSubmission->ipAddress = '192.0.2.10';
    $this->filenameNotification = new Notification([
        'name' => 'Admin Notice', 'handle' => 'adminNotice', 'subject' => 'Registration & confirmation',
        'fromName' => 'Example Team', 'to' => 'to@example.test', 'bcc' => 'bcc@example.test',
    ]);
});

it('resolves notification and submission object properties in PDF filenames', function (string $format, string $expected): void {
    $template = new PdfTemplate(['filenameFormat' => $format]);
    expect(Formie::$plugin->getPdfTemplates()->resolveSubmissionPdfFilename($this->filenameSubmission, $template, $this->filenameNotification))->toBe($expected . '.pdf');
})->with([
    ['{notification.subject}', 'Registration & confirmation'],
    ['{notification.name}-{notification.handle}', 'Admin Notice-adminNotice'],
    ['{notification.fromName}-{notification.to}-{notification.bcc}', 'Example Team-to@example.test-bcc@example.test'],
    ['{notification.subject|upper}', 'REGISTRATION & CONFIRMATION'],
    ['{submission.ipAddress}', '192.0.2.10'],
    ['{submission.formName}', 'Filename Form'],
    ['{{ notification.name }}-{{ submission.ipAddress }}', 'Admin Notice-192.0.2.10'],
    ['{field:fullName}-{notification.handle}', 'Jane Doe-adminNotice'],
    ['{submission.fullName}-{notification.handle}', 'Jane Doe-adminNotice'],
    ['{form.name;transform=upper}-{notification.handle}', 'FILENAME FORM-adminNotice'],
    ['{form.name|Fallback}-{notification.handle}', 'Filename Form-adminNotice'],
]);

it('preserves canonical references legacy aliases and formatted dates in PDF filenames', function (): void {
    $submission = $this->filenameSubmission;
    $submission->dateCreated = new DateTime('2026-10-20 12:30:00', new DateTimeZone('UTC'));
    $template = new PdfTemplate(['filenameFormat' => '{submission:id}-{submission.id}-{id}-{submission.formId}-{submission.dateCreated|date("Y-m-d")}-{notification.handle}']);
    expect(Formie::$plugin->getPdfTemplates()->resolveSubmissionPdfFilename($submission, $template, $this->filenameNotification))
        ->toBe("{$submission->id}-{$submission->id}-{$submission->id}-{$submission->formId}-2026-10-20-adminNotice.pdf");
});

it('does not evaluate Twig or references contained in notification values', function (): void {
    $this->filenameNotification->subject = '{{ 7 * 7 }}-{submission:id}-${LITERAL_ENV}';
    $template = new PdfTemplate(['filenameFormat' => '{notification.subject}']);
    expect(Formie::$plugin->getPdfTemplates()->resolveSubmissionPdfFilename($this->filenameSubmission, $template, $this->filenameNotification))
        ->toBe('{{ 7 * 7 }}-{submission:id}-${LITERAL_ENV}.pdf');
});

it('keeps internal properties and methods blocked in PDF filenames', function (string $format): void {
    $template = new PdfTemplate(['filenameFormat' => $format]);
    expect(fn() => Formie::$plugin->getPdfTemplates()->resolveSubmissionPdfFilename($this->filenameSubmission, $template, $this->filenameNotification))
        ->toThrow(SecurityError::class);
})->with([
    '{notification.customSettings}', '{notification.pdfTemplate}',
    '{{ notification.getPdfTemplate() }}', '{{ submission.getCaptchaData("secret") }}',
]);
