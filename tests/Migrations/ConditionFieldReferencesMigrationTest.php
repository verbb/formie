<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\ConditionsHelper;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261103_010000_condition_field_references;

function legacyConditionRow(string $field, string $value = 'Yes'): array
{
    return ['id' => 'new1234-567', 'field' => $field, 'condition' => '=', 'value' => $value, 'legacyForward' => true];
}

function legacyConditions(string ...$fields): array
{
    return ['version' => 1, 'showRule' => 'show', 'conditionRule' => 'all', 'conditions' => array_map(fn($field) => legacyConditionRow($field), $fields)];
}

function storedLegacySettings(string $table, int $id, string $column = 'settings'): array
{
    return Json::decode((new Query())->select($column)->from($table)->where(['id' => $id])->scalar());
}

function storeLegacySettings(string $table, int $id, callable $update, string $column = 'settings'): void
{
    Craft::$app->getDb()->createCommand()->update($table, [$column => Json::encode($update(storedLegacySettings($table, $id, $column)))], ['id' => $id])->execute();
}

function storeLegacyFieldConditions(\verbb\formie\base\Field $field, array $conditions): void
{
    storeLegacySettings(Table::FORMIE_FIELDS, $field->definitionId, fn($settings) => ['enableConditions' => true, 'conditions' => $conditions] + $settings);
}

function freshConditionForm(int $id): \verbb\formie\elements\Form
{
    Craft::$app->getElements()->invalidateAllCaches();

    return Formie::$plugin->getForms()->getFormById($id);
}

it('replaces Formie 3 handles in field conditions without changing the rule', function() {
    $form = formie()->form()
        ->radioField('a', ['options' => [['label' => 'Yes', 'value' => 'Yes'], ['label' => 'No', 'value' => 'No']]])
        ->singleLineTextField('b')
        ->create();
    $a = $form->getFieldByHandle('a');
    $b = $form->getFieldByHandle('b');
    storeLegacyFieldConditions($b, legacyConditions('{field:a}'));

    $legacyForm = freshConditionForm($form->id);
    $submission = new Submission(['title' => 'Conditions test']);
    $submission->setForm($legacyForm);
    $submission->setFieldValue('a', 'Yes');
    $legacyField = $legacyForm->getFieldByHandle('b');
    $before = ConditionsHelper::evaluate($legacyField->getConditions(), $submission)->matches();
    $beforeRules = $legacyField->getBrowserConditions()['rules'];

    foreach ([1, 2] as $run) {
        expect((new m261103_010000_condition_field_references())->safeUp())->toBeTrue();

        expect(storedLegacySettings(Table::FORMIE_FIELDS, $b->definitionId)['conditions'])->toBe([
            'version' => 1,
            'showRule' => 'show',
            'conditionRule' => 'all',
            'conditions' => [['id' => 'new1234-567', 'field' => '{field:' . $a->reference . '}', 'condition' => '=', 'value' => 'Yes', 'legacyForward' => true]],
        ]);
    }

    $migratedForm = freshConditionForm($form->id);
    $submission = new Submission(['title' => 'Conditions test']);
    $submission->setForm($migratedForm);
    $submission->setFieldValue('a', 'Yes');
    $migratedField = $migratedForm->getFieldByHandle('b');

    expect($before)->toBeTrue()
        ->and(ConditionsHelper::evaluate($migratedField->getConditions(), $submission)->matches())->toBeTrue()
        ->and($migratedField->getBrowserConditions()['rules'])->toBe($beforeRules);

    $submission->setFieldValue('a', 'No');
    expect(ConditionsHelper::evaluate($migratedField->getConditions(), $submission)->matches())->toBeFalse();
});

it('replaces handles in nested field, page and form conditions', function() {
    $form = formie()->form()
        ->singleLineTextField('a')
        ->groupField('contact', ['rows' => [['fields' => [['type' => SingleLineText::class, 'handle' => 'inner', 'label' => 'Inner']]]]])
        ->create();
    $canonical = '{field:' . $form->getFieldByHandle('a')->reference . '}';
    $inner = $form->getFieldByHandle('contact')->getFieldByHandle('inner');
    $pageId = $form->getPages()[0]->id;
    storeLegacyFieldConditions($inner, legacyConditions('{field:a}'));
    storeLegacySettings(Table::FORMIE_FIELD_LAYOUT_PAGES, $pageId, fn($settings) => [
        'pageConditions' => legacyConditions('{field:a}'),
        'nextButtonConditions' => legacyConditions('{field:a}'),
    ] + $settings);
    storeLegacySettings(Table::FORMIE_FORMS, $form->id, function($settings) {
        $settings['integrations'] = ['fixture' => ['enabled' => false, 'conditions' => legacyConditions('{field:a}')]];
        $settings['statusRules'] = [['conditions' => legacyConditions('{field:a}')]];

        return $settings;
    });

    expect((new m261103_010000_condition_field_references())->safeUp())->toBeTrue();

    $page = storedLegacySettings(Table::FORMIE_FIELD_LAYOUT_PAGES, $pageId);
    $settings = storedLegacySettings(Table::FORMIE_FORMS, $form->id);

    expect(storedLegacySettings(Table::FORMIE_FIELDS, $inner->definitionId)['conditions']['conditions'][0]['field'])->toBe($canonical)
        ->and($page['pageConditions']['conditions'][0]['field'])->toBe($canonical)
        ->and($page['nextButtonConditions']['conditions'][0]['field'])->toBe($canonical)
        ->and($settings['integrations']['fixture']['conditions']['conditions'][0]['field'])->toBe($canonical)
        ->and($settings['statusRules'][0]['conditions']['conditions'][0]['field'])->toBe($canonical);
});

