<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use verbb\formie\elements\Form;
use verbb\formie\fields\Calculations;
use verbb\formie\fields\MissingField;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261124_000000_per_form_field_settings;
use verbb\formie\models\RichText;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Stencils as StencilsService;

/**
 * Form A has a condition and a formula pointing at its own fields, and a stencil of synced placements
 * of A's fields, serialized as the stencil builder saves them. Issue #3159.
 */
function syncedConditionsSource(): array
{
    $suffix = uniqid();
    $form = formie()->form(['title' => 'Synced Conditions A', 'handle' => 'syncedConditionsA' . $suffix])
        ->radioField('paymentMethod', ['options' => [
            ['label' => 'Card', 'value' => 'card'],
            ['label' => 'Bank transfer', 'value' => 'bankTransfer'],
        ]])
        ->singleLineTextField('accountNumber')
        ->numberField('amountA')
        ->numberField('amountB')
        ->calculationsField('total')
        ->create();

    $form = syncedConditionsReload($form->id);
    $account = $form->getFieldByHandle('accountNumber');
    $account->enableConditions = true;
    $account->conditions = syncedConditionsRule($form, 'bankTransfer');
    $form->getFieldByHandle('total')->formula = RichText::from(syncedConditionsFormula($form));
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $pages = StencilData::getSerializedLayout(syncedConditionsReload($form->id)->getFormLayout());

    foreach ($pages as &$page) {
        foreach ($page['rows'] as &$row) {
            foreach ($row['fields'] as &$field) {
                $field['syncedDefinitionUid'] = $field['definitionUid'];
            }
        }
    }
    unset($page, $row, $field);

    $stencil = new Stencil(['name' => 'Synced Conditions', 'handle' => 'syncedConditions' . $suffix, 'scope' => StencilsService::SCOPE_SITE]);
    $stencil->data = new StencilData(['pages' => $pages]);

    return [syncedConditionsReload($form->id), $stencil];
}

function syncedConditionsRule(Form $form, string $value): array
{
    return ['version' => 1, 'showRule' => 'show', 'conditionRule' => 'all', 'conditions' => [
        ['id' => 'synced-001', 'field' => '{field:' . $form->getFieldByHandle('paymentMethod')->reference . '}', 'condition' => '=', 'value' => $value],
    ]];
}

function syncedConditionsFormula(Form $form): string
{
    return '{field:' . $form->getFieldByHandle('amountA')->reference . '} * 1 + {field:' . $form->getFieldByHandle('amountB')->reference . '} * 1';
}

function syncedConditionsFromStencil(Stencil $stencil, string $label): Form
{
    $form = new Form(['title' => 'Synced Conditions ' . $label, 'handle' => 'syncedConditions' . $label . uniqid()]);
    $stencil->applyStencilToForm($form, true);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    return syncedConditionsReload($form->id);
}

function syncedConditionsReload(int $id): Form
{
    // Rows change underneath the service in these tests, so drop Formie's request field cache too.
    Craft::$app->getElements()->invalidateAllCaches();
    \verbb\formie\Formie::$plugin->getFields()->resetFieldRegistryCache();

    return Form::find()->id($id)->status(null)->one();
}

// The condition target and formula variables each form resolves, against its own fields.
function syncedConditionsState(Form $form): array
{
    $account = $form->getFieldByHandle('accountNumber');

    return [
        'enabled' => $account->enableConditions,
        'ownTarget' => ($account->conditions['conditions'][0]['field'] ?? null) === '{field:' . $form->getFieldByHandle('paymentMethod')->reference . '}',
        'value' => $account->conditions['conditions'][0]['value'] ?? null,
        'variables' => array_column($form->getFieldByHandle('total')->getFormula()['variables'], 'sourceKey'),
    ];
}

it('keeps each form’s conditions and formula on its own fields when a stencil of synced fields is used again', function(): void {
    [$a, $stencil] = syncedConditionsSource();
    $b = syncedConditionsFromStencil($stencil, 'B');
    $c = syncedConditionsFromStencil($stencil, 'C');

    expect($b->getFieldByHandle('accountNumber')->definitionId)->toBe($a->getFieldByHandle('accountNumber')->definitionId)
        ->and($c->getFieldByHandle('total')->definitionId)->toBe($a->getFieldByHandle('total')->definitionId);

    foreach ([$a, $b, $c] as $form) {
        expect(syncedConditionsState(syncedConditionsReload($form->id)))->toBe([
            'enabled' => true,
            'ownTarget' => true,
            'value' => 'bankTransfer',
            'variables' => ['amountA', 'amountB'],
        ]);
    }
});

