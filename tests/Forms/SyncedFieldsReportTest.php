<?php

declare(strict_types=1);

use verbb\formie\Formie;

function syncedFieldsReportHandle(string $prefix): string
{
    static $counter = 0;

    return $prefix . (++$counter) . uniqid();
}

it('reports synced field definitions and the forms they appear on', function (): void {
    $sourceForm = formie()
        ->form([
            'title' => 'Sync Report Source',
            'handle' => syncedFieldsReportHandle('syncReportSource'),
        ])
        ->emailField('email', ['label' => 'Shared Email'])
        ->create();

    $sourceField = $sourceForm->getFieldByHandle('email');
    $definitionId = (int)$sourceField->fieldId;

    $syncedFieldConfig = $sourceField->getFormBuilderConfig();
    $syncedFieldConfig['fieldId'] = $definitionId;
    $syncedFieldConfig['syncId'] = $definitionId;
    $syncedFieldConfig['isSynced'] = true;

    $targetForm = formie()
        ->form([
            'title' => 'Sync Report Target',
            'handle' => syncedFieldsReportHandle('syncReportTarget'),
        ])
        ->addFieldConfig($syncedFieldConfig)
        ->create();

    $report = Formie::$plugin->getFields()->getSyncedFieldReport();
    $entry = collect($report)->firstWhere('id', $definitionId);

    expect($entry)->toBeArray()
        ->and($entry['label'])->toBe('Shared Email')
        ->and($entry['handle'])->toBe('email')
        ->and($entry['usageCount'])->toBe(2)
        ->and(collect($entry['forms'])->pluck('id')->all())->toEqualCanonicalizing([
            $sourceForm->id,
            $targetForm->id,
        ]);
});

it('excludes single-use field definitions from the synced field report', function (): void {
    $form = formie()
        ->form([
            'title' => 'Sync Report Single Use',
            'handle' => syncedFieldsReportHandle('syncReportSingle'),
        ])
        ->emailField('singleEmail', ['label' => 'Single Email'])
        ->create();

    $definitionId = (int)$form->getFieldByHandle('singleEmail')->fieldId;
    $report = Formie::$plugin->getFields()->getSyncedFieldReport();

    expect(collect($report)->firstWhere('id', $definitionId))->toBeNull();
});
