<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Form;

it('stores and retrieves preview sessions for the current user', function (): void {
    $service = Formie::$plugin->getFormPreview();
    $token = $service->createSession([
        'title' => 'Preview Session',
        'handle' => 'previewSession' . uniqid(),
        'pages' => [],
    ]);

    expect($token)->not->toBeEmpty()
        ->and($service->getSessionBodyParams($token)['title'])->toBe('Preview Session');
});

it('prepares preview forms without custom templates or availability gates', function (): void {
    $form = formie()
        ->form(['title' => 'Preview Availability'])
        ->singleLineTextField('fullName')
        ->create();

    $form->templateId = 999;
    $form->getSettings()->requireUser = true;
    $form->getSettings()->scheduleForm = true;
    $form->getSettings()->limitSubmissions = true;

    $prepared = Formie::$plugin->getFormPreview()->prepareFormForPreview($form);

    expect($prepared->templateId)->toBeNull()
        ->and($prepared->getTemplate())->toBeNull()
        ->and($prepared->getSettings()->requireUser)->toBeFalse()
        ->and($prepared->getSettings()->scheduleForm)->toBeFalse()
        ->and($prepared->getSettings()->limitSubmissions)->toBeFalse();
});

it('renders stock preview HTML for a simple form', function (): void {
    $form = formie()
        ->form(['title' => 'Preview Render'])
        ->singleLineTextField('fullName')
        ->create();

    $token = Formie::$plugin->getFormPreview()->createSession([
        'id' => $form->id,
        'title' => $form->title,
        'handle' => $form->handle,
        'pages' => $form->getFormLayout()->getFormBuilderConfig(),
        'settings' => $form->getSettings()->toArray(),
    ]);

    $preview = Formie::$plugin->getFormPreview()->renderPreviewFrame($token);

    expect($preview['html'])->toContain('data-formie-form')
        ->and($preview['html'])->toContain('fullName')
        ->and($preview['themeCss'] ?? '')->toContain('@layer formie-base')
        ->and($preview['themeCss'] ?? '')->toContain('.formie-form');
});

it('uses stock template paths when useStockTemplates is enabled', function (): void {
    $form = formie()
        ->form(['title' => 'Preview Stock Templates'])
        ->singleLineTextField('fullName')
        ->create();

    Formie::$plugin->getRendering()->pushRenderFrame($form, [
        'useStockTemplates' => true,
    ]);

    $path = Formie::$plugin->getRendering()->getFormComponentTemplatePath($form, 'form');

    Formie::$plugin->getRendering()->popRenderFrame();

    expect($path)->toContain('@verbb/formie/templates/_special/form-template');
});
