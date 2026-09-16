<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\services\Permissions;

function formSiteBoundaryEditor(int $siteId): User
{
    $name = 'siteEditor' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_FORMS,
        Permissions::PERM_MANAGE_FORMS, 'editSite:' . Craft::$app->getSites()->getSiteById($siteId)->uid,
    ]))->toBeTrue();
    return $user;
}

it('preserves configured form availability when a one-site editor saves the shared form', function (bool $grouped): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Multi-site contract.');
    }
    $sites = Craft::$app->getSites()->getAllSiteIds();
    $primary = (int)Craft::$app->getSites()->getPrimarySite()->id;
    $group = new FormGroup(['name' => 'Shared site policy', 'handle' => 'sharedSite' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => $sites, 'propagation' => 'allEnabled']]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $form = formie()->form(['groupId' => $grouped ? $group->id : null])->singleLineTextField('message')->create();
    $editor = formSiteBoundaryEditor($primary);
    $enabledIds = fn() => array_map('intval', (new Query())->select('siteId')->from('{{%elements_sites}}')
        ->where(['elementId' => $form->id, 'enabled' => true])->orderBy('siteId')->column());
    sort($sites);
    expect($enabledIds())->toBe($sites);

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $editor, $primary): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($editor->id)->status(null)->one());
        Craft::$app->set('sites', new \craft\services\Sites());
        $editable = Form::find()->id($form->id)->siteId($primary)->one();
        expect($editable)->toBeInstanceOf(Form::class);
        expect($editable->canSave(Craft::$app->getUser()->getIdentity()))->toBeTrue();
        $builderIds = array_map(fn($site) => (int)$site->id, Formie::$plugin->getFormSiteOverrides()->getBuilderSitesForForm($editable));
        expect($builderIds)->toBe([$primary]);
        $editable->title = 'Edited on the permitted site';
        expect(Craft::$app->getElements()->saveElement($editable))->toBeTrue();
    }, ['method' => 'POST', 'bodyParams' => ['siteId' => $primary]]);

    expect($enabledIds())->toBe($sites);
    foreach ($sites as $siteId) {
        expect(Form::find()->id($form->id)->siteId($siteId)->one()?->title)->toBe('Edited on the permitted site');
    }
})->with(['grouped' => true, 'ungrouped' => false]);

it('does not substitute an editor site for a disjoint explicit group policy', function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Multi-site contract.');
    }
    $sites = Craft::$app->getSites()->getAllSiteIds();
    $editorSite = (int)$sites[0];
    $groupSite = (int)$sites[1];
    $group = new FormGroup(['name' => 'Restricted site policy', 'handle' => 'restrictedSite' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => [$groupSite], 'propagation' => 'allEnabled']]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $form = formie()->form(['groupId' => $group->id])->singleLineTextField('message')->create();
    $editor = formSiteBoundaryEditor($editorSite);
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($group, $form, $editor, $editorSite, $groupSite): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($editor->id)->status(null)->one());
        Craft::$app->set('sites', new \craft\services\Sites());
        $propagation = Formie::$plugin->getFormSitePropagation();
        expect($propagation->resolveSiteIdsForGroup($group))->toBe([]);
        expect($propagation->isGroupAvailableForSite($group, $editorSite))->toBeFalse();
        expect($propagation->resolveSiteIdsForForm($form))->toBe([$groupSite]);
        expect(Formie::$plugin->getFormSiteOverrides()->getBuilderSitesForForm($form))->toBe([]);
    });
});

it('resolves editable form sites for the supplied user independently of the current session', function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Multi-site contract.');
    }
    $primary = (int)Craft::$app->getSites()->getPrimarySite()->id;
    $editor = formSiteBoundaryEditor($primary);
    WebRequestTestHelper::withWebRequestContext(function () use ($editor, $primary): void {
        $admin = User::find()->admin(true)->one();
        Craft::$app->getUser()->setIdentity($admin);
        Craft::$app->set('sites', new \craft\services\Sites());
        // Prime the host's current-session cache before asking for another user.
        $allSites = Craft::$app->getSites()->getEditableSiteIds();
        expect(count($allSites))->toBeGreaterThan(1);
        $propagation = Formie::$plugin->getFormSitePropagation();
        expect($propagation->getEditableSiteIds($editor))->toBe([$primary]);
        expect(array_column($propagation->getSiteOptionsForEditor($editor), 'value'))->toBe([$primary]);
        expect(array_map(fn($site) => (int)$site->id, Formie::$plugin->getFormSiteOverrides()->getEditableSites($editor)))->toBe([$primary]);
        Craft::$app->getUser()->setIdentity($editor);
        expect($propagation->getEditableSiteIds())->toBe([$primary]);
        expect($propagation->getEditableSiteIds($admin))->toBe($allSites);
    });
});

