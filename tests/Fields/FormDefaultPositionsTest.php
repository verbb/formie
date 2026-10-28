<?php

use verbb\formie\elements\Form;
use verbb\formie\fields\Address;
use verbb\formie\fields\Checkboxes;
use verbb\formie\fields\Content;
use verbb\formie\fields\Html;
use verbb\formie\fields\Name;
use verbb\formie\fields\Quiz;
use verbb\formie\fields\Radio;
use verbb\formie\fields\Recipients;
use verbb\formie\fields\Survey;
use verbb\formie\Formie;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;
use verbb\formie\positions\Hidden;
use verbb\formie\positions\LeftInput;
use verbb\formie\positions\RightInput;

dataset('field position defaults', [
    'Address instructions' => [Address::class, 'instructionsPosition', AboveInput::class],
    'Name instructions' => [Name::class, 'instructionsPosition', AboveInput::class],
    'Content label' => [Content::class, 'labelPosition', Hidden::class],
    'HTML label' => [Html::class, 'labelPosition', Hidden::class],
    'Recipients label' => [Recipients::class, 'labelPosition', Hidden::class],
    'Quiz label' => [Quiz::class, 'labelPosition', AboveInput::class],
    'Survey label' => [Survey::class, 'labelPosition', AboveInput::class],
]);

it('defaults only missing field positions', function (string $type, string $setting, string $default): void {
    expect((new $type())->{$setting})->toBe($default)
        ->and((new $type([$setting => null]))->{$setting})->toBeNull()
        ->and((new $type([$setting => '']))->{$setting})->toBeNull()
        ->and((new $type([$setting => BelowInput::class]))->{$setting})->toBe(BelowInput::class);
})->with('field position defaults');

it('retains live form position inheritance through repeated builder saves', function (string $type, string $setting, string $default, ?string $empty): void {
    $formSetting = $setting === 'labelPosition' ? 'defaultLabelPosition' : 'defaultInstructionsPosition';
    $attribute = $setting === 'labelPosition' ? 'data-formie-label-position' : 'data-formie-instructions-position';
    $form = formie()->form()->settings([$formSetting => BelowInput::class])
        ->addField($type, 'example', [
            'label' => 'Example',
            'instructions' => 'Helpful instructions.',
            $setting => $default,
        ])->create();

    foreach ([BelowInput::class => 'below', AboveInput::class => 'above'] as $position => $renderedPosition) {
        $form->settings->{$formSetting} = $position;
        $pages = $form->getFormLayout()->getFormBuilderConfig();
        $pages[0]['rows'][0]['fields'][0][$setting] = $empty;
        $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder(json_decode(json_encode($pages), true), $form));
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $form = Form::find()->id($form->id)->one();
        $field = $form->getFieldByHandle('example');

        expect($field->{$setting})->toBeNull()
            ->and($field->getFormBuilderConfig()[$setting])->toBeNull();
        $html = (string)Formie::$plugin->getRendering()->renderField($form, 'example');
        expect($html)->toContain($attribute . '="' . $renderedPosition . '"');

        // The next save uses the reloaded value rather than clearing the setting again.
        $empty = $field->{$setting};
        $form->title .= ' edited';
    }
})->with('field position defaults')->with(['empty string' => [''], 'null' => [null]]);

it('renders inherited option-group label positions for question field delegates', function (string $type, array $config): void {
    $form = formie()->form()->addField($type, 'example', $config + ['labelPosition' => null])->create();

    foreach ([LeftInput::class => 'left', RightInput::class => 'right', BelowInput::class => 'below', Hidden::class => 'hidden'] as $position => $expected) {
        $form->settings->defaultLabelPosition = $position;
        $html = (string)Formie::$plugin->getRendering()->renderField($form, 'example');
        expect($html)->toContain('data-formie-label-position="' . $expected . '"', 'formie-field-layout-label-' . $expected);
    }

    $form->getFieldByHandle('example')->labelPosition = AboveInput::class;
    expect((string)Formie::$plugin->getRendering()->renderField($form, 'example'))
        ->toContain('data-formie-label-position="above"', 'formie-field-layout-label-above');
})->with([
    [Radio::class, []],
    [Checkboxes::class, []],
    [Quiz::class, ['displayType' => 'radio']],
    [Quiz::class, ['displayType' => 'checkboxes']],
    [Survey::class, ['displayType' => 'radio']],
    [Survey::class, ['displayType' => 'checkboxes']],
]);
