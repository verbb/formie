<?php

declare(strict_types=1);

use craft\db\Query;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;

it('captures forms and fields service baselines for synthetic volume', function (string $profileLabel, int $formCount, int $fieldCount): void {
    seedFormsFieldsPerfForms($formCount, $fieldCount);

    $formsService = Formie::$plugin->getForms();
    $fieldsService = Formie::$plugin->getFields();

    $forms = measureFormsFieldsPerfPhase(function () use ($formsService): array {
        return $formsService->getAllForms();
    })['result'];

    $formIds = array_values(array_map(static fn($form): int => (int)$form->id, $forms));
    $layoutIds = array_values(array_filter(array_map(static fn($form): int => (int)$form->layoutId, $forms)));

    $formsPlain = measureFormsFieldsPerfPhase(function () use ($formsService): array {
        return $formsService->getAllForms();
    });
    $formsWithLayouts = measureFormsFieldsPerfPhase(function () use ($formsService): array {
        return $formsService->getAllFormsWithLayouts();
    });
    $layoutsByIds = measureFormsFieldsPerfPhase(function () use ($fieldsService, $layoutIds): array {
        return $fieldsService->getLayoutsByIds($layoutIds);
    });
    $fieldsByForms = measureFormsFieldsPerfPhase(function () use ($fieldsService, $formIds): array {
        return $fieldsService->getAllFieldsForForms($formIds);
    });

    $representativeConfig = getRepresentativeFormFieldConfig();
    $createFieldRepeated = measureFormsFieldsPerfPhase(function () use ($fieldsService, $representativeConfig): array {
        $created = [];

        for ($i = 0; $i < 200; $i++) {
            $config = $representativeConfig;
            $config['handle'] = $representativeConfig['handle'] . $i;
            $created[] = $fieldsService->createField($config);
        }

        return $created;
    });

    emitFormsFieldsPerfBreakdown($profileLabel, $formCount, $fieldCount, [
        'formsPlainMs' => $formsPlain['elapsedMs'],
        'formsWithLayoutsMs' => $formsWithLayouts['elapsedMs'],
        'layoutsByIdsMs' => $layoutsByIds['elapsedMs'],
        'fieldsByFormsMs' => $fieldsByForms['elapsedMs'],
        'createFieldRepeatedMs' => $createFieldRepeated['elapsedMs'],
        'counts' => [
            'forms' => count($formsPlain['result']),
            'formsWithLayouts' => count($formsWithLayouts['result']),
            'layouts' => count($layoutsByIds['result']),
            'fieldGroups' => count($fieldsByForms['result']),
            'createdFields' => count($createFieldRepeated['result']),
        ],
    ]);

    expect(count($formsPlain['result']))->toBeGreaterThanOrEqual($formCount)
        ->and(count($formsWithLayouts['result']))->toBeGreaterThanOrEqual($formCount)
        ->and(count($layoutsByIds['result']))->toBeGreaterThanOrEqual($formCount)
        ->and(count($fieldsByForms['result']))->toBeGreaterThanOrEqual($formCount)
        ->and(count($createFieldRepeated['result']))->toBe(200);
})->with([
    'small' => ['small', 5, 10],
    'medium' => ['medium', 15, 15],
    'large' => ['large', 30, 15],
])->group('perf');

function seedFormsFieldsPerfForms(int $formCount, int $fieldCount): void
{
    for ($formIndex = 1; $formIndex <= $formCount; $formIndex++) {
        $builder = formie()->form([
            'title' => "Forms Fields Perf {$formIndex}",
        ]);

        for ($fieldIndex = 1; $fieldIndex <= $fieldCount; $fieldIndex++) {
            $builder->singleLineTextField("field{$formIndex}_{$fieldIndex}");
        }

        $builder->create();
    }
}

function measureFormsFieldsPerfPhase(callable $callback): array
{
    resetFormsFieldsPerfCaches();

    $started = microtime(true);
    $result = $callback();

    return [
        'result' => $result,
        'elapsedMs' => (int)((microtime(true) - $started) * 1000),
    ];
}

function resetFormsFieldsPerfCaches(): void
{
    $formsService = Formie::$plugin->getForms();

    $formsService->invalidateFormCaches();

    $fieldsService = Formie::$plugin->getFields();
    $resetFieldCaches = new ReflectionMethod($fieldsService, '_resetFieldCaches');
    $resetFieldCaches->setAccessible(true);
    $resetFieldCaches->invoke($fieldsService);
}

function getRepresentativeFormFieldConfig(): array
{
    $usageQuery = (new Query())
        ->select([
            'fieldId',
            'count' => 'COUNT(*)',
        ])
        ->from(Table::FORMIE_FORM_FIELDS)
        ->groupBy(['fieldId']);

    $fieldConfig = (new Query())
        ->select([
            'ff.id',
            'ff.fieldId',
            'ff.layoutId',
            'ff.pageId',
            'ff.rowId',
            'ff.reference',
            'ff.sortOrder',
            'ff.dateCreated',
            'ff.dateUpdated',
            'ff.uid',
            'ff.settings as formFieldSettings',
            'f.label',
            'f.handle',
            'f.type',
            'f.settings',
            'COALESCE(usage.count, 1) as usageCount',
        ])
        ->from(['ff' => Table::FORMIE_FORM_FIELDS])
        ->innerJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]')
        ->leftJoin(['usage' => $usageQuery], '[[usage.fieldId]] = [[ff.fieldId]]')
        ->orderBy(['ff.id' => SORT_ASC])
        ->one();

    expect($fieldConfig)->toBeArray();

    $fieldsService = Formie::$plugin->getFields();
    $normalize = new ReflectionMethod($fieldsService, '_normalizeFormFieldConfig');
    $normalize->setAccessible(true);

    return $normalize->invoke($fieldsService, $fieldConfig);
}

function emitFormsFieldsPerfBreakdown(string $profileLabel, int $formCount, int $fieldCount, array $metrics): void
{
    fwrite(STDOUT, sprintf(
        "FORMS_FIELDS_SERVICE_PERF %s\n",
        json_encode([
            'profile' => $profileLabel,
            'forms' => $formCount,
            'fieldsPerForm' => $fieldCount,
            'formsPlainMs' => $metrics['formsPlainMs'],
            'formsWithLayoutsMs' => $metrics['formsWithLayoutsMs'],
            'layoutsByIdsMs' => $metrics['layoutsByIdsMs'],
            'fieldsByFormsMs' => $metrics['fieldsByFormsMs'],
            'createFieldRepeatedMs' => $metrics['createFieldRepeatedMs'],
            'counts' => $metrics['counts'],
        ], JSON_UNESCAPED_SLASHES)
    ));
}
