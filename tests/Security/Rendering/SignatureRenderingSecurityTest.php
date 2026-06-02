<?php

declare(strict_types=1);

use Craft;
use craft\web\View;

it('does not render submitted signature values as standalone image sources in edit HTML', function (): void {
    $form = formie()
        ->form(['title' => 'Signature Edit Rendering Security'])
        ->signatureField('signature')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['signature' => 'data:image/svg+xml,<svg onload=alert(1)>'])
        ->save();
    $field = $form->getFieldByHandle('signature');
    $view = Craft::$app->getView();
    $oldTemplateMode = $view->getTemplateMode();

    $view->setTemplateMode(View::TEMPLATE_MODE_CP);

    try {
        $html = (string)$field?->getSubmissionHtml($submission->getFieldValue('signature'), $submission);
    } finally {
        $view->setTemplateMode($oldTemplateMode);
    }

    expect($html)
        ->toContain('data-formie-signature-input')
        ->not->toContain('<img src=')
        ->not->toContain('<svg');
})->group('security');

it('strips unsafe signature summary urls before raw image rendering', function (): void {
    $form = formie()
        ->form(['title' => 'Signature Summary Rendering Security'])
        ->signatureField('signature')
        ->create();
    $submission = formie()
        ->submission($form)
        ->with(['signature' => 'data:image/svg+xml,<svg onload=alert(1)>'])
        ->save();
    $field = $form->getFieldByHandle('signature');

    $summary = (string)$field?->getValueForSummary($submission->getFieldValue('signature'), $submission);

    expect($summary)
        ->toContain('accessToken=')
        ->not->toContain('data:image/svg+xml')
        ->not->toContain('<svg');
})->group('security');
