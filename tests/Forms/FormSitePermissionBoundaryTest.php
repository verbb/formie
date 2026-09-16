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
