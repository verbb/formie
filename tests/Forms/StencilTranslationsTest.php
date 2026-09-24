<?php

declare(strict_types=1);

use verbb\formie\elements\Form;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Stencils as StencilsService;

beforeEach(function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        test()->markTestSkipped('Requires the multisite runtime; covered by the default suite.');
    }
});

function stencilTranslationSites(): array
{
    $primarySite = Craft::$app->getSites()->getPrimarySite();
    $secondarySite = null;

    foreach (Craft::$app->getSites()->getAllSites() as $site) {
        if ((int)$site->id !== (int)$primarySite->id) {
            $secondarySite = $site;
            break;
        }
    }

    expect($secondarySite)->not->toBeNull();

    return [$primarySite, $secondarySite];
}

it('stores stencil translations by site uid and exposes reference-keyed builder overrides', function (): void {
    [, $secondarySite] = stencilTranslationSites();
    $fieldReference = '99ae40f3-8f56-4207-9560-4d34db9ec3c6';
    $stencil = new Stencil([
        'name' => 'Translated Stencil',
        'handle' => 'translatedStencil' . uniqid(),
        'scope' => StencilsService::SCOPE_SITE,
        'data' => [
            'pages' => [
                [
                    'label' => 'Page 1',
                    'rows' => [
                        [
                            'fields' => [
                                [
                                    'type' => SingleLineText::class,
                                    'reference' => $fieldReference,
                                    'settings' => [
                                        'label' => 'Name',
                                        'handle' => 'name',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    Formie::$plugin->getStencils()->saveTranslationBundle($stencil, (int)$secondarySite->id, [
        'pages' => [
            $stencil->data->pages[0]->uid => [
                'label' => 'Page française',
            ],
        ],
        'fieldOverrides' => [
            $fieldReference => [
                'label' => 'Nom',
            ],
        ],
    ]);

    $serialized = $stencil->data->getSerializedData();
    $multiSite = Formie::$plugin->getStencils()->getBuilderMultiSiteConfig($stencil, (int)$secondarySite->id);
    $translatedData = Formie::$plugin->getStencils()->applyTranslationsToBuilderData(
        $stencil,
        [
            'pages' => $stencil->data->getFieldLayout()->getFormBuilderConfig(),
        ],
        (int)$secondarySite->id,
    );

    expect($serialized['translations'])->toHaveKey($secondarySite->uid)
        ->and($multiSite['fieldOverrides'][(int)$secondarySite->id][$fieldReference]['label'] ?? null)->toBe('Nom')
        ->and($multiSite['overrides'][(int)$secondarySite->id]['pages'][$stencil->data->pages[0]->uid]['label'] ?? null)->toBe('Page française')
        ->and($translatedData['pages'][0]['rows'][0]['fields'][0]['label'] ?? null)->toBe('Nom');
});

it('copies portable stencil bundles into a newly materialized forms normal overrides', function (): void {
    [, $secondarySite] = stencilTranslationSites();
    $fieldReference = '727e265a-818c-46a9-8d1a-dd6e28f56c62';
    $stencil = new Stencil([
        'name' => 'Materialized Translation Stencil',
        'handle' => 'materializedTranslationStencil' . uniqid(),
        'scope' => StencilsService::SCOPE_SITE,
        'data' => [
            'pages' => [
                [
                    'label' => 'Page 1',
                    'rows' => [
                        [
                            'fields' => [
                                [
                                    'type' => SingleLineText::class,
                                    'reference' => $fieldReference,
                                    'settings' => [
                                        'label' => 'Name',
                                        'handle' => 'name',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);
    $stencilPageUid = $stencil->data->pages[0]->uid;
    $stencil->data->translations = [
        $secondarySite->uid => [
            'pages' => [
                $stencilPageUid => [
                    'label' => 'Page française',
                ],
            ],
            'fieldOverrides' => [
                $fieldReference => [
                    'label' => 'Nom',
                ],
            ],
        ],
    ];
    $form = new Form([
        'title' => 'Materialized Translation Form',
        'handle' => 'materializedTranslationForm' . uniqid(),
    ]);

    $stencil->applyStencilToForm($form, true);

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    $reloaded = Form::find()->id($form->id)->siteId($form->sourceSiteId)->status(null)->one();
    $field = $reloaded?->getFieldByHandle('name');
    $page = $reloaded?->getPages()[0] ?? null;
    $formOverride = Formie::$plugin->getFormSiteOverrides()->getOverrides(
        (int)$form->id,
        (int)$secondarySite->id,
    );
    $fieldOverride = Formie::$plugin->getFieldSiteOverrides()->getOverride(
        (int)$field?->fieldId,
        (int)$secondarySite->id,
    );

    expect($field)->not->toBeNull()
        ->and($field?->reference)->not->toBe($fieldReference)
        ->and($page?->uid)->not->toBe($stencilPageUid)
        ->and($formOverride['pages'][$page?->uid]['label'] ?? null)->toBe('Page française')
        ->and($fieldOverride['label'] ?? null)->toBe('Nom');
});

it('captures existing form translations when saving a form as a stencil', function (): void {
    [, $secondarySite] = stencilTranslationSites();
    $siteOverrides = Formie::$plugin->getFormSiteOverrides();
    $form = formie()
        ->form(['title' => 'Source Form'])
        ->singleLineTextField('name', ['label' => 'Name'])
        ->create();
    $form = Formie::$plugin->getForms()->getFormById(
        (int)$form->id,
        $siteOverrides->getSourceSiteId($form),
    );
    $field = $form->getFieldByHandle('name');
    $page = $form->getPages()[0] ?? null;

    expect($field)->not->toBeNull()
        ->and($page)->not->toBeNull();

    $siteOverrides->saveTranslationBundle((int)$form->id, (int)$secondarySite->id, [
        'title' => 'Translated Form Title',
        'pages' => [
            $page->uid => [
                'label' => 'Page française',
            ],
        ],
        'fieldOverrides' => [
            (string)$field->fieldId => [
                'label' => 'Nom',
            ],
        ],
    ]);

    $stencil = new Stencil([
        'name' => 'Captured Translation Stencil',
        'handle' => 'capturedTranslationStencil' . uniqid(),
        'scope' => StencilsService::SCOPE_SITE,
    ]);
    $stencil->data->populateFormData($form);
    Formie::$plugin->getStencils()->populateTranslationBundlesFromForm($stencil, $form);

    $bundle = $stencil->data->translations[$secondarySite->uid] ?? [];

    expect($bundle)->not->toHaveKey('title')
        ->and($bundle['pages'][$page->uid]['label'] ?? null)->toBe('Page française')
        ->and($bundle['fieldOverrides'][$field->reference]['label'] ?? null)->toBe('Nom');
});