it('saves a synced field’s conditions for one form without changing the others', function(): void {
    [$a, $stencil] = syncedConditionsSource();
    $b = syncedConditionsFromStencil($stencil, 'B');

    $b->getFieldByHandle('accountNumber')->conditions = syncedConditionsRule($b, 'card');
    expect(Craft::$app->getElements()->saveElement($b))->toBeTrue();

    expect(syncedConditionsState(syncedConditionsReload($b->id))['value'])->toBe('card')
        ->and(syncedConditionsState(syncedConditionsReload($a->id)))->toMatchArray(['ownTarget' => true, 'value' => 'bankTransfer']);
});

it('moves definition conditions and formulas to each form, pointing at that form’s fields', function(): void {
    [$a, $stencil] = syncedConditionsSource();
    $b = syncedConditionsFromStencil($stencil, 'B');

    // The stored shape before this change: settings on the definition, holding the last saved form's references.
    $legacy = ['accountNumber' => ['enableConditions' => true, 'conditions' => syncedConditionsRule($b, 'bankTransfer')],
        'total' => ['formula' => RichText::from(syncedConditionsFormula($b))->getSchema()]];

    foreach ($legacy as $handle => $moved) {
        $definitionId = $a->getFieldByHandle($handle)->definitionId;
        $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $definitionId])->scalar());
        Db::update(Table::FORMIE_FIELDS, ['settings' => Json::encode(array_merge($settings, $moved))], ['id' => $definitionId]);

        foreach ((new Query())->select(['id', 'settings'])->from(Table::FORMIE_FORM_FIELDS)->where(['fieldId' => $definitionId])->all() as $row) {
            Db::update(Table::FORMIE_FORM_FIELDS, ['settings' => Json::encode(array_diff_key(Json::decode($row['settings']), $moved))], ['id' => $row['id']]);
        }
    }

    foreach ([1, 2] as $run) {
        expect((new m261124_000000_per_form_field_settings())->safeUp())->toBeTrue();

        foreach ([$a, $b] as $form) {
            expect(syncedConditionsState(syncedConditionsReload($form->id)))->toBe([
                'enabled' => true,
                'ownTarget' => true,
                'value' => 'bankTransfer',
                'variables' => ['amountA', 'amountB'],
            ]);
        }

        $definition = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $a->getFieldByHandle('total')->definitionId])->scalar());
        expect($definition)->not->toHaveKey('formula')->not->toHaveKey('conditions');
    }
});

it('keeps placement settings of an unavailable field type when it is saved', function(): void {
    $formula = RichText::from('{field:amountA} * 2')->getSchema();
    $field = new MissingField(['expectedType' => Calculations::class, 'handle' => 'total', 'settings' => []]);
    $field->applyInstanceSettings(['required' => true, 'formula' => $formula, 'enableConditions' => true, 'conditions' => ['version' => 1]]);

    expect($field->getInstanceSettings())->toMatchArray(['required' => true, 'formula' => $formula, 'enableConditions' => true, 'conditions' => ['version' => 1]]);
});

it('keeps the conditions and formula of an unavailable field type through a builder save and a copy', function(): void {
    [$a] = syncedConditionsSource();
    $types = [];

    // Make both fields' types unavailable, as when a plugin is uninstalled or a type is disabled.
    foreach (['accountNumber', 'total'] as $handle) {
        $definitionId = $a->getFieldByHandle($handle)->definitionId;
        $types[$definitionId] = (new Query())->select('type')->from(Table::FORMIE_FIELDS)->where(['id' => $definitionId])->scalar();
        Db::update(Table::FORMIE_FIELDS, ['type' => 'absent\\Field' . ucfirst($handle)], ['id' => $definitionId]);
    }

    $missing = syncedConditionsReload($a->id);
    expect($missing->getFieldByHandle('total'))->toBeInstanceOf(MissingField::class);

    $missing->getFormLayout()->setPages((new \verbb\formie\helpers\FormSerializer())->hydrateBuilder($missing->getFormLayout()->getFormBuilderConfig(), $missing));
    expect(Craft::$app->getElements()->saveElement($missing))->toBeTrue();

    // Copies (duplicate, stencil, export) carry the placement settings in the document.
    $serializer = new \verbb\formie\helpers\FormSerializer();
    $copied = $serializer->prepareCopy(['pages' => $serializer->serializeLayout(syncedConditionsReload($a->id)->getFormLayout())], 'duplicate');
    $copiedFields = array_merge(...array_column($copied['pages'][0]['rows'], 'fields'));
    $copiedTotal = array_values(array_filter($copiedFields, fn($field) => ($field['expectedType'] ?? null) === 'absent\\FieldTotal'))[0];
    $copiedReferences = array_column($copiedFields, 'reference', 'handle');

    // A copy gives every field a new reference, and the formula follows the copy's own fields.
    expect(RichText::from($copiedTotal['instanceSettings']['formula'] ?? null)->toPlainText())
        ->toBe('{field:' . $copiedReferences['amountA'] . '} * 1 + {field:' . $copiedReferences['amountB'] . '} * 1');

    foreach ($types as $definitionId => $type) {
        Db::update(Table::FORMIE_FIELDS, ['type' => $type], ['id' => $definitionId]);
    }

    expect(syncedConditionsState(syncedConditionsReload($a->id)))->toBe([
        'enabled' => true,
        'ownTarget' => true,
        'value' => 'bankTransfer',
        'variables' => ['amountA', 'amountB'],
    ]);
});

