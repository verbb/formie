<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\models\FormSitePolicy;

it('returns empty overrides for the source site', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();
    $form = formie()
        ->form(['title' => 'Source Site Form'])
        ->create();
    $sourceSiteId = $service->getSourceSiteId($form);

    expect($service->getOverrides((int)$form->id, $sourceSiteId))->toBe([]);
});

it('saves explicit translations payload without server-side diffing', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();

    $form = formie()
        ->form(['title' => 'Explicit Translations Form'])
        ->singleLineTextField('testField', ['label' => 'Test Field'])
        ->create();

    $canonicalForm = Formie::$plugin->getForms()->getFormById((int)$form->id, $service->getSourceSiteId($form));
    $fields = $canonicalForm->getFields();
    $field = reset($fields);
    $reference = $field?->reference;

    expect($reference)->not->toBeEmpty();

    $siteIds = Formie::$plugin->getFormSitePropagation()->resolveSiteIdsForForm($canonicalForm);
    $sourceSiteId = $service->getSourceSiteId($canonicalForm);
    $secondarySiteId = null;

    foreach ($siteIds as $siteId) {
        if ((int)$siteId !== $sourceSiteId) {
            $secondarySiteId = (int)$siteId;
            break;
        }
    }

    if ($secondarySiteId === null) {
        expect(true)->toBeTrue();

        return;
    }

    $translations = [
        'title' => 'Explicit Translations Form (Site 2)',
        'fields' => [
            (string)$reference => [
                'label' => 'Test Field Site 2',
            ],
        ],
    ];

    $service->saveOverrides((int)$form->id, $secondarySiteId, $translations);

    $saved = $service->getOverrides((int)$form->id, $secondarySiteId);

    expect($saved['title'] ?? null)->toBe('Explicit Translations Form (Site 2)');
    expect($saved['fields'][$reference]['label'] ?? null)->toBe('Test Field Site 2');
});

it('merges sparse title overrides into builder data', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();

    $canonical = [
        'id' => 10,
        'title' => 'Primary title',
        'settings' => [],
        'pages' => [],
        'notifications' => [],
    ];

    $merged = $service->mergeOverridesIntoBuilderData($canonical, [
        'title' => 'French title',
    ]);

    expect($merged['title'])->toBe('French title');

    if ($service->isEnabled()) {
        $siteIds = Craft::$app->getSites()->getAllSiteIds();
        $secondarySiteId = null;

        foreach ($siteIds as $siteId) {
            if ((int)$siteId !== $service->getSourceSiteIdForFormId((int)$canonical['id'])) {
                $secondarySiteId = (int)$siteId;
                break;
            }
        }

        if ($secondarySiteId !== null) {
            $applied = $service->applyToBuilderData($canonical, $secondarySiteId, [
                'title' => 'French title',
            ]);

            expect($applied['title'])->toBe('French title');
        }
    }
});

