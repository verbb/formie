<?php

declare(strict_types=1);

use Tests\Support\WebRequestTestHelper;
use verbb\formie\client\models\LoadContext;
use verbb\formie\elements\Form;
use verbb\formie\fields\Address;
use verbb\formie\fields\Email;
use verbb\formie\fields\Name;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Stencils;

function workstream11FieldMap(Form $form): array
{
    $map = [];

    foreach ($form->getFieldsRecursively() as $field) {
        $map[implode('.', $field->getFieldPath()->handlePath())] = $field;
    }

    return $map;
}

it('round trips the complete portable form graph without mutating it during render or export', function (): void {
    $sharedSource = formie()->form(['title' => 'Portable shared definition'])->emailField('sharedEmail')->create();
    $sharedField = $sharedSource->getFieldByHandle('sharedEmail');
    $syncedConfig = $sharedField->getFormBuilderConfig();
    unset(
        $syncedConfig['id'],
        $syncedConfig['uid'],
        $syncedConfig['reference'],
        $syncedConfig['layoutId'],
        $syncedConfig['pageId'],
        $syncedConfig['rowId'],
    );
    $syncedConfig['definitionId'] = $sharedField->definitionId;
    $syncedConfig['isSynced'] = true;

    $form = formie()
        ->form(['title' => 'Workstream 11 portable graph'])
        ->multiPage(2)
        ->onPage(1)
        ->singleLineTextField('source')
        ->singleLineTextField('conditional', [
            'enableConditions' => true,
            'conditions' => [
                'version' => 1,
                'conditionRule' => 'all',
                'showRule' => 'show',
                'conditions' => [[
                    'field' => 'source',
                    'condition' => '=',
                    'value' => 'yes',
                ]],
            ],
        ])
        ->nameField('person', [
            'useMultipleFields' => true,
            'rows' => (new Name(['useMultipleFields' => true]))->getSubFields(),
        ])
        ->addressField('address', [
            'rows' => (new Address())->getSubFields(),
        ])
        ->repeaterField('items', ['rows' => [[
            'fields' => [[
                'type' => SingleLineText::class,
                'handle' => 'description',
                'label' => 'Description',
            ], [
                'type' => Email::class,
                'handle' => 'contact',
                'label' => 'Contact',
            ]],
        ]]])
        ->onPage(2)
        ->entriesField('relatedEntries')
        ->addFieldConfig($syncedConfig)
        ->settings([
            'submissionTitleFormat' => 'Portable {field:source}',
            'integrations' => [
                'portableFixture' => [
                    'enabled' => true,
                    'fieldMapping' => ['value' => '{field:source}'],
                ],
            ],
        ])
        ->create();

    $before = ImportExportHelper::generateFormExport($form);
    $sourceFields = workstream11FieldMap($form);

    WebRequestTestHelper::withWebRequestContext(function () use ($form): void {
        $html = (string)Formie::$plugin->getRendering()->renderForm($form, [
            'theme' => 'none',
            'themeConfig' => ['form' => ['class' => 'must-not-be-saved']],
            'includeCss' => false,
            'includeJs' => false,
        ]);
        $bootstrap = Formie::$plugin->getClientFormBootstrapBuilder()
            ->build($form, new LoadContext(['handle' => $form->handle]))
            ->toArrayRecursive();

        expect($html)->toContain('data-formie-form')
            ->and($bootstrap['definition']['pages'])->toHaveCount(2)
            ->and(json_encode($bootstrap['definition'], JSON_THROW_ON_ERROR))->toContain('repeatable-parent', 'fixed-parent');
    });

    expect(ImportExportHelper::generateFormExport($form))->toBe($before)
        ->and($before)->not->toHaveKey('theme')
        ->and(json_encode($before, JSON_THROW_ON_ERROR))->not->toContain('must-not-be-saved');

    $duplicate = Formie::$plugin->getForms()->duplicateForm($form);
    $import = ImportExportHelper::createFromImport($before);

    $stencil = new Stencil([
        'name' => 'Workstream 11 portable stencil',
        'handle' => 'workstream11Portable' . uniqid(),
        'scope' => Stencils::SCOPE_SITE,
    ]);
    $stencil->data = new StencilData();
    $stencil->data->populateFormData($form);
    $fromStencil = Formie::$plugin->getStencils()->createFromStencil($stencil, [
        'title' => 'Workstream 11 stencil result',
        'handle' => 'workstream11StencilResult' . uniqid(),
    ]);
    expect(Craft::$app->getElements()->saveElement($fromStencil))->toBeTrue();

    // Forms are database content, but project-scoped stencils are the supported
    // project-config portability path for a complete form definition.
    $projectStencil = new Stencil([
        'name' => 'Workstream 11 project stencil',
        'handle' => 'workstream11ProjectStencil' . uniqid(),
        'scope' => Stencils::SCOPE_PROJECT,
    ]);
    $projectStencil->data = new StencilData();
    $projectStencil->data->populateFormData($form);
    expect(Formie::$plugin->getStencils()->saveStencil($projectStencil))->toBeTrue();

    $projectStencilConfig = Craft::$app->getProjectConfig()->get(
        Stencils::CONFIG_STENCILS_KEY . '.' . $projectStencil->uid,
        true,
    );
    $projectStencilJson = json_encode($projectStencilConfig, JSON_THROW_ON_ERROR);
    expect($projectStencilConfig['data']['pages'] ?? null)->toHaveCount(2)
        ->and($projectStencilJson)->toContain($sharedField->definitionUid)
        ->and($projectStencilJson)->not->toContain('must-not-be-saved');

    $fromProjectStencil = Formie::$plugin->getStencils()->createFromStencil($projectStencil, [
        'title' => 'Workstream 11 project-config stencil result',
        'handle' => 'workstream11ProjectStencilResult' . uniqid(),
    ]);
    expect(Craft::$app->getElements()->saveElement($fromProjectStencil))->toBeTrue();

    foreach ([$duplicate, $import->form, $fromStencil, $fromProjectStencil] as $portableForm) {
        $portableFields = workstream11FieldMap($portableForm);
        $mapping = $portableForm->settings->integrations['portableFixture']['fieldMapping']['value'] ?? null;
        $mappingReference = is_array($mapping) ? ($mapping['value'] ?? null) : $mapping;

        expect(array_keys($portableFields))->toBe(array_keys($sourceFields))
            ->and($portableForm->getPages())->toHaveCount(2)
            ->and($portableForm->settings->submissionTitleFormat)->not->toBe($form->settings->submissionTitleFormat)
            ->and($portableForm->settings->submissionTitleFormat)->toContain('{field:')
            ->and($mappingReference)->toContain('{field:');

        foreach ($portableFields as $path => $field) {
            expect($field->id)->not->toBe($sourceFields[$path]->id)
                ->and($field->uid)->not->toBe($sourceFields[$path]->uid)
                ->and($field->reference)->not->toBe($sourceFields[$path]->reference);
        }

        $conditionReference = $portableForm->getFieldByHandle('conditional')->getConditions()['conditions'][0]['field'] ?? null;
        expect(in_array($conditionReference, [
            'source',
            $portableForm->getFieldByHandle('source')->reference,
        ], true))->toBeTrue()
            ->and($conditionReference)->not->toBe($form->getFieldByHandle('source')->reference);
    }

    foreach ([$form, $fromStencil, $fromProjectStencil] as $syncedForm) {
        expect($syncedForm->getFieldByHandle('sharedEmail')->definitionUid)->toBe($sharedField->definitionUid)
            ->and($syncedForm->getFieldByHandle('sharedEmail')->getIsSynced())->toBeTrue();
    }

    expect($import->remaps)->toHaveKey($form->getFieldByHandle('source')->reference)
        ->and($import->form->getFieldByHandle('person')->getFields())->not->toBeEmpty()
        ->and($import->form->getFieldByHandle('address')->getFields())->not->toBeEmpty()
        ->and($import->form->getFieldByHandle('items')->getFields())->toHaveCount(2);
});