it('gives the other forms their copy when saving a field whose definition still holds conditions', function(): void {
    [$a, $stencil] = syncedConditionsSource();
    $b = syncedConditionsFromStencil($stencil, 'B');
    $account = $a->getFieldByHandle('accountNumber');

    // Left on the definition, as for a field type that was unavailable when updating. Both forms read it from there.
    $definition = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $account->definitionId])->scalar());
    $definition['conditions'] = syncedConditionsRule($b, 'bankTransfer');
    $definition['enableConditions'] = true;
    Db::update(Table::FORMIE_FIELDS, ['settings' => Json::encode($definition)], ['id' => $account->definitionId]);

    foreach ((new Query())->select(['id', 'settings'])->from(Table::FORMIE_FORM_FIELDS)->where(['fieldId' => $account->definitionId])->all() as $row) {
        Db::update(Table::FORMIE_FORM_FIELDS, ['settings' => Json::encode(array_diff_key(Json::decode($row['settings']), array_flip(['enableConditions', 'conditions'])))], ['id' => $row['id']]);
    }

    expect(syncedConditionsState(syncedConditionsReload($b->id))['ownTarget'])->toBeTrue();

    // Saving A takes the conditions off the definition. B keeps the rule it had.
    expect(Craft::$app->getElements()->saveElement(syncedConditionsReload($a->id)))->toBeTrue();

    expect(Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $account->definitionId])->scalar()))->not->toHaveKey('conditions')
        ->and(syncedConditionsState(syncedConditionsReload($b->id)))->toMatchArray(['enabled' => true, 'ownTarget' => true, 'value' => 'bankTransfer']);
});

it('reuses synced fields when importing an unchanged export from before conditions moved to each form', function(): void {
    [$a, $stencil] = syncedConditionsSource();
    $b = syncedConditionsFromStencil($stencil, 'B');
    $current = \verbb\formie\helpers\ImportExportHelper::generateFormExport($b);
    $older = $current;

    // Older exports kept these settings with the definition settings and only Required per form.
    foreach ($older['pages'] as &$page) {
        foreach ($page['rows'] as &$row) {
            foreach ($row['fields'] as &$field) {
                foreach (['enableConditions', 'conditions', 'formula'] as $key) {
                    if (array_key_exists($key, $field['instanceSettings'] ?? [])) {
                        $field['settings'][$key] = $field['instanceSettings'][$key];
                        unset($field['instanceSettings'][$key]);
                    }
                }
            }
        }
    }
    unset($page, $row, $field);

    foreach ([$current, $older] as $document) {
        $statuses = array_column((new \verbb\formie\helpers\SyncedFieldImport($document))->preview(), 'status');

        expect($statuses)->not->toBeEmpty()
            ->and(array_unique($statuses))->toBe(['reuse']);
    }
});

