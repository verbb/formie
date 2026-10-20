<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\Formie;

it('preserves dropdown optgroups through builder edits and repeated saves', function (bool $builderOptions, bool $multi): void {
    $options = [
        ['optgroup' => 'Pacific'],
        ['label' => 'Australia', 'value' => 'au', 'default' => true],
        ['optgroup' => 'Europe'],
        ['label' => 'France', 'value' => 'fr', 'default' => false],
    ];

    // Upgraded fields use stored group labels; new builder rows use a checkbox and label.
    $input = $options;

    if ($builderOptions) {
        $input[0] = ['label' => 'Pacific', 'value' => '', 'optgroup' => true];
        $input[2] = ['label' => 'Europe', 'value' => '', 'optgroup' => true];
        $input[1]['optgroup'] = $input[3]['optgroup'] = false;
    }

    $form = formie()->form()
        ->dropdownField('country', ['options' => $input, 'multi' => $multi])
        ->create();

    for ($save = 0; $save < 2; $save++) {
        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $form = Form::find()->id($form->id)->one();
        $field = $form->getFieldByHandle('country');

        expect($field->options)->toBe($options);

        // Exercise the JSON boundary used to hydrate and post the form builder.
        $settings = json_decode(json_encode($field->getFormBuilderSettings(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        expect($settings['options'])->toBe([
            ['optgroup' => true, 'label' => 'Pacific'],
            ['label' => 'Australia', 'value' => 'au', 'default' => true, 'optgroup' => false],
            ['optgroup' => true, 'label' => 'Europe'],
            ['label' => 'France', 'value' => 'fr', 'default' => false, 'optgroup' => false],
        ])
            ->and($field->options)->toBe($options);

        $html = (string)Formie::$plugin->getRendering()->renderField($form, 'country');

        expect($html)->toContain('<optgroup label="Pacific">', '<optgroup label="Europe">')
            ->and($html)->toContain('value="au"', 'value="fr"');

        $layout = $form->getFormLayout();
        $pages = $layout->getPages();
        $pages[0]->getRows()[0]->setFields([$settings]);
        $layout->setPages($pages);
        $form->setFormLayout($layout);

        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    }

    Formie::$plugin->getFields()->resetFieldRegistryCache();
    $reloaded = Form::find()->id($form->id)->one();

    expect($reloaded->getFieldByHandle('country')->options)->toBe($options);
})->with([
    'stored single-select' => [false, false],
    'stored multi-select' => [false, true],
    'builder single-select' => [true, false],
    'builder multi-select' => [true, true],
]);
