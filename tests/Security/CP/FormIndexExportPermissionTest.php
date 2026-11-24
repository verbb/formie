<?php

declare(strict_types=1);

use craft\elements\User;
use craft\services\UserPermissions;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\exporters\FormExport;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\models\FormGroup;
use verbb\formie\services\Permissions;

/**
 * Creates a form group and a real user who can view that group's forms, optionally with Export forms.
 */
function formIndexExportUser(bool $canExport): User
{
    $group = new FormGroup([
        'name' => 'Index Export Group',
        'handle' => 'indexExportGroup' . bin2hex(random_bytes(6)),
    ]);

    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    $username = 'formIndexExport' . bin2hex(random_bytes(6));
    $user = new User(['username' => $username, 'email' => $username . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new UserPermissions());
    $permissions = Formie::$plugin->getPermissions();

    $grants = [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
        $permissions->scopedPermission(Permissions::PERM_VIEW_FORMS, $permissions->groupScope($group->handle)),
    ];

    if ($canExport) {
        // Craft drops nested permissions without their parents, so grant the full Settings path.
        array_push($grants, Permissions::PERM_ACCESS_SETTINGS_SECTION, $permissions->settingsPagePermissionKey('import-export'), Permissions::PERM_EXPORT_FORMS);
    }

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $grants))->toBeTrue();

    return User::find()->id($user->id)->status(null)->one();
}

/**
 * Returns the exporter classes Craft's element index would offer, and accept, for the Forms index.
 */
function formIndexExporterClasses(?User $identity): array
{
    return WebRequestTestHelper::withWebRequestContext(function($request) use ($identity): array {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity($identity);

        // Formie only registers its exporter during a CP request's init, which the console test app never runs.
        // The helper restores the event registry afterwards, so this handler doesn't outlive the request.
        (new ReflectionMethod(Formie::class, '_registerElementExports'))->invoke(Formie::$plugin);

        return array_map(static fn(mixed $exporter): string => is_string($exporter) ? $exporter : ($exporter['type'] ?? get_class($exporter)), Form::exporters('*'));
    });
}

it('does not offer the form exporter to users who can only view forms', function(): void {
    $user = formIndexExportUser(false);

    expect(Formie::$plugin->getPermissions()->canExportForms($user))->toBeFalse()
        ->and(formIndexExporterClasses($user))->not->toContain(FormExport::class);
})->group('security');

it('offers the form exporter to users with the Export forms permission', function(): void {
    $user = formIndexExportUser(true);

    expect(Formie::$plugin->getPermissions()->canExportForms($user))->toBeTrue()
        ->and(formIndexExporterClasses($user))->toContain(FormExport::class);
})->group('security');

it('offers the form exporter to admins', function(): void {
    expect(formIndexExporterClasses(User::find()->admin(true)->one()))->toContain(FormExport::class);
})->group('security');

it('does not offer the form exporter without a signed-in user', function(): void {
    expect(formIndexExporterClasses(null))->not->toContain(FormExport::class);
})->group('security');