it('keeps later edits to a synced field when a form is created from an older stencil', function(): void {
    [$a, $stencil] = syncedConditionsSource();
    $b = syncedConditionsFromStencil($stencil, 'B');

    // Edited after the stencil was saved. The edit is synced to B.
    $field = $a->getFieldByHandle('paymentMethod');
    $field->label = 'How will you pay?';
    $field->options = [
        ['label' => 'Card', 'value' => 'card'],
        ['label' => 'Bank transfer', 'value' => 'bankTransfer'],
        ['label' => 'Cash', 'value' => 'cash'],
    ];
    expect(Craft::$app->getElements()->saveElement($a))->toBeTrue();

    $c = syncedConditionsFromStencil($stencil, 'C');

    foreach ([$a, $b, $c] as $form) {
        $field = syncedConditionsReload($form->id)->getFieldByHandle('paymentMethod');

        expect($field->label)->toBe('How will you pay?')
            ->and(array_column($field->options, 'value'))->toBe(['card', 'bankTransfer', 'cash']);
    }

    // Settings that belong to each form still come from the stencil.
    expect(syncedConditionsState(syncedConditionsReload($c->id)))->toBe([
        'enabled' => true,
        'ownTarget' => true,
        'value' => 'bankTransfer',
        'variables' => ['amountA', 'amountB'],
    ]);
});

// A direct upgrade from Formie 2 saves each form's layout early, then converts Formie 2's tokens on field definitions.
// Until the update that moves these settings to each form has run, saves have to keep them where those updates look.
it('keeps conditions and formulas on the definition until the update that moves them has run', function(): void {
    $history = ['track' => 'plugin:formie', 'name' => 'm261124_000000_per_form_field_settings'];
    $row = (new Query())->from(\craft\db\Table::MIGRATIONS)->where($history)->one();
    expect($row)->not->toBeNull();
    Db::delete(\craft\db\Table::MIGRATIONS, $history);
    \verbb\formie\Formie::$plugin->getFields()->resetFieldRegistryCache();

    $stored = function(Form $form, string $handle): array {
        $field = $form->getFieldByHandle($handle);

        return [
            Json::decode((string)(new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $field->definitionId])->scalar()),
            Json::decode((string)(new Query())->select('settings')->from(Table::FORMIE_FORM_FIELDS)->where(['id' => $field->id])->scalar()),
        ];
    };

    try {
        [$a] = syncedConditionsSource();
        [$definition, $placement] = $stored($a, 'accountNumber');
        [$formulaDefinition, $formulaPlacement] = $stored($a, 'total');

        expect($definition)->toHaveKey('conditions')
            ->and($placement)->not->toHaveKey('conditions')
            ->and($formulaDefinition)->toHaveKey('formula')
            ->and($formulaPlacement)->not->toHaveKey('formula')
            ->and(syncedConditionsState($a)['ownTarget'])->toBeTrue();

        // The update then moves them to the form, and saves follow.
        expect((new m261124_000000_per_form_field_settings())->safeUp())->toBeTrue();
        Db::insert(\craft\db\Table::MIGRATIONS, $row);
        \verbb\formie\Formie::$plugin->getFields()->resetFieldRegistryCache();
        $a = syncedConditionsReload($a->id);
        expect(Craft::$app->getElements()->saveElement($a))->toBeTrue();
        [$definition, $placement] = $stored(syncedConditionsReload($a->id), 'accountNumber');

        expect($definition)->not->toHaveKey('conditions')
            ->and($placement)->toHaveKey('conditions')
            ->and(syncedConditionsState(syncedConditionsReload($a->id)))->toBe([
                'enabled' => true,
                'ownTarget' => true,
                'value' => 'bankTransfer',
                'variables' => ['amountA', 'amountB'],
            ]);
    } finally {
        if (!(new Query())->from(\craft\db\Table::MIGRATIONS)->where($history)->exists()) {
            Db::insert(\craft\db\Table::MIGRATIONS, $row);
        }

        \verbb\formie\Formie::$plugin->getFields()->resetFieldRegistryCache();
    }
});

