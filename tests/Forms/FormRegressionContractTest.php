<?php

declare(strict_types=1);

use verbb\formie\elements\Form;

it('keeps key form builder contract keys stable across save and reload cycles', function (): void {
    $form = formie()
        ->form(['title' => 'Regression Contract'])
        ->singleLineTextField('fullName')
        ->submitAction('message', ['message' => 'Saved'])
        ->create();

    $before = [
        'fieldCount' => count($form->getFields()),
        'pageCount' => count($form->getPages()),
        'submitAction' => $form->settings->submitAction,
    ];
    Craft::$app->elements->saveElement($form);
    $reloaded = Form::find()->id($form->id)->one();
    $after = [
        'fieldCount' => count($reloaded?->getFields() ?? []),
        'pageCount' => count($reloaded?->getPages() ?? []),
        'submitAction' => $reloaded?->settings->submitAction,
    ];

    expect($after)->toBe($before);
});

it('keeps runtime config json parseable and stable for core keys', function (): void {
    $form = formie()
        ->form(['title' => 'JSON Contract'])
        ->singleLineTextField('fullName')
        ->submitAction('url', ['url' => 'https://example.test/thanks', 'tab' => 'same-tab'])
        ->create();

    $config = $form->getClientConfig();

    $settings = $config['settings'] ?? [];

    expect($config)->toBeArray()
        ->and($config)->toHaveKeys(['formId', 'handle', 'settings', 'pages', 'modules'])
        ->and($settings)->toHaveKeys([
            'currentPageId',
            'errorMessage',
            'progressCalculation',
            'submitMethod',
            'validationOnSubmit',
        ])
        ->and($form->settings->submitAction)->toBe('url')
        ->and($form->settings->submitActionTab)->toBe('same-tab')
        ->and((string)$form->getRedirectUrl())->toContain('example.test/thanks');
});
