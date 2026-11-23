<?php

declare(strict_types=1);

use craft\db\Query;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\FieldTraversal;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\helpers\Table;
use verbb\formie\migrations\m261115_030000_stencil_condition_tokens;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Stencils as StencilsService;

function stencilTokenData(): array
{
    $form = formie()->form()
        ->groupField('contact', ['rows' => [['fields' => [
            ['type' => SingleLineText::class, 'handle' => 'position', 'label' => 'Position'],
            ['type' => SingleLineText::class, 'handle' => 'detail', 'label' => 'Detail', 'enableConditions' => true, 'conditions' => stencilTokenRule('{contact.rows.new1.fields.position}')],
        ]]]])
        ->singleLineTextField('plain')
        ->singleLineTextField('bare', ['enableConditions' => true, 'conditions' => stencilTokenRule('{plain}')])
        ->singleLineTextField('unknown', ['enableConditions' => true, 'conditions' => stencilTokenRule('{nothing.rows.new1.fields.here}')])
        ->singleLineTextField('current', ['enableConditions' => true, 'conditions' => stencilTokenRule('{field:plain}')])
        ->create();
    $stencilData = new StencilData();
    $stencilData->populateFormData($form);
    $data = Json::decode(Json::encode($stencilData->getSerializedData()));
    Craft::$app->getElements()->deleteElement($form, true);

    return $data;
}

function stencilTokenRule(string $token): array
{
    return ['version' => 1, 'showRule' => 'show', 'conditionRule' => 'all', 'conditions' => [['id' => 'new1-2', 'field' => $token, 'condition' => '=', 'value' => 'Yes', 'legacyForward' => true]]];
}

function stencilTokens(array $data): array
{
    $tokens = [];
    array_walk_recursive($data, static function(mixed $value, string|int $key) use (&$tokens): void {
        if ($key === 'field' && is_string($value)) {
            $tokens[] = $value;
        }
    });
    sort($tokens);

    return $tokens;
}

function storedStencilData(int $id): array
{
    return Json::decode((new Query())->select('data')->from(Table::FORMIE_STENCILS)->where(['id' => $id])->scalar());
}

function appliedStencilConditions(array $data): array
{
    $form = new Form();
    (new StencilData($data))->populateToForm($form, true);
    $conditions = [];

    foreach (FieldTraversal::recursively($form->getFields()) as $field) {
        if ($field->hasConditions()) {
            $conditions[$field->handle] = [$field->getConditions()['conditions'][0]['field'], $field];
        }
    }

    return [$form, $conditions];
}

it('converts old condition tokens in stored stencils, so new forms read the field they name, and can run twice', function(): void {
    $data = stencilTokenData();
    $stencil = new Stencil(['name' => 'Tokens', 'handle' => 'tokens' . bin2hex(random_bytes(3)), 'scope' => StencilsService::SCOPE_SITE, 'data' => new StencilData($data)]);
    expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_STENCILS, ['data' => Json::encode($data)], ['id' => $stencil->id])->execute();

    try {
        foreach ([1, 2] as $run) {
            expect((new m261115_030000_stencil_condition_tokens())->safeUp())->toBeTrue();
            expect(stencilTokens(storedStencilData($stencil->id)))->toBe(['{field:contact.position}', '{field:plain}', '{field:plain}', '{nothing.rows.new1.fields.here}']);
        }

        [$form, $conditions] = appliedStencilConditions(storedStencilData($stencil->id));

        expect($conditions['detail'][0])->toBe('{field:' . $form->getFieldByHandle('contact')->getFieldByHandle('position')->reference . '}')
            ->and($conditions['bare'][0])->toBe('{field:' . $form->getFieldByHandle('plain')->reference . '}')
            ->and($conditions['unknown'][0])->toBe('{nothing.rows.new1.fields.here}');
    } finally {
        Formie::$plugin->getStencils()->deleteStencilById($stencil->id);
    }
});

