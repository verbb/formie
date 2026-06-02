<?php

declare(strict_types=1);

use verbb\formie\elements\Form;

it('persists appearance-oriented settings for labels instructions progress and indicators', function (): void {
    $form = formie()
        ->form(['title' => 'Appearance Runtime Config'])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $form->settings->setAttributes([
        'displayFormTitle' => true,
        'displayCurrentPageTitle' => true,
        'displayPageTabs' => true,
        'displayPageProgress' => true,
        'progressPosition' => 'start',
        'defaultLabelPosition' => 'top',
        'defaultInstructionsPosition' => 'below-input',
        'requiredIndicator' => 'optional',
    ], false);

    $saved = Craft::$app->elements->saveElement($form);
    $reloaded = Form::find()->id($form->id)->one();

    expect($saved)->toBeTrue()
        ->and($reloaded?->settings->displayFormTitle)->toBeTrue()
        ->and($reloaded?->settings->displayCurrentPageTitle)->toBeTrue()
        ->and($reloaded?->settings->displayPageTabs)->toBeTrue()
        ->and($reloaded?->settings->displayPageProgress)->toBeTrue()
        ->and($reloaded?->settings->progressPosition)->toBe('start')
        ->and($reloaded?->settings->defaultLabelPosition)->toBe('top')
        ->and($reloaded?->settings->defaultInstructionsPosition)->toBe('below-input')
        ->and($reloaded?->settings->requiredIndicator)->toBe('optional');
});

it('emits runtime frontend variables for loading indicator and scroll behavior', function (): void {
    $form = formie()
        ->form(['title' => 'Appearance Frontend Vars'])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->setAttributes([
        'submitMethod' => 'ajax',
        'loadingIndicator' => 'spinner',
        'loadingIndicatorText' => 'Submitting...',
        'scrollToTop' => false,
        'submitActionMessagePosition' => 'bottom-form',
    ], false);

    expect(Craft::$app->elements->saveElement($form))->toBeTrue();

    $clientConfig = $form->getClientConfig();
    $settings = $clientConfig['settings'] ?? [];

    expect($settings['submitMethod'] ?? null)->toBe('ajax')
        ->and($settings['loadingIndicator'] ?? null)->toBe('spinner')
        ->and($settings['loadingIndicatorText'] ?? null)->toBe('Submitting...')
        ->and((bool)($settings['scrollToTop'] ?? true))->toBeFalse()
        ->and($form->settings->submitActionMessagePosition)->toBe('bottom-form');
});
