<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\theme\context\RenderContext;

it('persists the disable submit button until valid setting', function (): void {
    $form = formie()
        ->form(['title' => 'Submit Button Disabled Setting'])
        ->singleLineTextField('fullName', ['required' => true])
        ->create();

    $form->settings->setAttributes([
        'disableSubmitButtonUntilValid' => true,
    ], false);

    $saved = Craft::$app->elements->saveElement($form);
    $reloaded = Form::find()->id($form->id)->one();

    expect($saved)->toBeTrue()
        ->and($reloaded?->settings->disableSubmitButtonUntilValid)->toBeTrue();
});

it('renders the disable submit until valid data attribute on the form slot', function (): void {
    $form = formie()
        ->form(['title' => 'Submit Button Disabled Render'])
        ->singleLineTextField('fullName')
        ->create();

    $form->settings->setAttributes([
        'disableSubmitButtonUntilValid' => true,
    ], false);

    expect(Craft::$app->elements->saveElement($form))->toBeTrue();

    $reloaded = Form::find()->id($form->id)->one();

    $tag = Formie::$plugin->getFormSlotRegistry()->resolve('form', RenderContext::from([
        'form' => $reloaded,
    ]));

    expect($reloaded?->settings->disableSubmitButtonUntilValid)->toBeTrue()
        ->and($tag?->coreAttributes['data']['formie-disable-submit-until-valid'] ?? null)->toBeTrue();
});

it('omits the disable submit until valid data attribute when disabled', function (): void {
    $form = formie()
        ->form(['title' => 'Submit Button Disabled Render Off'])
        ->singleLineTextField('fullName')
        ->create();

    $tag = Formie::$plugin->getFormSlotRegistry()->resolve('form', RenderContext::from([
        'form' => $form,
    ]));

    expect($form->settings->disableSubmitButtonUntilValid)->toBeFalse()
        ->and($tag?->coreAttributes['data']['formie-disable-submit-until-valid'] ?? null)->toBeNull();
});
