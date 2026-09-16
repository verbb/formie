<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\models\FormGroup;

it('keeps regional lookup caches separate from an unavailable current site', function (string $method, string $attribute, bool $cold): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    $siteId = Craft::$app->getSites()->getAllSiteIds()[1];
    $group = new FormGroup(['name' => 'Cache region', 'handle' => 'cacheRegion' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => [$siteId]]]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $form = formie()->form(['groupId' => $group->id, 'siteId' => $siteId, 'sourceSiteId' => $siteId])->singleLineTextField('message')->create();
    $forms = Formie::$plugin->getForms();
    $forms->invalidateFormCaches();
    if ($cold) { expect($forms->$method($form->$attribute))->toBeNull(); }
    expect($forms->$method($form->$attribute, $siteId)?->id)->toBe($form->id);
    expect($forms->$method($form->$attribute))->toBeNull();
    expect($forms->$method($form->$attribute, $siteId)?->id)->toBe($form->id);
})->with([
    'id' => ['getFormById', 'id'], 'handle' => ['getFormByHandle', 'handle'],
    'uid' => ['getFormByUid', 'uid'], 'layout' => ['getFormByLayoutId', 'layoutId'],
])->with(['cold first' => true, 'regional first' => false]);

it('refreshes cached form collections when the current site changes', function (): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    $sites = Craft::$app->getSites();
    $original = $sites->getCurrentSite();
    $regionalId = $sites->getAllSiteIds()[1];
    $group = new FormGroup(['name' => 'Collection region', 'handle' => 'collectionRegion' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => [$regionalId]]]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $form = formie()->form(['groupId' => $group->id, 'siteId' => $regionalId, 'sourceSiteId' => $regionalId])->singleLineTextField('message')->create();
    $forms = Formie::$plugin->getForms();
    $forms->invalidateFormCaches();
    expect(array_column($forms->getAllForms(), 'id'))->not->toContain($form->id);
    try {
        $sites->setCurrentSite($regionalId);
        expect(array_column($forms->getAllForms(), 'id'))->toContain($form->id);
        expect(array_column($forms->getAllFormsWithLayouts(), 'id'))->toContain($form->id);
        expect($forms->getFormById($form->id)?->id)->toBe($form->id);
    } finally { $sites->setCurrentSite($original); }
    expect(array_column($forms->getAllForms(), 'id'))->not->toContain($form->id);
    expect($forms->getFormById($form->id))->toBeNull();
});
