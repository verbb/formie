<?php

declare(strict_types=1);

use craft\elements\User;
use verbb\formie\Formie;
use verbb\formie\services\Permissions;

it('limits report form options to the supplied viewers submission permissions', function (bool $allowed): void {
    $form = formie()->form()->settings(['usePerFormPermissions' => true])->singleLineTextField('message')->create();
    $name = 'reportOptions' . bin2hex(random_bytes(6));
    $viewer = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($viewer))->toBeTrue();
    $permissions = Formie::$plugin->getPermissions();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    $grants = ['accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_SUBMISSIONS, Permissions::PERM_ACCESS_REPORTS, Permissions::PERM_MANAGE_REPORTS];
    if ($allowed) { $grants[] = $permissions->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $permissions->formScope($form)); }
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($viewer->id, $grants))->toBeTrue();
    $viewer = User::find()->id($viewer->id)->status(null)->one();
    expect($permissions->canManageReports($viewer))->toBeTrue();
    $options = Formie::$plugin->getReportEditor()->getFormOptions($viewer);
    expect(array_map('intval', array_column($options, 'value')))->toBe($allowed ? [(int)$form->id] : []);
})->with(['permitted' => true, 'no submission access' => false]);
