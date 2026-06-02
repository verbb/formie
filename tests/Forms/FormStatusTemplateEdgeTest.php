<?php

declare(strict_types=1);

use verbb\formie\elements\Form;

it('assigns default template and status on new form save', function (): void {
    $form = formie()
        ->form(['title' => 'Default Template Status'])
        ->singleLineTextField('fullName')
        ->create();

    expect($form->defaultStatusId)->not->toBeNull()
        ->and($form->getDefaultStatus())->not->toBeNull();
});

it('handles invalid template ids without hard failure on getter', function (): void {
    $form = formie()
        ->form(['title' => 'Template Edge'])
        ->singleLineTextField('fullName')
        ->create();

    $form->templateId = 999999999;

    expect($form->getTemplate())->toBeNull();
});

it('keeps setting-level submit action url and entry fallback accessors callable', function (): void {
    $form = formie()
        ->form(['title' => 'Redirect Accessors'])
        ->singleLineTextField('fullName')
        ->submitAction('url', ['url' => 'https://example.test/next'])
        ->create();

    $reloaded = Form::find()->id($form->id)->one();

    expect($reloaded?->settings->getFormRedirectUrl(false))->toBeString()
        ->and($reloaded?->settings->getRedirectEntry())->toBeNull();
});
