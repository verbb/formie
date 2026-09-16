<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FormGroupsController;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;

it('preserves shared group availability when a limited-site manager edits another setting', function (bool $includesEditorSite): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Multi-site contract.');
    }
    $sites = Craft::$app->getSites()->getAllSiteIds();
    $policySites = $includesEditorSite ? [$sites[0], $sites[1]] : [$sites[1]];
    $group = new FormGroup(['name' => 'Shared policy', 'handle' => 'sharedPolicy' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => $policySites]]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $form = formie()->form(['groupId' => $group->id])->singleLineTextField('message')->create();
    $name = 'groupManager' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    $permission = Formie::$plugin->getPermissions()->settingsPagePermissionKey('form-groups');
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, [
        'accessCp', 'accessPlugin-formie', $permission, 'editSite:' . Craft::$app->getSites()->getSiteById($sites[0])->uid,
    ]))->toBeTrue();
    $projectConfig = Craft::$app->getProjectConfig();
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $group, $projectConfig): void {
        Craft::$app->set('projectConfig', $projectConfig);
        Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
        $request->setIsCpRequest(true);
        expect(Formie::$plugin->getPermissions()->canAccessSettingsPage(Craft::$app->getUser()->getIdentity(), 'form-groups'))->toBeTrue();
        $payload = Formie::$plugin->getFormGroupDefaults()->getEditorValues($group);
        $payload['name'] = 'Renamed shared policy';
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(), 'id' => $group->id,
            'name' => $group->name, 'handle' => $group->handle, 'settings' => json_encode($payload),
        ]);
        $response = (new FormGroupsController('form-groups', Formie::$plugin))->runAction('save');
        expect($response?->statusCode)->toBe(302);
    }, ['method' => 'POST']);

    $reloaded = Formie::$plugin->getFormGroups()->getGroupById($group->id);
    expect($reloaded->name)->toBe('Renamed shared policy');
    expect($reloaded->getSettingsModel()->getSitePolicyModel()->enabledSiteIds)->toBe($policySites);
    $enabledIds = array_map('intval', (new Query())->select('siteId')->from('{{%elements_sites}}')
        ->where(['elementId' => $form->id, 'enabled' => true])->orderBy('siteId')->column());
    expect($enabledIds)->toBe($policySites);
})->with([true, false]);

it('validates selected group sites while retaining an explicit unrestricted choice', function (): void {
    $service = Formie::$plugin->getFormGroupDefaults();
    $group = new FormGroup(['name' => 'Policy validation', 'handle' => 'policyValidation']);
    $payload = $service->getEditorValues($group);
    $payload['sitePolicyEnabledSiteIds'] = [max(Craft::$app->getSites()->getAllSiteIds()) + 1000];
    expect($service->applyPayload($group, $payload))->toBeFalse();
    expect($group->hasErrors())->toBeTrue();
    $group->clearErrors();
    $payload['sitePolicyEnabledSiteIds'] = '*';
    expect($service->applyPayload($group, $payload))->toBeTrue();
    expect($group->getSettingsModel()->getSitePolicyModel()->enabledSiteIds)->toBeNull();
});
