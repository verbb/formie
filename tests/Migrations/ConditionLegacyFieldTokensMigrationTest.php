<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\conditions\ConditionVisibility;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\fields\Date;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261115_010000_condition_legacy_field_tokens;

function legacyTokenConditions(string $token, string $operator = '=', mixed $value = 'Yes', string $mode = 'all'): array
{
    return ['version' => 1, 'showRule' => 'show', 'conditionRule' => $mode, 'conditions' => [['id' => 'new1-2', 'field' => $token, 'condition' => $operator, 'value' => $value, 'legacyForward' => true]]];
}

function storeLegacyTokenConditions(\verbb\formie\base\Field $field, array $conditions): void
{
    $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $field->definitionId])->scalar());

    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FIELDS, ['settings' => Json::encode(['enableConditions' => true, 'conditions' => $conditions] + $settings)], ['id' => $field->definitionId])->execute();
}

function storedLegacyToken(\verbb\formie\base\Field $field): string
{
    return Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $field->definitionId])->scalar())['conditions']['conditions'][0]['field'];
}

function reloadLegacyTokenForm(int $id): \verbb\formie\elements\Form
{
    Craft::$app->getElements()->invalidateAllCaches();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    Formie::$plugin->getForms()->invalidateFormCaches();

    return Formie::$plugin->getForms()->getFormById($id);
}

function legacyTokenSubmission(\verbb\formie\elements\Form $form, array $values): Submission
{
    $submission = new Submission(['title' => 'Conditions test']);
    $submission->setForm($form);
    $submission->setFieldValues($values);

    return $submission;
}

function radioOptions(): array
{
    return ['options' => [['label' => 'Yes', 'value' => 'Yes'], ['label' => 'No', 'value' => 'No']]];
}

it('makes group row paths and bare tokens read the field they name', function(string $token) {
    $form = formie()->form()
        ->groupField('contact', ['rows' => [['fields' => [['type' => SingleLineText::class, 'handle' => 'child', 'label' => 'Child']]]]])
        ->singleLineTextField('plain')
        ->singleLineTextField('target')
        ->create();

    storeLegacyTokenConditions($form->getFieldByHandle('target'), legacyTokenConditions($token));

    foreach ([1, 2] as $run) {
        expect((new m261115_010000_condition_legacy_field_tokens())->safeUp())->toBeTrue();
    }

    $migrated = reloadLegacyTokenForm($form->id);
    $target = $migrated->getFieldByHandle('target');
    $values = str_contains($token, 'contact') ? ['contact' => ['child' => 'Yes']] : ['plain' => 'Yes'];
    $source = str_contains($token, 'contact') ? $migrated->getFieldByHandle('contact')->getFieldByHandle('child') : $migrated->getFieldByHandle('plain');

    expect(storedLegacyToken($target))->toBe('{field:' . $source->reference . '}')
        ->and($target->isConditionallyHidden(legacyTokenSubmission($migrated, $values)))->toBeFalse()
        ->and($target->isConditionallyHidden(legacyTokenSubmission($migrated, [])))->toBeTrue()
        ->and($target->getBrowserConditions()['rules'][0]['browserSafe'])->toBeTrue();
})->with([
    'stored by Formie 2' => '{field:contact.rows.new1.fields.child}',
    'bare with a row path' => '{contact.rows.new1.fields.child}',
    'bare group path' => '{contact.child}',
    'bare handle' => '{plain}',
]);

it('keeps same-row repeater sources working for each row', function() {
    $form = formie()->form()->repeaterField('people', ['rows' => [['fields' => [
        ['type' => SingleLineText::class, 'handle' => 'allow', 'label' => 'Allow'],
        ['type' => SingleLineText::class, 'handle' => 'detail', 'label' => 'Detail'],
    ]]]])->create();

    storeLegacyTokenConditions($form->getFieldByHandle('people')->getFieldByHandle('detail'), legacyTokenConditions('{field:people.__ROW__.allow}'));

    expect((new m261115_010000_condition_legacy_field_tokens())->safeUp())->toBeTrue();

    $migrated = reloadLegacyTokenForm($form->id);
    $people = $migrated->getFieldByHandle('people');
    $submission = legacyTokenSubmission($migrated, ['people' => [['allow' => 'Yes', 'detail' => 'kept'], ['allow' => 'No', 'detail' => 'cleared']]]);

    (new ConditionVisibility())->clear($submission);

    expect(storedLegacyToken($people->getFieldByHandle('detail')))->toBe('{field:' . $people->getFieldByHandle('allow')->reference . '}')
        ->and($submission->getFieldValue('people.0.detail'))->toBe('kept')
        ->and($submission->getFieldValue('people.1.detail'))->toBe('')
        ->and($people->getFieldByHandle('detail')->getBrowserConditions()['rules'][0]['source']['domHandle'])->toBe('people.__ROW__.allow');
});

