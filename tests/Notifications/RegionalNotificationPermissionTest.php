<?php

declare(strict_types=1);

use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\elements\SentNotification;
use verbb\formie\services\Permissions;

it('checks regional sent notification access against its actual form', function (bool $allowed): void {
    if (!Craft::$app->getIsMultiSite()) { $this->markTestSkipped('Multi-site contract.'); }
    $siteId = Craft::$app->getSites()->getAllSiteIds()[1];
    $group = new FormGroup(['name' => 'Notification region', 'handle' => 'noticeRegion' . bin2hex(random_bytes(5)),
        'settings' => ['sitePolicy' => ['enabledSiteIds' => [$siteId]]]]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $form = formie()->form(['groupId' => $group->id, 'siteId' => $siteId, 'sourceSiteId' => $siteId])->singleLineTextField('message')->create();
    $notice = new SentNotification(['title' => 'Regional notice', 'formId' => (string)$form->id, 'success' => true]);
    expect(Craft::$app->getElements()->saveElement($notice))->toBeTrue();
    $name = 'noticeViewer' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    $permissions = Formie::$plugin->getPermissions();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    $grants = ['accessCp', 'accessPlugin-formie', 'formie-accessSentNotifications'];
    if ($allowed) { $grants[] = $permissions->scopedPermission(Permissions::PERM_VIEW_SENT_NOTIFICATIONS, $permissions->groupScope($group->handle)); }
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $grants))->toBeTrue();
    expect(User::find()->id($user->id)->status(null)->one()->can($permissions->scopedPermission(Permissions::PERM_VIEW_SENT_NOTIFICATIONS, $permissions->groupScope($group->handle))))->toBe($allowed);
    WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $notice, $form, $allowed): void {
        $request->setIsCpRequest(true);
        $viewer = User::find()->id($user->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($viewer);
        $loaded = SentNotification::find()->id($notice->id)->one();
        expect($loaded->canView($viewer))->toBe($allowed);
        expect($loaded->getForm()?->id)->toBe($form->id);
    });
})->with(['allowed' => true, 'denied' => false]);
