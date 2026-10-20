<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use craft\helpers\Json;
use craft\models\UserGroup;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\ImportExportController;
use verbb\formie\Formie;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\elements\User as UserIntegration;
use verbb\formie\services\FormImportFiles;
use verbb\formie\services\Integrations;
use verbb\formie\services\Permissions;
use yii\web\ForbiddenHttpException;

function withImportGroupActor(callable $callback, bool $authorized = false, bool $enabled = true): void
{
    $original = Formie::$plugin->getIntegrations();
    $originalUser = Craft::$app->getUser()->getIdentity();
    $registry = new class extends Integrations {
        public UserIntegration $fixture;
        public function getAllIntegrations(): array { return [$this->fixture]; }
        public function getAllCaptchas(): array { return []; }
    };
    $registry->fixture = new UserIntegration(['name' => 'Imported users', 'handle' => 'importedUsers', 'enabled' => $enabled]);
    Formie::$plugin->set('integrations', $registry);

    try {
        $suffix = bin2hex(random_bytes(5));
        $group = new UserGroup(['name' => 'Import restricted ' . $suffix, 'handle' => 'importRestricted' . $suffix]);
        expect(Craft::$app->getUserGroups()->saveGroup($group))->toBeTrue();
        $actor = new User(['username' => 'importActor' . $suffix, 'email' => 'importActor' . $suffix . '@example.test']);
        expect(Craft::$app->getElements()->saveElement($actor))->toBeTrue();
        Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
        expect(Craft::$app->getUserPermissions()->saveUserPermissions($actor->id, \Tests\Support\PermissionTestHelper::withParents([
            'accessCp', 'accessPlugin-formie', Permissions::PERM_IMPORT_FORMS,
            Permissions::PERM_ACCESS_FORMS, Permissions::PERM_MANAGE_FORMS,
            Formie::$plugin->getPermissions()->settingsPagePermissionKey('import-export'),
            ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
            ...($authorized ? ['viewUsers', 'editUsers', 'assignUserGroup:' . $group->uid] : []),
        ])))->toBeTrue();
        $form = formie()->form()->singleLineTextField('email')->integrations([
            'importedUsers' => ['enabled' => true, 'groupUids' => []],
        ])->create();
        WebRequestTestHelper::withWebRequestContext(function($request) use ($actor, $form, $group, $callback): void {
            $request->setIsCpRequest(true);
            $actor = User::find()->id($actor->id)->status(null)->one();
            Craft::$app->getUser()->setIdentity($actor);
            expect($actor->can('formie-showFormIntegrations'))->toBeFalse();
            $callback($request, $form, $group, $actor);
        }, ['method' => 'POST', 'requestUri' => '/admin/formie/settings/import-export/import-complete', 'headers' => ['Accept' => 'application/json']]);
    } finally {
        Formie::$plugin->set('integrations', $original);
        Craft::$app->getUser()->setIdentity($originalUser);
        Formie::$plugin->getForms()->invalidateFormCaches();
    }
}

function importGroupRequest($request, array $payload, string $action, callable $assert): void
{
    $files = new FormImportFiles(Craft::$app->getAssets()->getTempAssetUploadFs());
    $userId = (int)Craft::$app->getUser()->getIdentity()->id;
    $stream = fopen('php://temp', 'w+b');
    fwrite($stream, Json::encode($payload));
    rewind($stream);
    $filename = $files->store($stream, $userId);
    fclose($stream);
    try {
        $request->setBodyParams([
            $request->csrfParam => $request->getCsrfToken(), 'filename' => $filename, 'formAction' => $action,
        ]);
        $assert(fn() => (new ImportExportController('import-export', Formie::$plugin))->runAction('import-complete'), $files, $filename, $userId);
    } finally {
        $files->delete($filename, $userId);
    }
}

function importGroupStored(int $id): array
{
    return Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $id])->scalar())['integrations']['importedUsers'];
}

it('rejects CP import group assignments before persisting forms or portable dependencies', function(string $action, string $encoding, bool $enabled, bool $dependencies): void {
    withImportGroupActor(function($request, $form, $group) use ($action, $encoding, $dependencies): void {
        $payload = ImportExportHelper::generateFormExport($form);
        $payload['settings'] = Json::decodeIfJson($payload['settings']);
        $payload['title'] = 'Denied imported title';
        $payload['settings']['integrations']['importedUsers']['groupUids'] = $encoding === 'json' ? Json::encode([$group->uid]) : [$group->uid];
        $dependencyHandle = 'deniedImport' . bin2hex(random_bytes(5));
        if ($dependencies) {
            $payload['dependencies'][] = ['kind' => 'group', 'config' => ['name' => 'Denied import group', 'handle' => $dependencyHandle]];
        }
        $before = (new Query())->from(Table::FORMIE_FORMS)->all();
        Craft::$app->getProjectConfig()->saveModifiedConfigData();
        $config = Craft::$app->getProjectConfig()->get();
        $writeYaml = Craft::$app->getProjectConfig()->writeYamlAutomatically;
        $relax = \verbb\formie\fields\Recipients::$relaxLegacyOptionValidation;
        importGroupRequest($request, $payload, $action, function($run, $files, $filename, $userId) use ($group): void {
            expect($run)->toThrow(ForbiddenHttpException::class, $group->name);
            expect($files->read($filename, $userId))->not->toBeNull();
        });
        expect((new Query())->from(Table::FORMIE_FORMS)->all())->toBe($before)
            ->and(Formie::$plugin->getFormGroups()->getGroupByHandle($dependencyHandle))->toBeNull()
            ->and(Craft::$app->getProjectConfig()->get())->toBe($config)
            ->and(Craft::$app->getProjectConfig()->writeYamlAutomatically)->toBe($writeYaml)
            ->and(\verbb\formie\fields\Recipients::$relaxLegacyOptionValidation)->toBe($relax);
    }, false, $enabled);
})->with(['create', 'update'])->with(['array', 'json'])->with([true, false])->with(['without dependencies' => false, 'portable dependencies' => true])->group('security');

