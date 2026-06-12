<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\ClientModule;
use verbb\formie\theme\context\RenderContext;

it('registers the file-upload module for simple display type', function (): void {
    $form = formie()
        ->form(['title' => 'Simple File Upload'])
        ->fileUploadField('attachments', [
            'displayType' => 'fileInput',
        ])
        ->create();

    $modules = Formie::$plugin->getClientModuleManifestBuilder()->buildCanonical($form, ClientModule::RENDER_TARGET_FRONTEND);
    $moduleIds = array_map(static fn(array $module): string => (string)($module['id'] ?? ''), $modules);

    expect($moduleIds)->toContain('file-upload')
        ->and($moduleIds)->not->toContain('upload-manager');
});

it('registers the upload-manager module for advanced display type', function (): void {
    $form = formie()
        ->form(['title' => 'Advanced File Upload'])
        ->fileUploadField('attachments', [
            'displayType' => 'uploadManager',
        ])
        ->create();

    $modules = Formie::$plugin->getClientModuleManifestBuilder()->buildCanonical($form, ClientModule::RENDER_TARGET_FRONTEND);
    $uploadManagerModule = current(array_filter($modules, static fn(array $module): bool => ($module['id'] ?? null) === 'upload-manager')) ?: null;

    expect($uploadManagerModule)->not->toBeNull()
        ->and($uploadManagerModule['config']['uploadEndpoint'] ?? null)->toContain('formie/file-upload/upload')
        ->and($uploadManagerModule['config']['deleteEndpoint'] ?? null)->toContain('formie/file-upload/delete')
        ->and($uploadManagerModule['config']['hydrateEndpoint'] ?? null)->toContain('formie/file-upload/hydrate');

    $moduleIds = array_map(static fn(array $module): string => (string)($module['id'] ?? ''), $modules);

    expect($moduleIds)->not->toContain('file-upload');
});

it('defaults file upload fields to the simple display type', function (): void {
    $form = formie()
        ->form(['title' => 'Default File Upload'])
        ->fileUploadField('attachments')
        ->create();

    $field = $form->getFieldByHandle('attachments');

    expect($field->displayType)->toBe('fileInput');
});

it('preserves structural field slot tags for upload manager display type', function (): void {
    $form = formie()
        ->form(['title' => 'Advanced File Upload Slots'])
        ->fileUploadField('attachments', [
            'displayType' => 'uploadManager',
        ])
        ->create();

    $field = $form->getFieldByHandle('attachments');
    $context = RenderContext::from([
        'form' => $form,
        'field' => $field,
    ]);

    $fieldTag = $field->renderSlotTag('field', $context);
    $dropzoneTag = $field->renderSlotTag('fieldDropzone', $context);

    expect($fieldTag?->coreAttributes['data-formie-field'] ?? null)->toBeTrue()
        ->and($fieldTag?->themeAttributes['class'] ?? [])->toContain('formie-field')
        ->and($dropzoneTag?->coreAttributes['data-formie-upload-manager'] ?? null)->toBeTrue();
});