it('keeps sub-field selectors on bare and repeater paths', function() {
    $form = formie()->form()
        ->addressField('school', ['rows' => (new Address())->getSubFields()])
        ->repeaterField('rows1', ['rows' => [['fields' => [
            ['type' => Date::class, 'handle' => 'born', 'label' => 'Born', 'displayType' => 'dropdowns', 'includeDate' => true],
            ['type' => SingleLineText::class, 'handle' => 'phone', 'label' => 'Phone'],
        ]]]])
        ->singleLineTextField('target')
        ->create();

    storeLegacyTokenConditions($form->getFieldByHandle('target'), legacyTokenConditions('{school.state}', '=', 'NSW'));
    storeLegacyTokenConditions($form->getFieldByHandle('rows1')->getFieldByHandle('phone'), legacyTokenConditions('{field:rows1.__ROW__.born.year}', '<', '2008'));

    expect((new m261115_010000_condition_legacy_field_tokens())->safeUp())->toBeTrue();

    $migrated = reloadLegacyTokenForm($form->id);
    $repeater = $migrated->getFieldByHandle('rows1');

    expect(storedLegacyToken($migrated->getFieldByHandle('target')))->toBe('{field:' . $migrated->getFieldByHandle('school')->getFieldByHandle('state')->reference . '}')
        ->and(storedLegacyToken($repeater->getFieldByHandle('phone')))->toBe('{field:' . $repeater->getFieldByHandle('born')->getFieldByHandle('year')->reference . '}')
        ->and($migrated->getFieldByHandle('target')->isConditionallyHidden(legacyTokenSubmission($migrated, ['school' => ['state' => 'NSW']])))->toBeFalse()
        ->and($migrated->getFieldByHandle('target')->isConditionallyHidden(legacyTokenSubmission($migrated, ['school' => ['state' => 'VIC']])))->toBeTrue();
});

it('converts notification conditions and conditional recipients', function() {
    $form = formie()->form()
        ->groupField('contact', ['rows' => [['fields' => [['type' => SingleLineText::class, 'handle' => 'email', 'label' => 'Email']]]]])
        ->singleLineTextField('region')
        ->create();
    $notification = new \verbb\formie\models\Notification(['formId' => $form->id, 'name' => 'Legacy', 'handle' => 'legacy', 'enabled' => true, 'subject' => 'Subject', 'to' => 'to@example.test', 'content' => '[]']);
    Formie::$plugin->getNotifications()->saveNotification($notification);

    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_NOTIFICATIONS, [
        'enableConditions' => true,
        'conditions' => Json::encode(['sendRule' => 'send', 'conditionRule' => 'all', 'conditions' => [['id' => 'a', 'field' => '{field:contact.rows.new1.fields.email}', 'condition' => 'contains', 'value' => '@']]]),
        'recipients' => 'conditions',
        'toConditions' => Json::encode(['sendRule' => 'send', 'conditionRule' => 'all', 'toRecipients' => [['id' => 'b', 'email' => 'bern@example.test', 'field' => '{region}', 'condition' => '=', 'value' => 'bern']]]),
    ], ['id' => $notification->id])->execute();

    expect((new m261115_010000_condition_legacy_field_tokens())->safeUp())->toBeTrue();

    $migrated = reloadLegacyTokenForm($form->id);
    $row = (new Query())->select(['conditions', 'toConditions'])->from(Table::FORMIE_NOTIFICATIONS)->where(['id' => $notification->id])->one();
    $saved = Formie::$plugin->getNotifications()->getNotificationById($notification->id);
    $matching = legacyTokenSubmission($migrated, ['contact' => ['email' => 'a@example.test'], 'region' => 'bern']);

    expect(Json::decode($row['conditions'])['conditions'][0]['field'])->toBe('{field:' . $migrated->getFieldByHandle('contact')->getFieldByHandle('email')->reference . '}')
        ->and(Json::decode($row['toConditions'])['toRecipients'][0]['field'])->toBe('{field:' . $migrated->getFieldByHandle('region')->reference . '}')
        ->and($saved->getToEmail($matching))->toBe('bern@example.test')
        ->and(Formie::$plugin->getNotifications()->evaluateConditions($saved, $matching))->toBeTrue()
        ->and(Formie::$plugin->getNotifications()->evaluateConditions($saved, legacyTokenSubmission($migrated, [])))->toBeFalse();
});

it('leaves tokens alone unless they name a field in this form', function(string $token) {
    $form = formie()->form()->singleLineTextField('plain')->singleLineTextField('target')->create();

    storeLegacyTokenConditions($form->getFieldByHandle('target'), legacyTokenConditions($token));

    expect((new m261115_010000_condition_legacy_field_tokens())->safeUp())->toBeTrue();

    expect(storedLegacyToken(reloadLegacyTokenForm($form->id)->getFieldByHandle('target')))->toBe($token);
})->with([
    'a deleted field' => '{field:deleted.rows.new1.fields.child}',
    'a deleted bare handle' => '{deleted}',
    'an unknown child' => '{field:plain.rows.new1.fields.child}',
    'a submission attribute' => '{submission:status}',
    'a current plain token' => '{field:plain}',
    'a row-scoped token' => '{field:plain;scope=first}',
]);

it('stores readable paths on definitions shared between forms', function() {
    $form = formie()->form()
        ->groupField('contact', ['rows' => [['fields' => [['type' => SingleLineText::class, 'handle' => 'child', 'label' => 'Child']]]]])
        ->singleLineTextField('target')
        ->create();
    $target = $form->getFieldByHandle('target');
    $other = formie()->form()->singleLineTextField('unused')->create();
    $unused = $other->getFieldByHandle('unused');

    storeLegacyTokenConditions($target, legacyTokenConditions('{field:contact.rows.new1.fields.child}'));

    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORM_FIELDS, ['fieldId' => $target->definitionId], ['fieldId' => $unused->definitionId])->execute();

    expect((new m261115_010000_condition_legacy_field_tokens())->safeUp())->toBeTrue();

    $migrated = reloadLegacyTokenForm($form->id);

    expect(storedLegacyToken($migrated->getFieldByHandle('target')))->toBe('{field:contact.child}')
        ->and($migrated->getFieldByHandle('target')->isConditionallyHidden(legacyTokenSubmission($migrated, ['contact' => ['child' => 'Yes']])))->toBeFalse();
});
