<?php

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\helpers\References;
use verbb\formie\helpers\Table;

function legacyMatchForm(string $stored): Form
{
    $form = formie()->form()->emailField('original', ['label' => 'Original'])->emailField('confirmation', ['label' => 'Confirmation'])->create();

    return storeLegacyMatchField($form, 'confirmation', str_replace('{reference}', (string)$form->getFieldByHandle('original')->reference, $stored));
}

function storeLegacyMatchField(Form $form, string $handle, string $stored): Form
{
    $definitionId = $form->getFieldByHandle($handle)->definitionId;
    $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $definitionId])->scalar());
    $settings['matchField'] = $stored;
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FIELDS, ['settings' => Json::encode($settings)], ['id' => $definitionId])->execute();

    return reloadedMatchForm($form->id);
}

function reloadedMatchForm(int $id): Form
{
    Formie::$plugin->getFields()->resetFieldRegistryCache();

    return Form::find()->id($id)->status(null)->one();
}

function matchFieldErrors(Form $form, string $confirmation): array
{
    $submission = new Submission(['title' => 'Match field']);
    $submission->setForm($form);
    $submission->setFieldValues(['original' => 'first@example.test', 'confirmation' => $confirmation]);
    $submission->validate();

    return $submission->getErrors('confirmation');
}

function storedMatchField(Form $form, string $handle = 'confirmation'): mixed
{
    return Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $form->getFieldByHandle($handle)->definitionId])->scalar())['matchField'] ?? null;
}

function builderMatchField(Form $form, string $handle = 'confirmation'): mixed
{
    return $form->getFieldByHandle($handle)->getFormBuilderConfig()['matchField'] ?? null;
}

it('keeps enforcing every stored match field format', function(string $stored) {
    $form = legacyMatchForm($stored);
    $field = $form->getFieldByHandle('confirmation');

    expect($field->getMatchField())->toBe('original')
        ->and(array_column($field->getElementValidationRules(), 1))->toContain('validateMatchField')
        ->and(array_column($field->browserValidationRules(), 'fieldHandle'))->toBe(['original'])
        ->and(matchFieldErrors($form, 'other@example.test'))->toBe(['Confirmation must match Original.'])
        ->and(matchFieldErrors($form, 'first@example.test'))->toBe([]);
})->with(['{original}', '{field:original}', '{field.original}', '{field:{reference}}']);

it('shows stored match fields in the builder as the reference its picker offers', function(string $stored) {
    $form = legacyMatchForm($stored);

    expect(builderMatchField($form))->toBe(References::field((string)$form->getFieldByHandle('original')->reference))
        ->and(storedMatchField($form))->toBe(str_replace('{reference}', (string)$form->getFieldByHandle('original')->reference, $stored));
})->with(['{original}', '{field:original}', '{field.original}', '{field:{reference}}']);

it('stores the reference once the builder saves, without changing validation', function(string $stored) {
    $form = legacyMatchForm($stored);
    $canonical = References::field((string)$form->getFieldByHandle('original')->reference);

    foreach ([1, 2] as $save) {
        $pages = Json::decode(Json::encode($form->getFormLayout()->getFormBuilderConfig()));
        $form->getFormLayout()->setPages((new FormSerializer())->hydrateBuilder($pages, $form));
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        $form = reloadedMatchForm($form->id);

        expect(storedMatchField($form))->toBe($canonical)
            ->and(matchFieldErrors($form, 'other@example.test'))->toBe(['Confirmation must match Original.'])
            ->and(matchFieldErrors($form, 'first@example.test'))->toBe([]);
    }
})->with(['{original}', '{field:original}', '{field.original}']);

it('leaves values alone when they name no field in the form', function(string $stored) {
    $form = legacyMatchForm($stored);
    $field = $form->getFieldByHandle('confirmation');

    expect($field->getMatchField())->toBeNull()
        ->and(builderMatchField($form))->toBe($stored)
        ->and(matchFieldErrors($form, 'other@example.test'))->toBe([]);
})->with(['{missing}', '{field:missing}', '{form}', '{form:handle}', 'original']);

it('keeps the stored value on a synced definition, where references differ per form', function() {
    $form = legacyMatchForm('{field:original}');
    $other = formie()->form()->emailField('original', ['label' => 'Original'])->emailField('copy', ['label' => 'Copy'])->create();
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORM_FIELDS, ['fieldId' => $form->getFieldByHandle('confirmation')->definitionId], ['id' => $other->getFieldByHandle('copy')->id])->execute();
    $form = reloadedMatchForm($form->id);

    expect($form->getFieldByHandle('confirmation')->getIsSynced())->toBeTrue()
        ->and(builderMatchField($form))->toBe('{field:original}')
        ->and(matchFieldErrors($form, 'other@example.test'))->toBe(['Confirmation must match Original.']);
});
