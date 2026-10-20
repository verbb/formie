<?php

use craft\elements\User;
use verbb\formie\Formie;

it('keeps navigation parents separate from action capabilities', function(array $grants, array $allowed): void {
    $name = 'navigationActions' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $grants))->toBeTrue();
    $fresh = User::find()->id($user->id)->status(null)->one();
    $access = Formie::$plugin->getPermissions();
    $capabilities = [
        'import' => $access->canImportForms($fresh),
        'export' => $access->canExportForms($fresh),
        'schedules' => $access->canManageScheduledReports($fresh),
        'testSchedules' => $access->canTestScheduledReports($fresh),
        'stencils' => $fresh->can('formie-accessStencils'),
        'integrations' => $access->canAccessIntegrations($fresh),
        'providers' => $access->canManageCaptchas($fresh),
        'rules' => $access->canManageSpamRules($fresh),
    ];
    foreach ($capabilities as $action => $permitted) {
        expect($permitted)->toBe(in_array($action, $allowed, true));
    }
})->with([
    'section parents alone' => [[
        'formie-accessSettingsSection', 'formie-accessStencilsSection',
        'formie-accessIntegrationsSection', 'formie-accessSpamProtection',
    ], []],
    'import tools alone' => [['formie-accessSettingsSection', 'formie-settingsImportExport'], []],
    'import only' => [['formie-accessSettingsSection', 'formie-settingsImportExport', 'formie-importForms'], ['import']],
    'export only' => [['formie-accessSettingsSection', 'formie-settingsImportExport', 'formie-exportForms'], ['export']],
    'schedule viewer' => [['formie-accessSettingsSection', 'formie-settingsScheduledReports'], []],
    'schedule tester' => [['formie-accessSettingsSection', 'formie-settingsScheduledReports', 'formie-testScheduledReports'], ['testSchedules']],
    'stencil manager' => [['formie-accessStencilsSection', 'formie-accessStencils'], ['stencils']],
    'integration manager' => [['formie-accessIntegrationsSection', 'formie-accessIntegrations'], ['integrations']],
    'provider manager' => [['formie-accessSpamProtection', 'formie-manageCaptchas'], ['providers']],
    'rules manager' => [['formie-accessSpamProtection', 'formie-manageSpamRules'], ['rules']],
]);
