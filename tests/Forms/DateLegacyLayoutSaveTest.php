<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;

function dateFormWithoutVariantSettings(string $displayType): Form
{
    $form = formie()->form()->dateField('appointment', ['displayType' => $displayType])->create();
    $field = $form->getFieldByHandle('appointment');
    $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $field->definitionId])->scalar());
    // Formie 3 persisted the nested layout, without Formie 4's variant map.
    unset($settings['layouts']);
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FIELDS, ['settings' => Json::encode($settings)], ['id' => $field->definitionId])->execute();
    Formie::$plugin->getFields()->resetFieldRegistryCache();

    return Form::find()->id($form->id)->one();
}

it('preserves legacy date children when an upgraded form is edited and saved', function (string $displayType): void {
    $form = dateFormWithoutVariantSettings($displayType);
    $field = $form->getFieldByHandle('appointment');
    expect($field->layouts)->toBe([]);
    $identity = fn($child) => [$child->id, $child->definitionId, $child->uid, $child->reference];
    $identities = array_map($identity, $field->getFields());
    $first = $field->getFields()[0];
    $first->label = 'Custom migrated label';
    $first->enabled = false;
    $form->title .= ' edited';

    for ($save = 0; $save < 2; $save++) {
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        Formie::$plugin->getFields()->resetFieldRegistryCache();
        $form = Form::find()->id($form->id)->one();
        $children = $form->getFieldByHandle('appointment')->getFields();
        expect(array_map($identity, $children))->toBe($identities)
            ->and($children[0]->label)->toBe('Custom migrated label')
            ->and($children[0]->enabled)->toBeFalse();
    }
})->with(['calendar', 'datePicker', 'inputs', 'dropdowns']);

it('uses the correct child types when a legacy date changes display variant', function (string $from, string $to): void {
    $form = dateFormWithoutVariantSettings($from);
    $field = $form->getFieldByHandle('appointment');
    $field->displayType = $to;
    $defaults = (new \verbb\formie\fields\Date(['displayType' => $to]))->getSubFields();
    $expected = [];
    foreach ($defaults as $row) {
        foreach ($row['fields'] as $child) $expected[] = [$child['handle'], $child['type']];
    }
    $shape = fn($child) => [$child->handle, $child::class];

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    $reloaded = Form::find()->id($form->id)->one()->getFieldByHandle('appointment');
    expect(array_map($shape, $reloaded->getFields()))->toBe($expected);
})->with([['inputs', 'dropdowns'], ['dropdowns', 'inputs'], ['calendar', 'inputs']]);