it('permits authorized CP import groups without integration tab access and removes the temporary file', function(string $action): void {
    withImportGroupActor(function($request, $form, $group) use ($action): void {
        $payload = ImportExportHelper::generateFormExport($form);
        $payload['settings'] = Json::decodeIfJson($payload['settings']);
        $payload['title'] = 'Authorized imported ' . bin2hex(random_bytes(5));
        $payload['settings']['integrations']['importedUsers']['groupUids'] = Json::encode([$group->uid]);
        importGroupRequest($request, $payload, $action, function($run, $files, $filename, $userId): void {
            $run();
            expect($files->read($filename, $userId))->toBeNull();
        });
        $imported = \verbb\formie\elements\Form::find()->title($payload['title'])->status(null)->one();
        expect($imported)->not->toBeNull()
            ->and(importGroupStored((int)$imported->id)['groupUids'])->toBe([$group->uid]);
    }, true);
})->with(['create', 'update'])->group('security');

it('preserves retained and removed import groups but checks re-enabling and copying them', function(string $operation): void {
    withImportGroupActor(function($request, $form, $group, $actor) use ($operation): void {
        $form->settings->integrations['importedUsers'] = ['enabled' => $operation !== 'enable', 'groupUids' => [$group->uid]];
        expect(Craft::$app->getElements()->saveElement($form, false))->toBeTrue();
        $payload = ImportExportHelper::generateFormExport($form);
        $payload['settings'] = Json::decodeIfJson($payload['settings']);
        $payload['settings']['integrations']['importedUsers'] = ['enabled' => true, 'groupUids' => $operation === 'remove' ? [] : Json::encode([$group->uid])];
        $existing = $operation === 'copy' ? null : $form;
        if (in_array($operation, ['enable', 'copy'], true)) {
            expect(fn() => ImportExportHelper::importForUser($payload, $existing, $actor))->toThrow(ForbiddenHttpException::class, $group->name);
        } else {
            $result = ImportExportHelper::importForUser($payload, $existing, $actor);
            expect(importGroupStored((int)$result->form->id)['groupUids'])->toBe($operation === 'remove' ? [] : [$group->uid]);
        }
    });
})->with(['retain', 'remove', 'enable', 'copy'])->group('security');

it('preserves trusted programmatic import APIs without ambient group authorization', function(string $api): void {
    withImportGroupActor(function($request, $form, $group) use ($api): void {
        $payload = ImportExportHelper::generateFormExport($form);
        $payload['settings'] = Json::decodeIfJson($payload['settings']);
        $payload['settings']['integrations']['importedUsers']['groupUids'] = [$group->uid];
        $result = match ($api) {
            'create' => ImportExportHelper::createFromImport($payload)->form,
            'update' => ImportExportHelper::updateFromImport($payload, $form)->form,
            'json' => ImportExportHelper::importFormFromJson($payload),
        };
        expect(importGroupStored((int)$result->id)['groupUids'])->toBe([$group->uid]);
    });
})->with(['create', 'update', 'json'])->group('security');

it('requires import and existing form permissions at the user-aware helper boundary', function(): void {
    withImportGroupActor(function($request, $form, $group, $actor): void {
        $payload = ImportExportHelper::generateFormExport($form);
        $payload['settings'] = Json::decodeIfJson($payload['settings']);
        Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
        Craft::$app->getUserPermissions()->saveUserPermissions($actor->id, \Tests\Support\PermissionTestHelper::withParents([]));
        $actor = User::find()->id($actor->id)->status(null)->one();
        expect(fn() => ImportExportHelper::importForUser($payload, null, $actor))->toThrow(ForbiddenHttpException::class, 'User is not permitted to import forms');
        Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
        Craft::$app->getUserPermissions()->saveUserPermissions($actor->id, \Tests\Support\PermissionTestHelper::withParents(['accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_FORMS, Permissions::PERM_IMPORT_FORMS, Formie::$plugin->getPermissions()->settingsPagePermissionKey('import-export')]));
        $actor = User::find()->id($actor->id)->status(null)->one();
        expect(fn() => ImportExportHelper::importForUser($payload, $form, $actor))->toThrow(ForbiddenHttpException::class, 'User is not permitted to update this form');
    });
})->group('security');
