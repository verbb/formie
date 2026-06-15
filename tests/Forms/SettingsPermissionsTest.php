<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\services\Permissions;

it('builds stable settings page permission keys', function (): void {
    $permissions = Formie::$plugin->getPermissions();

    expect($permissions->settingsPagePermissionKey('general'))->toBe('formie-settingsGeneral')
        ->and($permissions->settingsPagePermissionKey('import-export'))->toBe('formie-settingsImportExport')
        ->and($permissions->settingsPagePermissionKey('migrate/freeform4'))->toBe('formie-settingsMigrateFreeform4');
});

it('resolves settings pages from redirect urls', function (): void {
    $permissions = Formie::$plugin->getPermissions();

    expect($permissions->resolveSettingsPageFromUrl('/admin/formie/settings/spam-protection'))
        ->toBe('spam-protection')
        ->and($permissions->resolveSettingsPageFromUrl('/admin/formie/settings/spam'))
        ->toBe('spam')
        ->and($permissions->normalizeSettingsPage('spam'))->toBe('spam-protection')
        ->and($permissions->normalizeSettingsPage('captchas'))->toBe('spam-protection')
        ->and($permissions->resolveSettingsPageFromUrl('/admin/formie/settings/migrate/freeform5'))
        ->toBe('migrate/freeform5')
        ->and($permissions->resolveSettingsPageFromUrl('/admin/formie/settings/fields'))
        ->toBe('fields');
});

it('registers a settings permission definition for every settings page', function (): void {
    $permissions = Formie::$plugin->getPermissions();
    $pages = array_keys($permissions->getSettingsPageDefinitions());
    $definitions = $permissions->getSettingsPermissionDefinitions();

    expect(count($definitions))->toBe(count($pages));

    foreach ($pages as $page) {
        expect($definitions)->toHaveKey($permissions->settingsPagePermissionKey($page));
    }
});
