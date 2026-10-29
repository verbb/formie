<?php

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\fields\Calculations;
use verbb\formie\fields\Email;
use verbb\formie\fields\MultiLineText;
use verbb\formie\fields\Number;
use verbb\formie\fields\Password;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\helpers\References;

it('removes match validation through repeated builder saves', function(string $type) {
    $form = formie()->form()->addField($type, 'original')->addField($type, 'confirmation')->create();
    $form->getFieldByHandle('confirmation')->matchField = References::field($form->getFieldByHandle('original')->reference);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    $form = Form::find()->id($form->id)->one();
    expect($form->getFieldByHandle('confirmation')->getMatchField())->toBe('original');

    for ($save = 0; $save < 2; $save++) {
        $pages = json_decode(json_encode($form->getFormLayout()->getFormBuilderConfig()), true);
        if ($save === 0) {
            foreach ($pages[0]['rows'] as &$row) {
                foreach ($row['fields'] as &$field) {
                    if ($field['handle'] === 'confirmation') {
                        $field['matchField'] = '';
                    }
                }
                unset($field);
            }
            unset($row);
        }
        $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder($pages, $form));
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $form = Form::find()->id($form->id)->one();
        $field = $form->getFieldByHandle('confirmation');
        expect($field->matchField)->toBeIn(['', null])
            ->and($field->getMatchField())->toBeNull();
        expect(array_column($field->getElementValidationRules(), 1))->not->toContain('validateMatchField');
    }
})->with([SingleLineText::class, MultiLineText::class, Email::class, Password::class, Number::class, Calculations::class]);