// The repair points a rule at the field with the same handle in its own form. Where that field comes after the
// conditional field, a rule written in Formie 4 would make the form reject every submission.
it('keeps a form valid when the update repairs a condition onto a later field', function(): void {
    [$a, $stencil] = syncedConditionsSource();

    // Form B has the same synced fields with the conditional field first, and no conditions of its own yet.
    $pages = StencilData::getSerializedLayout($a->getFormLayout());
    $handle = fn(array $field): string => (string)($field['handle'] ?? $field['settings']['handle'] ?? '');

    foreach ($pages[0]['rows'] as &$row) {
        usort($row['fields'], fn($left, $right) => (int)($handle($right) === 'accountNumber') <=> (int)($handle($left) === 'accountNumber'));

        foreach ($row['fields'] as $index => &$field) {
            $field['syncedDefinitionUid'] = $field['definitionUid'];

            if (array_key_exists('sortOrder', $field)) {
                $field['sortOrder'] = $index;
            }

            foreach (['settings', 'instanceSettings'] as $key) {
                if (isset($field[$key]) && is_array($field[$key])) {
                    unset($field[$key]['conditions']);
                    $field[$key]['enableConditions'] = false;
                }
            }

            unset($field['conditions']);
            $field['enableConditions'] = false;
        }
    }
    unset($row, $field);

    $reordered = new Stencil(['name' => 'Synced Conditions reordered', 'handle' => 'syncedConditionsReordered' . uniqid(), 'scope' => StencilsService::SCOPE_SITE]);
    $reordered->data = new StencilData(['pages' => $pages]);
    $b = syncedConditionsFromStencil($reordered, 'B');
    $handles = array_values(array_map(fn($field) => $field->handle, $b->getFields()));
    expect(array_search('accountNumber', $handles, true))->toBeLessThan(array_search('paymentMethod', $handles, true));

    // The stored shape before conditions moved to each form: on the definition, holding form A's references.
    $definitionId = $a->getFieldByHandle('accountNumber')->definitionId;
    $moved = ['enableConditions' => true, 'conditions' => syncedConditionsRule($a, 'bankTransfer')];
    $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $definitionId])->scalar());
    Db::update(Table::FORMIE_FIELDS, ['settings' => Json::encode(array_merge($settings, $moved))], ['id' => $definitionId]);

    foreach ((new Query())->select(['id', 'settings'])->from(Table::FORMIE_FORM_FIELDS)->where(['fieldId' => $definitionId])->all() as $row) {
        Db::update(Table::FORMIE_FORM_FIELDS, ['settings' => Json::encode(array_diff_key(Json::decode($row['settings']), $moved))], ['id' => $row['id']]);
    }

    expect((new m261124_000000_per_form_field_settings())->safeUp())->toBeTrue();
    $b = syncedConditionsReload($b->id);
    $rule = $b->getFieldByHandle('accountNumber')->conditions['conditions'][0];

    expect($rule['field'])->toBe('{field:' . $b->getFieldByHandle('paymentMethod')->reference . '}')
        ->and(fn() => (new \verbb\formie\conditions\ConditionGraph())->orderedFields($b))->not->toThrow(RuntimeException::class);

    $b->validateFormLayout();
    expect($b->getErrors('formLayout'))->toBe([])
        // Form A's own rule is untouched.
        ->and(syncedConditionsReload($a->id)->getFieldByHandle('accountNumber')->conditions['conditions'][0])->not->toHaveKey('legacyForward');
});

// Only a synced field was given another form's references. A field used in one form keeps what it has, even a
// rule naming a field in another form, which did nothing before the update and mustn't start working because of it.
it('leaves a rule naming another form’s field alone on a field that isn’t synced', function(): void {
    [$a] = syncedConditionsSource();
    $c = formie()->form(['title' => 'Unsynced Conditions C', 'handle' => 'unsyncedConditionsC' . uniqid()])
        ->radioField('paymentMethod', ['options' => [
            ['label' => 'Card', 'value' => 'card'],
            ['label' => 'Bank transfer', 'value' => 'bankTransfer'],
        ]])
        ->singleLineTextField('accountNumber')
        ->create();
    $c = syncedConditionsReload($c->id);
    $field = $c->getFieldByHandle('accountNumber');

    expect((new Query())->from(Table::FORMIE_FORM_FIELDS)->where(['fieldId' => $field->definitionId])->count())->toEqual(1);

    // The stored shape before conditions moved to each form: on the definition, naming form A's field.
    $moved = ['enableConditions' => true, 'conditions' => syncedConditionsRule($a, 'bankTransfer')];
    $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $field->definitionId])->scalar());
    Db::update(Table::FORMIE_FIELDS, ['settings' => Json::encode(array_merge($settings, $moved))], ['id' => $field->definitionId]);
    $placement = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORM_FIELDS)->where(['id' => $field->id])->scalar());
    Db::update(Table::FORMIE_FORM_FIELDS, ['settings' => Json::encode(array_diff_key($placement, $moved))], ['id' => $field->id]);

    foreach ([1, 2] as $run) {
        expect((new m261124_000000_per_form_field_settings())->safeUp())->toBeTrue();
        $rule = syncedConditionsReload($c->id)->getFieldByHandle('accountNumber')->conditions['conditions'][0];

        expect($rule['field'])->toBe('{field:' . $a->getFieldByHandle('paymentMethod')->reference . '}')
            ->and($rule)->not->toHaveKey('legacyForward');
    }
});
