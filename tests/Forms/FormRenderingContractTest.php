<?php

declare(strict_types=1);

it('exposes expected form builder and runtime config contracts', function (): void {
    $form = formie()
        ->form(['title' => 'Rendering Contract'])
        ->singleLineTextField('fullName')
        ->submitAction('message', [
            'message' => 'Saved',
            'hideForm' => true,
        ])
        ->create();

    $runtimeConfig = $form->getClientConfig();
    $settings = $runtimeConfig['settings'] ?? [];

    expect($runtimeConfig)->toHaveKeys(['formId', 'handle', 'settings', 'pages', 'modules'])
        ->and($settings)->toHaveKeys(['currentPageId', 'errorMessage', 'submitMethod', 'validationOnSubmit'])
        ->and($form->settings->submitAction)->toBe('message')
        ->and($form->settings->submitActionFormHide)->toBeTrue();
});

it('reports baseline condition flags as false on simple forms', function (): void {
    $form = formie()
        ->form(['title' => 'Condition Baseline'])
        ->singleLineTextField('fullName')
        ->create();

    expect($form->hasFieldConditions())->toBeFalse()
        ->and($form->hasButtonConditions())->toBeFalse()
        ->and($form->hasPageConditions())->toBeFalse()
        ->and($form->hasConditions())->toBeFalse();
});