it('converts project stencils in project config too, unless the config is already ahead of this version', function(string $schema, bool $converts): void {
    $projectConfig = Craft::$app->getProjectConfig();
    $data = stencilTokenData();
    $stencil = new Stencil(['name' => 'Project tokens', 'handle' => 'projectTokens' . bin2hex(random_bytes(3)), 'scope' => StencilsService::SCOPE_PROJECT, 'data' => new StencilData($data)]);
    expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();
    // Stored the way a real stencil is, with the whole entry packed rather than just its data.
    $path = StencilsService::CONFIG_STENCILS_KEY . '.' . $stencil->uid;
    $projectConfig->set($path, ProjectConfig::packAssociativeArray(['data' => $data, 'handle' => $stencil->handle, 'name' => $stencil->name]));
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_STENCILS, ['data' => Json::encode($data)], ['id' => $stencil->id])->execute();
    $originalSchema = $projectConfig->get('plugins.formie.schemaVersion', true);
    $projectConfig->set('plugins.formie.schemaVersion', $schema === 'current' ? Formie::$plugin->schemaVersion : $schema);

    try {
        foreach ([1, 2] as $run) {
            expect((new m261115_030000_stencil_condition_tokens())->safeUp())->toBeTrue();
        }

        $stored = ProjectConfig::unpackAssociativeArray((array)$projectConfig->get($path));

        expect($stored['handle'])->toBe($stencil->handle)->and(stencilTokens($stored['data']))->toBe($converts
            ? ['{field:contact.position}', '{field:plain}', '{field:plain}', '{nothing.rows.new1.fields.here}']
            : ['{contact.rows.new1.fields.position}', '{field:plain}', '{nothing.rows.new1.fields.here}', '{plain}']);
    } finally {
        $projectConfig->set('plugins.formie.schemaVersion', $originalSchema);
        Formie::$plugin->getStencils()->deleteStencilById($stencil->id);
    }
})->with([
    'older config' => ['4.0.0', true],
    'config at this version' => ['current', false],
]);

it('reads the converted token when a new form is made from the stencil and submitted', function(): void {
    $data = stencilTokenData();
    $form = new Form();
    $form->title = 'From stencil';
    $form->handle = 'fromStencil' . bin2hex(random_bytes(3));
    (new StencilData($data))->populateToForm($form, true);
    expect(Craft::$app->getElements()->saveElement($form, false))->toBeTrue();
    $form = Formie::$plugin->getForms()->getFormById($form->id);
    $submission = new \verbb\formie\elements\Submission();
    $submission->setForm($form);
    $submission->setFieldValue('contact', ['position' => 'Yes']);
    $detail = $form->getFieldByHandle('contact')->getFieldByHandle('detail');

    expect($detail->isConditionallyHidden($submission))->toBeFalse();

    $submission = new \verbb\formie\elements\Submission();
    $submission->setForm($form);
    $submission->setFieldValue('contact', ['position' => 'No']);

    expect($detail->isConditionallyHidden($submission))->toBeTrue();
});

it('converts old condition tokens when importing an export from an older version', function(): void {
    $source = formie()->form()
        ->singleLineTextField('plain')
        ->singleLineTextField('bare')
        ->create();
    $export = ImportExportHelper::generateFormExport($source);
    $export['handle'] = 'conditionImport' . bin2hex(random_bytes(3));
    $export['title'] = 'Condition import';
    $encode = static function(array $pages): array {
        foreach ($pages as &$page) {
            foreach ($page['rows'] as &$row) {
                foreach ($row['fields'] as &$field) {
                    if (($field['handle'] ?? $field['settings']['handle'] ?? '') === 'bare') {
                        $field['settings']['enableConditions'] = true;
                        $field['settings']['conditions'] = stencilTokenRule('{plain}');
                        // Exports before 4.0.114 only had Required as a per-form setting.
                        unset($field['instanceSettings']['enableConditions'], $field['instanceSettings']['conditions']);
                    }
                }
            }
        }

        return $pages;
    };
    $export['pages'] = $encode($export['pages']);

    $form = ImportExportHelper::createFormFromImport($export);

    expect($form->getFieldByHandle('bare')->getConditions()['conditions'][0]['field'])->toBe('{field:' . $form->getFieldByHandle('plain')->reference . '}');
});
