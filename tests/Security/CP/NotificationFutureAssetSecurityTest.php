<?php

declare(strict_types=1);

use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\elements\User;
use Tests\Support\PermissionTestHelper;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\models\Notification;
use verbb\formie\services\Permissions;

// Element IDs are sequential, so an attachment ID that doesn't resolve yet could later be taken by a private
// upload. A control panel save keeps only attachments that exist and the editor can view.

function futureAssetEditor(): User
{
    $name = 'futureAsset' . bin2hex(random_bytes(5));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, PermissionTestHelper::withParents([
        'accessCp', 'accessPlugin-formie', Permissions::PERM_MANAGE_FORMS,
    ])))->toBeTrue();

    return User::find()->id($user->id)->status(null)->one();
}

it('drops attachment IDs that do not resolve when a control panel user saves', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()->form(['title' => 'Future asset ' . uniqid()])->create();
    $editor = futureAssetEditor();
    $futureId = (int)(new Query())->from(CraftTable::ELEMENTS)->max('[[id]]') + 50;

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($editor, $form, $futureId): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity($editor);

        $notification = new Notification([
            'formId' => $form->id,
            'name' => 'Future asset',
            'subject' => 'Future asset',
            'to' => 'recipient@example.test',
            'attachAssets' => [['id' => $futureId]],
        ]);

        $notification->validate();

        expect(array_column($notification->attachAssets ?? [], 'id'))->not->toContain($futureId);
    }, ['method' => 'POST']);

    expect($volume)->not->toBeNull();
})->group('security');

it('keeps attachments that exist when a control panel user saves', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $asset = UploadTestHelper::seedAsset('future-asset-control-' . bin2hex(random_bytes(4)) . '.txt', 'visible', $volume);
    $form = formie()->form(['title' => 'Future asset control ' . uniqid()])->create();
    $admin = User::find()->admin()->status(null)->one();

    WebRequestTestHelper::withWebRequestContext(function ($request) use ($admin, $asset, $form): void {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity($admin);

        $notification = new Notification([
            'formId' => $form->id,
            'name' => 'Existing asset',
            'subject' => 'Existing asset',
            'to' => 'recipient@example.test',
            'attachAssets' => [['id' => $asset->id]],
        ]);

        $notification->validate();

        expect(array_column($notification->attachAssets ?? [], 'id'))->toBe([$asset->id]);
    }, ['method' => 'POST']);
})->group('security');
