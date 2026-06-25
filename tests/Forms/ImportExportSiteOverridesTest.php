<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\ImportExportHelper;

it('exports and imports form and field site overrides using portable keys', function (): void {
    $siteOverrides = Formie::$plugin->getFormSiteOverrides();

    if (!$siteOverrides->isEnabled()) {
        expect(true)->toBeTrue();

        return;
    }

    $allSites = Craft::$app->getSites()->getAllSites();
    $secondarySite = null;

    foreach ($allSites as $site) {
        if ((int)$site->id !== $siteOverrides->getPrimarySiteId()) {
            $secondarySite = $site;
            break;
        }
    }

    if ($secondarySite === null) {
        expect(true)->toBeTrue();

        return;
    }

    $form = formie()
        ->form(['title' => 'Import Export Site Overrides Form'])
        ->singleLineTextField('testField', ['label' => 'Test Field'])
        ->create();

    $canonicalForm = Formie::$plugin->getForms()->getFormById((int)$form->id, $siteOverrides->getSourceSiteId($form));
    $field = $canonicalForm->getFields()[0] ?? null;

    expect($field)->not->toBeNull()
        ->and($field->reference)->not->toBeEmpty();

    $siteOverrides->saveTranslationBundle((int)$form->id, (int)$secondarySite->id, [
        'title' => 'Import Export Site Overrides Form (Translated)',
        'fieldOverrides' => [
            (string)$field->fieldId => [
                'label' => 'Translated Field Label',
            ],
        ],
    ]);

    $export = ImportExportHelper::generateFormExport($canonicalForm);

    expect($export['exportVersion'])->toBe('v4')
        ->and($export['sourceSiteHandle'])->not->toBeEmpty()
        ->and($export['siteOverrides'][$secondarySite->handle]['title'] ?? null)
            ->toBe('Import Export Site Overrides Form (Translated)')
        ->and($export['fieldSiteOverrides'][$secondarySite->handle][$field->reference]['label'] ?? null)
            ->toBe('Translated Field Label')
        ->and($export)->not->toHaveKey('sourceSiteId');

    ArrayHelper::remove($export, 'id');

    $importedForm = ImportExportHelper::importFormFromJson($export, 'create');

    expect($importedForm->id)->not->toBe((int)$form->id);

    $importedOverrides = $siteOverrides->getOverrides((int)$importedForm->id, (int)$secondarySite->id);
    $importedField = Formie::$plugin->getForms()->getFormById((int)$importedForm->id, $siteOverrides->getSourceSiteId($importedForm))
        ->getFields()[0] ?? null;

    expect($importedOverrides['title'] ?? null)->toBe('Import Export Site Overrides Form (Translated)')
        ->and($importedField)->not->toBeNull();

    $importedFieldOverride = Formie::$plugin->getFieldSiteOverrides()->getOverride(
        (int)$importedField->fieldId,
        (int)$secondarySite->id,
    );

    expect($importedFieldOverride['label'] ?? null)->toBe('Translated Field Label');
});

it('ignores legacy v3 exports without site override payloads', function (): void {
    $form = formie()
        ->form(['title' => 'Legacy Import Form'])
        ->singleLineTextField('name')
        ->create();

    $export = ImportExportHelper::generateFormExport($form);
    $export['exportVersion'] = 'v3';
    unset($export['siteOverrides'], $export['fieldSiteOverrides'], $export['sourceSiteHandle']);

    $importedForm = ImportExportHelper::importFormFromJson($export, 'create');

    expect($importedForm->id)->toBeGreaterThan(0)
        ->and($importedForm->title)->toBe('Legacy Import Form');
});
