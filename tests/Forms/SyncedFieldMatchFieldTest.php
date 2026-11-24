<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\helpers\References;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261124_000000_per_form_field_settings;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Stencils as StencilsService;

/**
 * A synced field's Match Field names another field in the same form. Creating a form from a stencil of synced fields
 * mustn't point the shared setting at the new form's field, which stops the original form from checking the match.
 */
function syncedMatchReload(int $id): Form
{
    Craft::$app->getElements()->invalidateAllCaches();
    Formie::$plugin->getFields()->resetFieldRegistryCache();

    return Form::find()->id($id)->status(null)->one();
}

function syncedMatchErrors(Form $form, string $email, string $confirm): array
{
    $submission = new Submission(['title' => 'Synced match']);
    $submission->setForm($form);
    $submission->setFieldValues(['email' => $email, 'confirmEmail' => $confirm]);
    $submission->validate();

    return $submission->getErrors('confirmEmail');
}

it('keeps Match Field working in every form when a stencil of synced fields is used again', function(): void {
    $suffix = uniqid();
    $a = formie()->form(['title' => 'Synced Match A', 'handle' => 'syncedMatchA' . $suffix])
        ->emailField('email')
        ->emailField('confirmEmail')
        ->create();
    $a = syncedMatchReload($a->id);
    $a->getFieldByHandle('confirmEmail')->matchField = References::field((string)$a->getFieldByHandle('email')->reference);
    expect(Craft::$app->getElements()->saveElement($a))->toBeTrue(json_encode($a->getErrors()));
    $a = syncedMatchReload($a->id);

    // The check works in the original form before the stencil is used.
    expect(syncedMatchErrors($a, 'one@example.test', 'two@example.test'))->not->toBeEmpty()
        ->and(syncedMatchErrors($a, 'one@example.test', 'one@example.test'))->toBeEmpty();

    $pages = StencilData::getSerializedLayout($a->getFormLayout());

    foreach ($pages as &$page) {
        foreach ($page['rows'] as &$row) {
            foreach ($row['fields'] as &$field) {
                $field['syncedDefinitionUid'] = $field['definitionUid'];
            }
        }
    }
    unset($page, $row, $field);

    $stencil = new Stencil(['name' => 'Synced Match', 'handle' => 'syncedMatch' . $suffix, 'scope' => StencilsService::SCOPE_SITE]);
    $stencil->data = new StencilData(['pages' => $pages]);

    $b = new Form(['title' => 'Synced Match B', 'handle' => 'syncedMatchB' . $suffix]);
    $stencil->applyStencilToForm($b, true);
    expect(Craft::$app->getElements()->saveElement($b))->toBeTrue(json_encode($b->getErrors()));
    $a = syncedMatchReload($a->id);
    $b = syncedMatchReload($b->id);

    expect($b->getFieldByHandle('confirmEmail')->definitionId)->toBe($a->getFieldByHandle('confirmEmail')->definitionId)
        ->and(syncedMatchErrors($b, 'one@example.test', 'two@example.test'))->not->toBeEmpty()
        ->and(syncedMatchErrors($b, 'one@example.test', 'one@example.test'))->toBeEmpty()
        ->and(syncedMatchErrors($a, 'one@example.test', 'two@example.test'))->not->toBeEmpty()
        ->and(syncedMatchErrors($a, 'one@example.test', 'one@example.test'))->toBeEmpty();
});

it('moves a Match Field stored on the definition to each form, and keeps checking the match meanwhile', function(): void {
    $form = formie()->form(['title' => 'Stored Match', 'handle' => 'storedMatch' . uniqid()])
        ->emailField('email')
        ->emailField('confirmEmail')
        ->create();
    $form = syncedMatchReload($form->id);
    $field = $form->getFieldByHandle('confirmEmail');
    $stored = References::field((string)$form->getFieldByHandle('email')->reference);
    $read = fn(string $table, int $id): array => Json::decode((string)(new Query())->select('settings')->from($table)->where(['id' => $id])->scalar());

    // The stored shape before this change: on the definition, with nothing on the form's row.
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FIELDS, ['settings' => Json::encode(['matchField' => $stored] + $read(Table::FORMIE_FIELDS, $field->definitionId))], ['id' => $field->definitionId])->execute();
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORM_FIELDS, ['settings' => Json::encode(array_diff_key($read(Table::FORMIE_FORM_FIELDS, $field->id), ['matchField' => true]))], ['id' => $field->id])->execute();

    $form = syncedMatchReload($form->id);
    expect(syncedMatchErrors($form, 'one@example.test', 'two@example.test'))->not->toBeEmpty();

    foreach ([1, 2] as $run) {
        expect((new m261124_000000_per_form_field_settings())->safeUp())->toBeTrue();
        $form = syncedMatchReload($form->id);

        expect($read(Table::FORMIE_FORM_FIELDS, $field->id)['matchField'] ?? null)->toBe($stored)
            ->and($read(Table::FORMIE_FIELDS, $field->definitionId))->not->toHaveKey('matchField')
            ->and(syncedMatchErrors($form, 'one@example.test', 'two@example.test'))->not->toBeEmpty()
            ->and(syncedMatchErrors($form, 'one@example.test', 'one@example.test'))->toBeEmpty();
    }
});
