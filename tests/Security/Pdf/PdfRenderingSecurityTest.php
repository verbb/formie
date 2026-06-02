<?php

declare(strict_types=1);

use Tests\Support\MaliciousPayloads;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\events\PdfEvent;
use verbb\formie\helpers\References;
use verbb\formie\models\Notification;
use verbb\formie\services\PdfTemplates;

use yii\base\Event;

it('sanitizes parsed notification content before it becomes pdf html', function (): void {
    $form = formie()
        ->form(['title' => 'PDF Rendering Security'])
        ->singleLineTextField('fullName')
        ->create();

    $field = $form->getFieldByHandle('fullName');
    $submission = formie()->submission($form)->with([
        'fullName' => MaliciousPayloads::storedXssProbe(),
    ])->save();

    $notification = new Notification([
        'name' => 'Security PDF',
        'handle' => 'securityPdf' . uniqid(),
        'to' => 'recipient@example.test',
        'from' => 'sender@example.test',
        'subject' => 'Security Subject',
        'content' => References::field((string)$field?->reference),
    ]);

    $capturedContentHtml = null;
    $handler = static function(PdfEvent $event) use (&$capturedContentHtml): void {
        $capturedContentHtml = (string)($event->variables['contentHtml'] ?? '');
        $event->pdf = 'stub-pdf';
    };

    Event::on(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_RENDER_PDF, $handler);

    try {
        $pdf = Formie::$plugin->getPdfTemplates()->renderPdf(null, $submission, $notification);
    } finally {
        Event::off(PdfTemplates::class, PdfTemplates::EVENT_BEFORE_RENDER_PDF, $handler);
    }

    expect($pdf)->toBe('stub-pdf')
        ->and($capturedContentHtml)->toContain('safe-text')
        ->and($capturedContentHtml)->not->toContain('<script')
        ->and($capturedContentHtml)->not->toContain('onerror=');
})->group('security');

it('allows submission attributes and field handles in sandboxed object templates', function (): void {
    $form = formie()
        ->form(['title' => 'PDF Filename Sandbox'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = formie()
        ->submission($form)
        ->with(['fullName' => 'Sandbox User'])
        ->save();

    $allowedProperties = Formie::config()['components']['templates']['allowedProperties'][Submission::class] ?? null;

    expect($allowedProperties)->toBeCallable()
        ->and($allowedProperties($submission, 'id'))->toBeTrue()
        ->and($allowedProperties($submission, 'fullName'))->toBeTrue()
        ->and($allowedProperties($submission, 'field:fullName'))->toBeTrue()
        ->and($allowedProperties($submission, 'notARealField'))->toBeFalse();
})->group('security');
