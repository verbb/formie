<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\fields\Agree;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;
use verbb\formie\positions\Hidden;

it('defaults new agree labels to hidden while preserving explicit positions', function (array $config, ?string $expected): void {
    expect((new Agree($config))->labelPosition)->toBe($expected);
})->with([
    'new field' => [[], Hidden::class],
    'form default' => [['labelPosition' => null], null],
    'empty builder value' => [['labelPosition' => ''], null],
    'hidden' => [['labelPosition' => Hidden::class], Hidden::class],
    'visible' => [['labelPosition' => AboveInput::class], AboveInput::class],
]);

it('preserves the agree form default label position through builder saves', function (?string $position): void {
    $form = formie()->form()
        ->settings(['defaultLabelPosition' => AboveInput::class, 'requiredIndicator' => 'asterisk'])
        ->agreeField('terms', [
            'label' => 'Consent',
            'required' => true,
            'description' => '<p>I agree to the terms</p>',
        ])->create();

    foreach (['above', 'below', 'hidden'] as $save => $expectedPosition) {
        $pages = $form->getFormLayout()->getFormBuilderConfig();

        // The editor posts an empty value; stored settings can also contain null.
        if ($save === 0) {
            $pages[0]['rows'][0]['fields'][0]['labelPosition'] = $position;
        }

        $pages = json_decode(json_encode($pages, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder($pages, $form));
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $form = Form::find()->id($form->id)->one();
        $field = $form->getFieldByHandle('terms');

        expect($field->labelPosition)->toBeNull()
            ->and($field->getFormBuilderConfig()['labelPosition'])->toBeNull();

        $html = (string)Formie::$plugin->getRendering()->renderField($form, 'terms');
        expect($html)->toContain('data-formie-agree-field-label', 'Consent')
            ->toContain('data-formie-label-position="' . $expectedPosition . '"')
            ->toContain('formie-field-layout-label-' . $expectedPosition);

        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        expect($xpath->query('//label[@data-formie-agree-option-label]//*[@data-formie-field-required]')->length)
            ->toBe($expectedPosition === 'hidden' ? 1 : 0)
            ->and($xpath->query('//legend[@data-formie-sr-only]')->length)
            ->toBe($expectedPosition === 'hidden' ? 1 : 0);

        // Inheritance must remain live when the form's default changes.
        $form->settings->defaultLabelPosition = $save === 0 ? BelowInput::class : Hidden::class;
        $form->title .= ' edited';
    }
})->with(['null' => [null], 'empty string' => ['']]);

it('renders the required asterisk on the agree field option label when the label is hidden', function (): void {
    $form = formie()
        ->form(['title' => 'Agree Required Indicator'])
        ->settings(['requiredIndicator' => 'asterisk'])
        ->agreeField('terms', [
            'required' => true,
            'description' => '<p>I agree to the terms</p>',
        ])
        ->create();

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'terms');

    expect($html)
        ->toContain('data-formie-agree-option-label')
        ->toContain('data-formie-field-required')
        ->toContain('I agree to the terms')
        ->toMatch('/data-formie-agree-option-label[^>]*>[\s\S]*I agree to the terms[\s\S]*data-formie-field-required/');
});

it('does not duplicate the required asterisk when the agree field label is visible', function (): void {
    $form = formie()
        ->form(['title' => 'Agree Visible Label Required Indicator'])
        ->settings(['requiredIndicator' => 'asterisk'])
        ->agreeField('terms', [
            'required' => true,
            'label' => 'Consent',
            'labelPosition' => 'verbb\\formie\\positions\\AboveInput',
            'description' => '<p>I agree to the terms</p>',
        ])
        ->create();

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'terms');

    expect($html)
        ->toContain('data-formie-agree-field-label')
        ->toContain('data-formie-field-required')
        ->toContain('Consent')
        ->not->toMatch('/data-formie-agree-option-label[^>]*>[\s\S]*data-formie-field-required/');
});
