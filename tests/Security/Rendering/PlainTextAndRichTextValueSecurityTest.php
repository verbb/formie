<?php

declare(strict_types=1);

use craft\web\View;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\models\Settings;

it('stores plain text values raw and escapes them when rendering single-line inputs', function (): void {
    $payload = 'Tom & Jerry • 3 < 5 • 10 > 2 • "quotes" • \'apostrophe\' • café • 日本語';
    $form = formie()
        ->form(['title' => 'Plain Text Storage Rendering Security'])
        ->singleLineTextField('fullName')
        ->create();

    $submission = new Submission();
    $submission->setForm($form);
    $submission->setFieldValueFromRequest('fullName', $payload);
    $form->setCurrentSubmission($submission);

    $field = $form->getFieldByHandle('fullName');
    $view = Craft::$app->getView();
    $oldTemplateMode = $view->getTemplateMode();
    $view->setTemplateMode(View::TEMPLATE_MODE_CP);

    try {
        $inputHtml = (string)$field?->renderInput($form, $submission->getFieldValue('fullName'));
    } finally {
        $view->setTemplateMode($oldTemplateMode);
    }

    expect($submission->getFieldValue('fullName'))->toBe($payload)
        ->and($field?->getValueAsString($submission->getFieldValue('fullName'), $submission))->toBe($payload)
        ->and($inputHtml)->toContain('value="Tom &amp; Jerry')
        ->and($inputHtml)->toContain('3 &lt; 5')
        ->and($inputHtml)->toContain('10 &gt; 2')
        ->and($inputHtml)->not->toContain('&amp;amp;');
})->group('security');

it('sanitizes multi-line rich text values while preserving safe html', function (): void {
    $form = formie()
        ->form(['title' => 'Multi Line Rich Text Security'])
        ->multiLineTextField('bio', [
            'useRichText' => true,
        ])
        ->create();

    $submission = new Submission();
    $submission->setForm($form);

    $field = $form->getFieldByHandle('bio');
    $payload = '<script>alert("xss")</script><p>safe-text</p><img src=x onerror=alert("xss")>';
    $normalized = $field?->normalizeValue($payload, $submission);
    $valueAsString = $field?->getValueAsString($normalized, $submission);

    expect($normalized)->toBeString()
        ->and($normalized)->toContain('safe-text')
        ->and($normalized)->not->toContain('<script')
        ->and($normalized)->not->toContain('onerror=')
        ->and($valueAsString)->toContain('safe-text')
        ->and($valueAsString)->not->toContain('<script')
        ->and($valueAsString)->not->toContain('onerror=');
})->group('security');

it('can opt into sanitizing plain-text values before storage', function (): void {
    $settings = Formie::$plugin->getSettings();
    $originalPolicy = $settings->plainTextHtmlSanitizationMode;
    $settings->plainTextHtmlSanitizationMode = Settings::PLAIN_TEXT_HTML_SANITIZATION_MODE_SANITIZE;

    $payload = 'Tom & Jerry • 3 < 5 <script>alert("xss")</script><img src=x onerror=alert("xss")><p>safe-text</p>';

    try {
        $form = formie()
            ->form(['title' => 'Plain Text Sanitized Storage Security'])
            ->singleLineTextField('fullName')
            ->create();

        $submission = new Submission();
        $submission->setForm($form);
        $submission->setFieldValueFromRequest('fullName', $payload);
        $form->setCurrentSubmission($submission);

        $field = $form->getFieldByHandle('fullName');
        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        try {
            $inputHtml = (string)$field?->renderInput($form, $submission->getFieldValue('fullName'));
        } finally {
            $view->setTemplateMode($oldTemplateMode);
        }

        expect($submission->getFieldValue('fullName'))->toContain('Tom & Jerry')
            ->and($submission->getFieldValue('fullName'))->toContain('3 < 5')
            ->and($submission->getFieldValue('fullName'))->toContain('safe-text')
            ->and($submission->getFieldValue('fullName'))->not->toContain('<script')
            ->and($submission->getFieldValue('fullName'))->not->toContain('alert("xss")')
            ->and($submission->getFieldValue('fullName'))->not->toContain('onerror=')
            ->and($submission->getFieldValue('fullName'))->not->toContain('<p>')
            ->and($inputHtml)->toContain('Tom &amp; Jerry')
            ->and($inputHtml)->toContain('3 &lt; 5')
            ->and($inputHtml)->toContain('safe-text')
            ->and($inputHtml)->not->toContain('&amp;lt;')
            ->and($inputHtml)->not->toContain('<script');
    } finally {
        $settings->plainTextHtmlSanitizationMode = $originalPolicy;
    }
})->group('security');