it('merges field overrides keyed by reference into builder data', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();

    $canonical = [
        'id' => 10,
        'title' => 'Primary title',
        'settings' => [],
        'pages' => [
            [
                '_handle' => 'pageOne',
                'label' => 'Page One',
                'rows' => [
                    [
                        'fields' => [
                            [
                                'reference' => 'field-ref-1',
                                'uid' => 'legacy-field-uid',
                                'label' => 'Primary label',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'notifications' => [],
    ];

    $merged = $service->mergeOverridesIntoBuilderData($canonical, [
        'fields' => [
            'field-ref-1' => [
                'label' => 'Translated label',
            ],
        ],
    ]);

    expect($merged['pages'][0]['rows'][0]['fields'][0]['label'])->toBe('Translated label');
});

it('merges legacy uid-keyed field overrides into builder data', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();

    $canonical = [
        'pages' => [
            [
                '_handle' => 'pageOne',
                'rows' => [
                    [
                        'fields' => [
                            [
                                'reference' => 'field-ref-1',
                                'uid' => 'legacy-field-uid',
                                'label' => 'Primary label',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $merged = $service->mergeOverridesIntoBuilderData($canonical, [
        'fields' => [
            'legacy-field-uid' => [
                'label' => 'Translated label',
            ],
        ],
    ]);

    expect($merged['pages'][0]['rows'][0]['fields'][0]['label'])->toBe('Translated label');
});

it('does not mutate canonical builder data while merging overrides', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();

    $canonical = [
        'title' => 'Primary title',
        'pages' => [
            [
                'uid' => 'page-uid-1',
                'label' => 'Primary page',
                'rows' => [
                    [
                        'fields' => [
                            [
                                'reference' => 'field-ref-1',
                                'label' => 'Primary label',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $service->mergeOverridesIntoBuilderData($canonical, [
        'title' => 'Translated title',
        'pages' => [
            'page-uid-1' => [
                'label' => 'Translated page',
            ],
        ],
        'fields' => [
            'field-ref-1' => [
                'label' => 'Translated label',
            ],
        ],
    ]);

    expect($canonical['title'])->toBe('Primary title');
    expect($canonical['pages'][0]['label'])->toBe('Primary page');
    expect($canonical['pages'][0]['rows'][0]['fields'][0]['label'])->toBe('Primary label');
});

it('merges page overrides keyed by handle into builder data', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();

    $canonical = [
        'pages' => [
            [
                'id' => 42,
                'uid' => 'page-uid-1',
                '_handle' => 'pageOne',
                'label' => 'Primary page',
                'rows' => [],
            ],
        ],
    ];

    $merged = $service->mergeOverridesIntoBuilderData($canonical, [
        'pages' => [
            'pageOne' => [
                'label' => 'Translated page',
            ],
        ],
    ]);

    expect($merged['pages'][0]['label'])->toBe('Translated page');
});

it('merges page overrides keyed by uid into builder data', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();

    $canonical = [
        'pages' => [
            [
                'id' => 42,
                'uid' => 'page-uid-1',
                '_handle' => 'pageOne',
                'label' => 'Primary page',
                'rows' => [],
            ],
        ],
    ];

    $merged = $service->mergeOverridesIntoBuilderData($canonical, [
        'pages' => [
            'page-uid-1' => [
                'label' => 'Translated page',
            ],
        ],
    ]);

    expect($merged['pages'][0]['label'])->toBe('Translated page');
});

it('merges page overrides keyed by id into builder data', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();

    $canonical = [
        'pages' => [
            [
                'id' => 42,
                'uid' => 'page-uid-1',
                '_handle' => 'pageOne',
                'label' => 'Primary page',
                'rows' => [],
            ],
        ],
    ];

    $merged = $service->mergeOverridesIntoBuilderData($canonical, [
        'pages' => [
            '42' => [
                'label' => 'Translated page',
            ],
        ],
    ]);

    expect($merged['pages'][0]['label'])->toBe('Translated page');
});

it('merges notification overrides keyed by handle into builder data', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();

    $canonical = [
        'notifications' => [
            [
                'handle' => 'adminNotification',
                'uid' => 'legacy-notification-uid',
                'subject' => 'Primary subject',
            ],
        ],
    ];

    $merged = $service->mergeOverridesIntoBuilderData($canonical, [
        'notifications' => [
            'adminNotification' => [
                'subject' => 'Translated subject',
            ],
        ],
    ]);

    expect($merged['notifications'][0]['subject'])->toBe('Translated subject');
});

it('merges option overrides with translated labels and values on secondary sites only', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();

    $canonical = [
        'title' => 'Primary title',
        'pages' => [
            [
                'uid' => 'page-1',
                'label' => 'Page 1',
                'rows' => [
                    [
                        'fields' => [
                            [
                                'uid' => 'radio-field',
                                'type' => 'verbb\\formie\\fields\\Radio',
                                'label' => 'Radio',
                                'handle' => 'radio',
                                'options' => [
                                    ['label' => 'Option 1', 'value' => 'Option 1'],
                                    ['label' => 'Option 2', 'value' => 'Option 2'],
                                    ['label' => 'Option 3', 'value' => 'Option 3'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $merged = $service->mergeOverridesIntoBuilderData($canonical, [
        'fields' => [
            'radio-field' => [
                'options' => [
                    ['value' => 'Option 2', 'label' => 'Option 2 (Site 2)', 'optionValue' => 'Option 2 (Site 2)'],
                ],
            ],
        ],
    ]);

    expect($merged['pages'][0]['rows'][0]['fields'][0]['options'][1]['label'])->toBe('Option 2 (Site 2)');
    expect($merged['pages'][0]['rows'][0]['fields'][0]['options'][1]['value'])->toBe('Option 2 (Site 2)');
    expect($canonical['pages'][0]['rows'][0]['fields'][0]['options'][1]['value'])->toBe('Option 2');
});

it('merges label-derived option overrides using canonical option values', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();

    $canonical = [
        'title' => 'Primary title',
        'pages' => [
            [
                'uid' => 'page-1',
                'label' => 'Page 1',
                'rows' => [
                    [
                        'fields' => [
                            [
                                'uid' => 'radio-field',
                                'type' => 'verbb\\formie\\fields\\Radio',
                                'label' => 'Radio',
                                'handle' => 'radio',
                                'options' => [
                                    ['label' => 'Option 1', 'value' => 'Option 1'],
                                    ['label' => 'Option 2', 'value' => 'Option 2'],
                                    ['label' => 'Option 3', 'value' => 'Option 3'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $merged = $service->mergeOverridesIntoBuilderData($canonical, [
        'fields' => [
            'radio-field' => [
                'options' => [
                    ['label' => 'Option 2 (Site 2)', 'value' => 'Option 2 (Site 2)'],
                ],
            ],
        ],
    ]);

    expect($merged['pages'][0]['rows'][0]['fields'][0]['options'][1]['label'])->toBe('Option 2 (Site 2)');
    expect($merged['pages'][0]['rows'][0]['fields'][0]['options'][1]['value'])->toBe('Option 2 (Site 2)');
});

it('normalizes and prunes empty override payloads', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();

    $normalized = $service->normalizeOverrides([
        'title' => '',
        'settings' => [
            'errorMessage' => '',
        ],
        'fields' => [
            'abc' => [
                'label' => 'Alt label',
            ],
        ],
    ]);

    expect($normalized)->toBe([
        'fields' => [
            'abc' => [
                'label' => 'Alt label',
            ],
        ],
    ]);
});

it('resolves site-specific form titles for cp indexes', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();
    $form = formie()
        ->form(['title' => 'Primary title'])
        ->create();

    expect($service->resolveFormTitlesForSite([
        (int)$form->id => 'Primary title',
    ], $service->getSourceSiteId($form)))->toBe([
        (int)$form->id => 'Primary title',
    ]);
});

it('normalizes top-level field overrides', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();

    expect($service->normalizeOverrides([
        'title' => 'Site title',
        'fields' => [
            'abc' => ['label' => 'Alt label'],
        ],
    ]))->toBe([
        'title' => 'Site title',
        'fields' => [
            'abc' => ['label' => 'Alt label'],
        ],
    ]);
});

it('ignores field overrides nested under pages', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();

    expect($service->normalizeOverrides([
        'pages' => [
            'fields' => [
                'abc' => ['label' => 'Alt label'],
            ],
        ],
    ]))->toBe([]);
});

it('resolves all-enabled propagation for ungrouped forms', function (): void {
    $form = formie()
        ->form(['title' => 'Propagation Form'])
        ->singleLineTextField('name')
        ->create();

    $propagation = Formie::$plugin->getFormSitePropagation();
    $siteIds = $propagation->resolveSiteIdsForForm($form);

    expect($siteIds)->not->toBeEmpty();
});

it('propagates canonical form titles to secondary element site rows', function (): void {
    $propagation = Formie::$plugin->getFormSitePropagation();

    if (!$propagation->isEnabled()) {
        expect(true)->toBeTrue();

        return;
    }

    $form = formie()
        ->form(['title' => 'Canonical Form Title'])
        ->singleLineTextField('name')
        ->create();

    $propagation->syncFormSites($form);

    $siteIds = $propagation->resolveSiteIdsForForm($form);
    $sourceSiteId = Formie::$plugin->getFormSiteOverrides()->getSourceSiteId($form);
    $secondarySiteIds = array_values(array_filter(
        $siteIds,
        fn(int $siteId) => $siteId !== $sourceSiteId,
    ));

    if ($secondarySiteIds === []) {
        expect(true)->toBeTrue();

        return;
    }

    $secondarySiteId = $secondarySiteIds[0];

    $title = (new \craft\db\Query())
        ->select(['title'])
        ->from([\craft\db\Table::ELEMENTS_SITES])
        ->where(['elementId' => $form->id, 'siteId' => $secondarySiteId])
        ->scalar();

    expect($title)->toBe('Canonical Form Title');
});

it('merges nested child field overrides into form elements for front-end rendering', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();

    if (!$service->isEnabled()) {
        expect(true)->toBeTrue();

        return;
    }

    $form = formie()
        ->form(['title' => 'Nested Override Form'])
        ->nameField('fullName', ['useMultipleFields' => true, 'label' => 'Name'])
        ->create();

    Formie::$plugin->getFormSitePropagation()->syncFormSites($form);

    $canonicalForm = Formie::$plugin->getForms()->getFormById((int)$form->id, $service->getSourceSiteId($form));
    $nameField = $canonicalForm->getFieldByHandle('fullName');
    $childField = $nameField?->getFieldByHandle('firstName');

    if (!$childField) {
        expect(true)->toBeTrue();

        return;
    }

    $siteIds = Formie::$plugin->getFormSitePropagation()->resolveSiteIdsForForm($canonicalForm);
    $sourceSiteId = $service->getSourceSiteId($canonicalForm);
    $secondarySiteId = null;

    foreach ($siteIds as $siteId) {
        if ((int)$siteId !== $sourceSiteId) {
            $secondarySiteId = (int)$siteId;
            break;
        }
    }

    if ($secondarySiteId === null) {
        expect(true)->toBeTrue();

        return;
    }

    $service->saveOverrides((int)$form->id, $secondarySiteId, [
        'fields' => [
            (string)$childField->reference => [
                'label' => 'First Name (Site 2)',
            ],
        ],
    ]);

    $applied = $service->applyToForm($canonicalForm, $secondarySiteId, true);
    $appliedChild = $applied->getFieldByHandle('fullName')->getFieldByHandle('firstName');

    expect($appliedChild->label)->toBe('First Name (Site 2)')
        ->and($canonicalForm->getFieldByHandle('fullName')->getFieldByHandle('firstName')->label)->not->toBe('First Name (Site 2)');
});

it('exposes builder translatable config for client merge/extract', function (): void {
    $service = Formie::$plugin->getFormSiteOverrides();
    $config = $service->getBuilderTranslatableConfig();

    expect($config)->toHaveKeys([
        'form',
        'formSettings',
        'page',
        'pageSettings',
        'notification',
        'fieldTypes',
        'scalarKeys',
        'nestedKeys',
    ])
        ->and($config['form'])->toContain('title')
        ->and($config['nestedKeys'])->toContain('options', 'columns')
        ->and($config['fieldTypes']['verbb\\formie\\fields\\SingleLineText'] ?? null)
        ->toContain('label', 'placeholder');
});

it('restricts form group availability to enabled sites', function (): void {
    $propagation = Formie::$plugin->getFormSitePropagation();

    if (!$propagation->isEnabled()) {
        expect(true)->toBeTrue();

        return;
    }

    $allSiteIds = Craft::$app->getSites()->getAllSiteIds();

    if (count($allSiteIds) < 2) {
        expect(true)->toBeTrue();

        return;
    }

    $restrictedSiteId = (int)$allSiteIds[1];
    $otherSiteId = count($allSiteIds) > 2 ? (int)$allSiteIds[2] : (int)$allSiteIds[0];

    $group = new FormGroup([
        'name' => 'Site Restricted Group',
        'handle' => 'siteRestricted' . uniqid(),
        'settings' => [
            'sitePolicy' => [
                'enabledSiteIds' => [$restrictedSiteId],
                'propagation' => FormSitePolicy::PROPAGATION_ALL_ENABLED,
            ],
        ],
    ]);

    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    expect($propagation->resolveSiteIdsForGroup($group))->toBe([$restrictedSiteId])
        ->and($propagation->isGroupAvailableForSite($group, $restrictedSiteId))->toBeTrue()
        ->and($propagation->isGroupAvailableForSite($group, $otherSiteId))->toBeFalse()
        ->and($propagation->isGroupAvailableForSite(null, $otherSiteId))->toBeTrue();
});

it('resolves the creation site for new forms restricted to a single enabled site', function (): void {
    $propagation = Formie::$plugin->getFormSitePropagation();

    if (!$propagation->isEnabled()) {
        expect(true)->toBeTrue();

        return;
    }

    $allSiteIds = Craft::$app->getSites()->getAllSiteIds();

    if (count($allSiteIds) < 2) {
        expect(true)->toBeTrue();

        return;
    }

    $restrictedSiteId = (int)$allSiteIds[1];

    $group = new FormGroup([
        'name' => 'Creation Site Group',
        'handle' => 'creationSiteGroup' . uniqid(),
        'settings' => [
            'sitePolicy' => [
                'enabledSiteIds' => [$restrictedSiteId],
                'propagation' => FormSitePolicy::PROPAGATION_ALL_ENABLED,
            ],
        ],
    ]);

    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    $form = new \verbb\formie\elements\Form();
    $form->groupId = $group->id;

    Craft::$app->getRequest()->setBodyParams([
        'siteId' => $restrictedSiteId,
    ]);

    expect($propagation->resolveCreationSiteIdForForm($form))->toBe($restrictedSiteId)
        ->and($propagation->resolveSiteIdsForForm($form))->toBe([$restrictedSiteId]);
});

it('limits builder site switcher options to form availability', function (): void {
    $siteOverrides = Formie::$plugin->getFormSiteOverrides();

    if (!$siteOverrides->isEnabled()) {
        expect(true)->toBeTrue();

        return;
    }

    $allSiteIds = Craft::$app->getSites()->getAllSiteIds();

    if (count($allSiteIds) < 2) {
        expect(true)->toBeTrue();

        return;
    }

    $restrictedSiteId = (int)$allSiteIds[1];

    $group = new FormGroup([
        'name' => 'Builder Site Group',
        'handle' => 'builderSiteGroup' . uniqid(),
        'settings' => [
            'sitePolicy' => [
                'enabledSiteIds' => [$restrictedSiteId],
                'propagation' => FormSitePolicy::PROPAGATION_ALL_ENABLED,
            ],
        ],
    ]);

    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    $form = formie()
        ->form([
            'title' => 'Builder Site Form',
            'groupId' => $group->id,
        ])
        ->singleLineTextField('name')
        ->create();

    Formie::$plugin->getFormSitePropagation()->syncFormSites($form);

    $builderSites = $siteOverrides->getBuilderSitesForForm($form);
    $builderSiteIds = array_map(fn(\craft\models\Site $site) => (int)$site->id, $builderSites);
    $multiSite = $siteOverrides->getBuilderMultiSiteConfig($form, $restrictedSiteId);

    expect($builderSiteIds)->toBe([$restrictedSiteId])
        ->and($multiSite['enabled'])->toBeFalse()
        ->and($multiSite['sites'])->toHaveCount(1)
        ->and($multiSite['sourceSiteId'])->toBe($restrictedSiteId)
        ->and($siteOverrides->getBuilderSiteCrumbConfig($form, $restrictedSiteId))->toBeNull();
});

it('propagates using the form source site site group instead of craft primary', function (): void {
    $propagation = Formie::$plugin->getFormSitePropagation();

    if (!$propagation->isEnabled()) {
        expect(true)->toBeTrue();

        return;
    }

    $allSiteIds = Craft::$app->getSites()->getAllSiteIds();

    if (count($allSiteIds) < 2) {
        expect(true)->toBeTrue();

        return;
    }

    $sourceSiteId = (int)$allSiteIds[1];
    $otherSiteId = count($allSiteIds) > 2 ? (int)$allSiteIds[2] : (int)$allSiteIds[0];
    $sourceSite = Craft::$app->getSites()->getSiteById($sourceSiteId);
    $matchingSiteIds = array_values(array_filter(
        $allSiteIds,
        function(int $siteId) use ($sourceSite) {
            $site = Craft::$app->getSites()->getSiteById($siteId);

            return $site && $site->groupId === $sourceSite->groupId;
        },
    ));

    if (count($matchingSiteIds) < 2) {
        expect(true)->toBeTrue();

        return;
    }

    $group = new FormGroup([
        'name' => 'Source Site Group Propagation',
        'handle' => 'sourceSiteGroupPropagation' . uniqid(),
        'settings' => [
            'sitePolicy' => [
                'enabledSiteIds' => $matchingSiteIds,
                'propagation' => FormSitePolicy::PROPAGATION_SAME_SITE_GROUP,
            ],
        ],
    ]);

    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    $form = new \verbb\formie\elements\Form();
    $form->groupId = $group->id;
    $form->sourceSiteId = $sourceSiteId;
    $form->siteId = $sourceSiteId;

    expect($propagation->resolveSiteIdsForForm($form))->toBe($matchingSiteIds)
        ->and($propagation->validateFormSiteAvailability($form))->toBeNull();

    $form->sourceSiteId = $otherSiteId;
    $form->siteId = $otherSiteId;

    $resolved = $propagation->resolveSiteIdsForForm($form);
    $otherSite = Craft::$app->getSites()->getSiteById($otherSiteId);

    expect($resolved)->toBe(array_values(array_filter(
        $matchingSiteIds,
        fn(int $siteId) => Craft::$app->getSites()->getSiteById($siteId)?->groupId === $otherSite?->groupId,
    )));
});