it('replaces the string form Formie 3 offered for fields with sub-fields', function() {
    $form = formie()->form()
        ->addressField('place', ['rows' => (new Address())->getSubFields()])
        ->groupField('contact', ['rows' => [['fields' => [['type' => Address::class, 'handle' => 'home', 'label' => 'Home', 'settings' => ['rows' => (new Address())->getSubFields()]]]]]])
        ->singleLineTextField('b')
        ->create();
    $b = $form->getFieldByHandle('b');
    storeLegacyFieldConditions($b, legacyConditions('{field:place.__toString}', '{field:contact.home.__toString}'));

    expect((new m261103_010000_condition_field_references())->safeUp())->toBeTrue();

    $rows = storedLegacySettings(Table::FORMIE_FIELDS, $b->definitionId)['conditions']['conditions'];

    expect($rows[0]['field'])->toBe('{field:' . $form->getFieldByHandle('place')->reference . '}')
        ->and($rows[1]['field'])->toBe('{field:' . $form->getFieldByHandle('contact')->getFieldByHandle('home')->reference . '}');
});

it('leaves tokens it cannot safely identify and text that is not a condition source', function() {
    $form = formie()->form()
        ->singleLineTextField('a')
        ->phoneField('phone')
        ->repeaterField('items', ['rows' => [['fields' => [['type' => SingleLineText::class, 'handle' => 'item', 'label' => 'Item']]]]])
        ->singleLineTextField('b')
        ->hiddenField('tracking')
        ->create();
    $b = $form->getFieldByHandle('b');
    $tracking = $form->getFieldByHandle('tracking');
    $unchanged = ['{field:missing}', '{submission:title}', '{form:handle}', '{field:phone.missing}', '{field:items.item}', '{field:item}', '{field:items.item.__toString}', ''];
    $conditions = legacyConditions(...$unchanged);
    $conditions['conditions'][0]['value'] = '{field:a}';
    storeLegacyFieldConditions($b, $conditions);
    storeLegacySettings(Table::FORMIE_FIELDS, $tracking->definitionId, fn($settings) => ['defaultValue' => '{field:a}'] + $settings);

    expect((new m261103_010000_condition_field_references())->safeUp())->toBeTrue();

    expect(storedLegacySettings(Table::FORMIE_FIELDS, $b->definitionId)['conditions'])->toBe($conditions)
        ->and(storedLegacySettings(Table::FORMIE_FIELDS, $tracking->definitionId)['defaultValue'])->toBe('{field:a}');
});

it('keeps handles on synced definitions, where references differ per form', function() {
    $first = formie()->form()->singleLineTextField('a')->singleLineTextField('b')->create();
    $second = formie()->form()->singleLineTextField('a')->singleLineTextField('c')->create();
    $synced = $first->getFieldByHandle('b');
    $other = $second->getFieldByHandle('c');
    storeLegacyFieldConditions($synced, legacyConditions('{field:a}'));
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORM_FIELDS, ['fieldId' => $synced->definitionId], ['id' => $other->id])->execute();

    expect((new m261103_010000_condition_field_references())->safeUp())->toBeTrue();

    expect(storedLegacySettings(Table::FORMIE_FIELDS, $synced->definitionId)['conditions']['conditions'][0]['field'])->toBe('{field:a}');
});

it('includes forms that are disabled or trashed', function() {
    $disabled = formie()->form()->singleLineTextField('a')->singleLineTextField('b')->create();
    $trashed = formie()->form()->singleLineTextField('a')->singleLineTextField('b')->create();
    storeLegacyFieldConditions($disabled->getFieldByHandle('b'), legacyConditions('{field:a}'));
    storeLegacyFieldConditions($trashed->getFieldByHandle('b'), legacyConditions('{field:a}'));
    $disabled->enabled = false;
    Craft::$app->getDb()->createCommand()->update('{{%elements}}', ['enabled' => false], ['id' => $disabled->id])->execute();
    $references = [$disabled->getFieldByHandle('a')->reference, $trashed->getFieldByHandle('a')->reference];
    $definitions = [$disabled->getFieldByHandle('b')->definitionId, $trashed->getFieldByHandle('b')->definitionId];
    Craft::$app->getDb()->createCommand()->update('{{%elements}}', ['dateDeleted' => '2026-01-01 00:00:00'], ['id' => $trashed->id])->execute();

    expect((new m261103_010000_condition_field_references())->safeUp())->toBeTrue();

    foreach ($definitions as $index => $definitionId) {
        expect(storedLegacySettings(Table::FORMIE_FIELDS, $definitionId)['conditions']['conditions'][0]['field'])->toBe('{field:' . $references[$index] . '}');
    }
});