it('does not move a created-site-only form when its source is excluded by group policy', function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Multi-site contract.');
    }
    $sites = Craft::$app->getSites()->getAllSiteIds();
    $sourceId = (int)$sites[0];
    $otherId = (int)$sites[1];
    $group = new FormGroup(['name' => 'Created site policy', 'handle' => 'createdSite' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => [$sourceId, $otherId], 'propagation' => 'createdSiteOnly']]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $form = formie()->form(['groupId' => $group->id, 'siteId' => $sourceId, 'sourceSiteId' => $sourceId])
        ->singleLineTextField('message')->create();
    $enabledIds = fn() => array_map('intval', (new Query())->select('siteId')->from('{{%elements_sites}}')
        ->where(['elementId' => $form->id, 'enabled' => true])->orderBy('siteId')->column());
    expect($enabledIds())->toBe([$sourceId]);

    $settings = $group->getSettingsModel();
    $settings->sitePolicy['enabledSiteIds'] = [$otherId];
    $group->setSettingsModel($settings);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    expect($enabledIds())->toBe([]);
    $reloaded = Form::find()->id($form->id)->siteId($sourceId)->status(null)->one();
    expect((int)$reloaded->sourceSiteId)->toBe($sourceId);
    expect(Formie::$plugin->getFormSitePropagation()->validateFormSiteAvailability($reloaded))->not->toBeNull();

    $settings->sitePolicy['enabledSiteIds'] = [$sourceId, $otherId];
    $group->setSettingsModel($settings);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    expect($enabledIds())->toBe([$sourceId]);
    expect(Form::find()->id($form->id)->siteId($sourceId)->one()?->getFieldByHandle('message'))->not->toBeNull();
});

it('loads regional forms through explicit site queries and the public form service', function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Multi-site contract.');
    }
    $sites = Craft::$app->getSites()->getAllSiteIds();
    $regionalId = (int)$sites[1];
    $group = new FormGroup(['name' => 'Regional lookup', 'handle' => 'regionalLookup' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => [$regionalId]]]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $form = formie()->form(['groupId' => $group->id, 'siteId' => $regionalId, 'sourceSiteId' => $regionalId])
        ->singleLineTextField('message')->create();
    expect(Form::find()->id($form->id)->siteId($regionalId)->one()?->siteId)->toBe($regionalId);
    expect(Form::find()->id($form->id)->siteId($sites[0])->one())->toBeNull();
    expect(Form::find()->id($form->id)->siteId(max($sites) + 1000)->one())->toBeNull();
    expect((int)Form::find()->id($form->id)->site('*')->unique()->count())->toBe(1);
    $loaded = Formie::$plugin->getForms()->getFormByHandle($form->handle, $regionalId);
    expect($loaded?->id)->toBe($form->id);
    expect($loaded?->getFieldByHandle('message'))->not->toBeNull();
    Formie::$plugin->getForms()->invalidateFormCaches();
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($form, $sites): void {
        $controller = new \verbb\formie\controllers\client\FormsController('forms', Formie::$plugin);
        $response = $controller->runAction('load');
        expect($response->data['definition']['handle'])->toBe($form->handle);
        expect($response->data['session']['tokens']['csrf']['value'])->not->toBeEmpty();
        $request->setBodyParams(['handle' => $form->handle, 'siteId' => $sites[0]]);
        expect(fn() => $controller->runAction('load'))->toThrow(\yii\web\NotFoundHttpException::class);
    }, ['method' => 'POST', 'bodyParams' => ['handle' => $form->handle, 'siteId' => $regionalId], 'headers' => ['Accept' => 'application/json']]);
});
