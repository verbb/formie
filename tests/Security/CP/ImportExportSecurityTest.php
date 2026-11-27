<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use craft\services\UserPermissions;
use Tests\Support\PermissionTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\ImportExportController;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\FormGroup;
use verbb\formie\services\FormImportFiles;
use verbb\formie\services\Permissions;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SecurityImportExportControllerProbe extends ImportExportController
{
    public ?string $capturedSummary = null;
    public ?array $capturedPlan = null;

    public function renderTemplate($template, $variables = [], $templateMode = null): Response
    {
        $this->capturedSummary = (string)($variables['summary'] ?? '');
        $this->capturedPlan = $variables['plan'] ?? null;

        return new Response();
    }
}

it('rejects import temp filenames outside the generated import file pattern', function(string $filename): void {
    $controller = new ImportExportController('formie-import-export-security', Craft::$app);

    expect(fn() => $controller->actionImportConfigure($filename))
        ->toThrow(BadRequestHttpException::class);
})->with([
    'parent traversal' => ['../.env'],
    'nested traversal' => ['formie-import-0199a81f-c823-7000-89e8-1c4a874a2fbb.json/../../.env'],
    'absolute path' => ['/tmp/formie-import-0199a81f-c823-7000-89e8-1c4a874a2fbb.json'],
    'wrong prefix' => ['craft-import-0199a81f-c823-7000-89e8-1c4a874a2fbb.json'],
    'wrong extension' => ['formie-import-0199a81f-c823-7000-89e8-1c4a874a2fbb.php'],
])->group('security');

it('reports when a form import file is no longer available', function(): void {
    $user = \craft\elements\User::find()->admin(true)->one();

    WebRequestTestHelper::withWebRequestContext(function() use ($user): void {
        Craft::$app->getRequest()->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity($user);

        $controller = new ImportExportController('formie-import-export-security', Craft::$app);

        expect(fn() => $controller->actionImportConfigure('formie-import-0199a81f-c823-7000-89e8-1c4a874a2fbb.json'))
            ->toThrow(HttpException::class, 'This form import has expired or is no longer available. Upload the JSON file again.');
    }, [
        'method' => 'GET',
        'requestUri' => '/admin/formie/settings/import-export/import-configure',
    ]);
})->group('security');

it('rejects files that are not valid Formie form exports', function(string $contents): void {
    $importFiles = new FormImportFiles(Craft::$app->getAssets()->getTempAssetUploadFs());
    $user = \craft\elements\User::find()->admin(true)->one();
    $userId = (int)$user->id;
    $stream = fopen('php://temp', 'w+b');
    fwrite($stream, $contents);
    rewind($stream);
    $filename = $importFiles->store($stream, $userId);
    fclose($stream);

    try {
        WebRequestTestHelper::withWebRequestContext(function() use ($filename, $user): void {
            Craft::$app->getRequest()->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity($user);

            $controller = new ImportExportController('formie-import-export-security', Craft::$app);
            $response = $controller->actionImportConfigure($filename);

            expect($response)->toBeInstanceOf(Response::class)
                ->and($response->getStatusCode())->toBe(302)
                ->and($response->getHeaders()->get('Location'))->toContain('/formie/settings/import-export');
        }, [
            'method' => 'GET',
            'requestUri' => '/admin/formie/settings/import-export/import-configure',
        ]);
    } finally {
        $importFiles->delete($filename, $userId);
    }
})->with([
    'unrelated JSON object' => [json_encode(['name' => 'Not a form'], JSON_THROW_ON_ERROR)],
    'malformed JSON' => ['{"handle":'],
])->group('security');

it('encodes hostile import preview strings before rendering the summary with raw', function(): void {
    $controller = new SecurityImportExportControllerProbe('formie-import-export-security', Craft::$app);
    $importFiles = new FormImportFiles(Craft::$app->getAssets()->getTempAssetUploadFs());
    $user = \craft\elements\User::find()->admin(true)->one();
    $userId = (int)$user->id;

    $payload = [
        'title' => '<img src=x onerror=alert(1)> [unsafe](javascript:alert(4))',
        'handle' => '"><script>alert(1)</script>',
        'pages' => [[
            'rows' => [[
                'fields' => [[
                    'type' => '<img src=x onerror=alert(2)> [unsafe](javascript:alert(2)) ![image](data:text/html,unsafe)',
                    'label' => 'Unsafe type',
                    'handle' => 'unsafeType',
                ]],
            ]],
        ]],
        'notifications' => [
            ['name' => '<svg onload=alert(1)>'],
        ],
    ];

    $stream = fopen('php://temp', 'w+b');
    fwrite($stream, json_encode($payload, JSON_THROW_ON_ERROR));
    rewind($stream);
    $filename = $importFiles->store($stream, $userId);
    fclose($stream);

    try {
        WebRequestTestHelper::withWebRequestContext(function() use ($controller, $filename, $user): void {
            Craft::$app->getRequest()->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity($user);

            $controller->actionImportConfigure($filename);

            $summary = (string)$controller->capturedSummary;

            expect($summary)
                ->toContain('&lt;img src=x onerror=alert(1)&gt;')
                ->toContain('&lt;img src=x onerror=alert(2)&gt;')
                ->toContain('&lt;svg onload=alert(1)&gt;')
                ->and($summary)->not->toContain('<img src=x onerror=alert(1)>')
                ->and($summary)->not->toContain('<img src=x onerror=alert(2)>')
                ->and($summary)->not->toContain('<svg onload=alert(1)>')
                ->and($summary)->not->toContain('href="javascript:')
                ->and($summary)->not->toContain('src="data:text')
                ->and($summary)->not->toContain('<a ')
                ->and($summary)->not->toContain('<img ');
        }, [
            'method' => 'GET',
            'requestUri' => '/admin/formie/settings/import-export/import-configure',
        ]);
    } finally {
        $importFiles->delete($filename, $userId);
    }
})->group('security');

it('requires a visible form for the import completed page', function(): void {
    $controller = new ImportExportController('formie-import-export-security', Craft::$app);

    WebRequestTestHelper::withWebRequestContext(function() use ($controller): void {
        Craft::$app->getRequest()->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(null);
        $form = formie()->form(['title' => 'Restricted completed import'])->create();

        expect(fn() => $controller->actionImportCompleted((int)$form->id))
            ->toThrow(ForbiddenHttpException::class)
            ->and(fn() => $controller->actionImportCompleted(2147483647))
            ->toThrow(NotFoundHttpException::class);
    }, [
        'method' => 'GET',
        'requestUri' => '/admin/formie/settings/import-export/import-completed/1',
    ]);
})->group('security');

it('encodes form titles in the import completed message', function(): void {
    $html = WebRequestTestHelper::withWebRequestContext(function($request): string {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(\craft\elements\User::find()->admin(true)->one());
        $form = new \verbb\formie\elements\Form([
            'id' => 123,
            'title' => '<img src=x onerror=alert(3)>',
        ]);
        $view = Craft::$app->getView();
        $view->setTemplateMode(\craft\web\View::TEMPLATE_MODE_CP);
        $view->registerTwigExtension(new \verbb\base\web\twig\Extension());

        return $view->renderTemplate('formie/settings/import-export/import-completed', compact('form'));
    }, [
        'method' => 'GET',
        'requestUri' => '/admin/formie/settings/import-export/import-completed/123',
    ]);

    expect($html)
        ->toContain('&lt;img src=x onerror=alert(3)&gt;')
        ->and($html)->not->toContain('<img src=x onerror=alert(3)>');
})->group('security');

it('requires manage access before an import can update an existing form', function(): void {
    $target = formie()->form(['title' => 'Restricted Import Target'])->create();
    $payload = ImportExportHelper::generateFormExport($target);
    $payload['title'] = 'Forged Import Title';

    $username = 'formImportAcl' . bin2hex(random_bytes(6));
    $user = new \craft\elements\User(['username' => $username, 'email' => $username . '@example.test']);

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());

    $permissions = [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_IMPORT_FORMS,
        Formie::$plugin->getPermissions()->settingsPagePermissionKey('import-export'),
    ];

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, \Tests\Support\PermissionTestHelper::withParents($permissions)))->toBeTrue();

    $importFiles = new FormImportFiles(Craft::$app->getAssets()->getTempAssetUploadFs());
    $stream = fopen('php://temp', 'w+b');
    fwrite($stream, json_encode($payload, JSON_THROW_ON_ERROR));
    rewind($stream);
    $filename = $importFiles->store($stream, (int)$user->id);
    fclose($stream);

    try {
        WebRequestTestHelper::withWebRequestContext(function($request) use ($filename, $target, $user): void {
            $request->setIsCpRequest(true);
            $identity = \craft\elements\User::find()->id($user->id)->status(null)->one();
            Craft::$app->getUser()->setIdentity($identity);
            // A prior privileged lookup must not supply the target from cache.
            Formie::$plugin->getForms()->invalidateFormCaches();
            $request->setBodyParams([
                $request->csrfParam => $request->getCsrfToken(),
                'filename' => $filename,
                'formAction' => 'update',
            ]);

            expect(Formie::$plugin->getPermissions()->canManageForm($identity, $target))->toBeFalse()
                ->and(fn() => (new ImportExportController('formie-import-export-security', Formie::$plugin))->actionImportComplete())
                ->toThrow(ForbiddenHttpException::class, 'User is not permitted to update this form');
        }, [
            'method' => 'POST',
            'requestUri' => '/admin/formie/settings/import-export/import-complete',
            'headers' => ['Accept' => 'application/json'],
        ]);
    } finally {
        $importFiles->delete($filename, (int)$user->id);
    }

    expect(Formie::$plugin->getForms()->getFormById((int)$target->id)?->title)->toBe('Restricted Import Target');
})->group('security');

it('requires a visible form before exporting it', function(bool $isCpRequest): void {
    $group = new \verbb\formie\models\FormGroup([
        'name' => 'Visible Export Group',
        'handle' => 'visibleExportGroup' . bin2hex(random_bytes(6)),
    ]);

    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();

    $allowed = formie()->form(['title' => 'Visible Export Form', 'groupId' => $group->id])->create();
    $restricted = formie()->form(['title' => 'Restricted Export Form'])->create();
    $permissions = Formie::$plugin->getPermissions();

    $username = 'formExportAcl' . bin2hex(random_bytes(6));
    $user = new \craft\elements\User(['username' => $username, 'email' => $username . '@example.test']);

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    // Reload the permission list so the new group's scoped grant isn't dropped as unknown.
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());

    $grants = [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
        $permissions->scopedPermission(Permissions::PERM_VIEW_FORMS, $permissions->groupScope($group->handle)),
        Permissions::PERM_EXPORT_FORMS,
    ];

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, \Tests\Support\PermissionTestHelper::withParents($grants)))->toBeTrue();

    $identity = \craft\elements\User::find()->id($user->id)->status(null)->one();

    expect($permissions->canViewForm($identity, $allowed))->toBeTrue()
        ->and($permissions->canExportForms($identity))->toBeTrue();

    WebRequestTestHelper::withWebRequestContext(function($request, $response) use ($isCpRequest, $allowed, $restricted, $permissions, $user): void {
        $request->setIsCpRequest($isCpRequest);
        $identity = \craft\elements\User::find()->id($user->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);

        // A sent export ends the application. Stop at its after-request event instead of exiting the test run.
        Craft::$app->state = \yii\base\Application::STATE_HANDLING_REQUEST;
        Craft::$app->on(\yii\base\Application::EVENT_AFTER_REQUEST, static function(): void {
            throw new RuntimeException('Export sent');
        });

        $export = function(int $formId) use ($request): void {
            $request->setQueryParams(['formId' => $formId]);
            $level = ob_get_level();
            ob_start(); // Craft closes a download buffer; preserve the test runner's buffer.

            try {
                (new ImportExportController('import-export', Formie::$plugin))->actionExport();
            } finally {
                while (ob_get_level() > $level) {
                    ob_end_clean();
                }
            }
        };

        expect($permissions->canViewForm($identity, $restricted))->toBeFalse()
            ->and(fn() => $export((int)$restricted->id))->toThrow(ForbiddenHttpException::class)
            ->and(fn() => $export(2147483647))->toThrow(NotFoundHttpException::class)
            ->and(fn() => $export((int)$allowed->id))->toThrow(RuntimeException::class, 'Export sent')
            ->and(json_decode((string)$response->content, true)['handle'] ?? null)->toBe($allowed->handle);
    }, [
        'method' => 'GET',
        'requestUri' => ($isCpRequest ? '/admin' : '') . '/actions/formie/import-export/export',
    ]);
})->with([
    'control panel request' => [true],
    'site request' => [false],
])->group('security');

/**
 * Creates form groups A and B, a form in A, and an importer holding the given grants.
 * A per-form grant is only registered, and so only saved, when the form uses dedicated permissions.
 */
function importGroupMoveFixture(callable $grants, bool $admin = false, bool $dedicated = false): array
{
    $groups = [];

    foreach (['a', 'b'] as $key) {
        $groups[$key] = new FormGroup([
            'name' => 'Import Move ' . strtoupper($key) . ' ' . bin2hex(random_bytes(3)),
            'handle' => 'importMove' . strtoupper($key) . bin2hex(random_bytes(6)),
        ]);

        expect(Formie::$plugin->getFormGroups()->saveGroup($groups[$key]))->toBeTrue();
    }

    $form = formie()->form(['title' => 'Import Move Form', 'groupId' => $groups['a']->id])
        ->settings(['usePerFormPermissions' => $dedicated])
        ->singleLineTextField('fullName')
        ->create();
    $name = 'importGroupMove' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    $user->admin = $admin;

    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new UserPermissions());

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, PermissionTestHelper::withParents([
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
        Permissions::PERM_IMPORT_FORMS,
        Formie::$plugin->getPermissions()->settingsPagePermissionKey('import-export'),
        ...$grants($groups, $form),
    ])))->toBeTrue();

    return ['groups' => $groups, 'form' => $form, 'user' => $user];
}

function importGroupMoveGrant(string $base, FormGroup $group): string
{
    $permissions = Formie::$plugin->getPermissions();

    return $permissions->scopedPermission($base, $permissions->groupScope($group->handle));
}

/**
 * Exports the form with its group dependency replaced, as an edited export file would carry it.
 */
function importGroupMovePayload(Form $form, array $group): array
{
    $payload = ImportExportHelper::generateFormExport($form);
    $payload['dependencies'] = array_values(array_filter($payload['dependencies'], static fn(array $dependency) => $dependency['kind'] !== 'group'));
    $payload['dependencies'][] = ['kind' => 'group', 'config' => $group];

    return $payload;
}

function importGroupMoveConfig(FormGroup $group): array
{
    return ['uid' => $group->uid, 'name' => $group->name, 'handle' => $group->handle];
}

/**
 * Runs the user-aware import as the fixture user, updating the fixture form unless `$update` is false.
 */
function importGroupMoveRun(array $fixture, array $payload, bool $update = true): array
{
    // Share the console project config, so a group the import creates is visible after the request.
    $projectConfig = Craft::$app->getProjectConfig();
    $mutex = Craft::$app->getMutex();

    return WebRequestTestHelper::withWebRequestContext(function($request) use ($fixture, $payload, $update, $projectConfig, $mutex): array {
        Craft::$app->set('projectConfig', $projectConfig);
        Craft::$app->set('mutex', $mutex);
        $request->setIsCpRequest(true);
        $identity = User::find()->id($fixture['user']->id)->status(null)->one();
        Craft::$app->getUser()->setIdentity($identity);
        Formie::$plugin->getForms()->invalidateFormCaches();
        $existing = $update ? Form::find()->withoutCpIndexScope()->id($fixture['form']->id)->status(null)->one() : null;
        $plan = ImportExportHelper::planImport($payload, $existing, $identity);

        try {
            return ['plan' => $plan, 'form' => ImportExportHelper::importForUser($payload, $existing, $identity)->form, 'error' => null];
        } catch (ForbiddenHttpException $e) {
            return ['plan' => $plan, 'form' => null, 'error' => $e->getMessage()];
        }
    }, [
        'method' => 'POST',
        'requestUri' => '/admin/formie/settings/import-export/import-complete',
        'headers' => ['Accept' => 'application/json'],
    ]);
}

function importGroupMoveStoredGroupId(int $formId): ?int
{
    $groupId = (new Query())->select('groupId')->from(Table::FORMIE_FORMS)->where(['id' => $formId])->scalar();

    return $groupId === null ? null : (int)$groupId;
}

it('refuses an update import that moves a form into a group the importer cannot manage', function(bool $missingGroup): void {
    $fixture = importGroupMoveFixture(fn(array $groups) => [
        importGroupMoveGrant(Permissions::PERM_MANAGE_FORMS, $groups['a']),
        Permissions::PERM_ACCESS_SUBMISSIONS,
        importGroupMoveGrant(Permissions::PERM_VIEW_SUBMISSIONS, $groups['b']),
    ]);
    ['a' => $groupA, 'b' => $groupB] = $fixture['groups'];
    $missingHandle = 'importMoveMissing' . bin2hex(random_bytes(6));
    $destination = $missingGroup
        ? ['name' => 'Import Move Missing', 'handle' => $missingHandle]
        : importGroupMoveConfig($groupB);
    $payload = importGroupMovePayload($fixture['form'], $destination);
    $payload['title'] = 'Import Move Forged Title';

    $result = importGroupMoveRun($fixture, $payload);

    expect($result['plan']['deniedGroup'])->toBe($destination['name'])
        ->and($result['error'])->toBe('You don’t have permission to move this form to the “' . $destination['name'] . '” form group.')
        ->and(importGroupMoveStoredGroupId((int)$fixture['form']->id))->toBe((int)$groupA->id)
        ->and(Formie::$plugin->getForms()->getFormById((int)$fixture['form']->id)?->title)->toBe('Import Move Form')
        ->and(Formie::$plugin->getFormGroups()->getGroupByHandle($missingHandle))->toBeNull();

    // The same import can still create a new form in that group.
    $created = importGroupMoveRun($fixture, $payload, false);

    expect($created['error'])->toBeNull()
        ->and($created['plan']['deniedGroup'])->toBeNull()
        ->and($created['form']->id)->not->toBe($fixture['form']->id);

    $createdGroup = $missingGroup ? Formie::$plugin->getFormGroups()->getGroupByHandle($missingHandle) : $groupB;

    expect($createdGroup)->not->toBeNull()
        ->and(importGroupMoveStoredGroupId((int)$created['form']->id))->toBe((int)$createdGroup->id);
})->with(['existing group' => false, 'missing group' => true])->group('security');

it('refuses the group move on the review and import steps', function(): void {
    $fixture = importGroupMoveFixture(fn(array $groups) => [importGroupMoveGrant(Permissions::PERM_MANAGE_FORMS, $groups['a'])]);
    ['a' => $groupA, 'b' => $groupB] = $fixture['groups'];
    $payload = importGroupMovePayload($fixture['form'], importGroupMoveConfig($groupB));
    $importFiles = new FormImportFiles(Craft::$app->getAssets()->getTempAssetUploadFs());
    $userId = (int)$fixture['user']->id;
    $stream = fopen('php://temp', 'w+b');
    fwrite($stream, json_encode($payload, JSON_THROW_ON_ERROR));
    rewind($stream);
    $filename = $importFiles->store($stream, $userId);
    fclose($stream);

    try {
        WebRequestTestHelper::withWebRequestContext(function($request) use ($filename, $userId, $groupB): void {
            $request->setIsCpRequest(true);
            Craft::$app->getUser()->setIdentity(User::find()->id($userId)->status(null)->one());
            Formie::$plugin->getForms()->invalidateFormCaches();
            $probe = new SecurityImportExportControllerProbe('formie-import-export-security', Formie::$plugin);
            $probe->actionImportConfigure($filename);

            expect($probe->capturedPlan['deniedGroup'] ?? null)->toBe($groupB->name);

            $request->setBodyParams([
                $request->csrfParam => $request->getCsrfToken(),
                'filename' => $filename,
                'formAction' => 'update',
            ]);

            expect(fn() => (new ImportExportController('formie-import-export-security', Formie::$plugin))->actionImportComplete())
                ->toThrow(ForbiddenHttpException::class, $groupB->name);
        }, [
            'method' => 'POST',
            'requestUri' => '/admin/formie/settings/import-export/import-complete',
            'headers' => ['Accept' => 'application/json'],
        ]);
    } finally {
        $importFiles->delete($filename, $userId);
    }

    expect(importGroupMoveStoredGroupId((int)$fixture['form']->id))->toBe((int)$groupA->id);
})->group('security');

it('keeps update imports that stay in the group or move into a group the importer can manage', function(string $role): void {
    $fixture = importGroupMoveFixture(fn(array $groups, Form $form) => match ($role) {
        'same group' => [importGroupMoveGrant(Permissions::PERM_MANAGE_FORMS, $groups['a'])],
        'managed destination' => [
            importGroupMoveGrant(Permissions::PERM_MANAGE_FORMS, $groups['a']),
            importGroupMoveGrant(Permissions::PERM_MANAGE_FORMS, $groups['b']),
        ],
        'dedicated form grant in a managed destination' => [
            Formie::$plugin->getPermissions()->scopedPermission(Permissions::PERM_MANAGE_FORMS, Formie::$plugin->getPermissions()->formScope($form)),
            importGroupMoveGrant(Permissions::PERM_MANAGE_FORMS, $groups['b']),
        ],
        'manage all forms' => [Permissions::PERM_MANAGE_FORMS],
        'admin' => [],
    }, $role === 'admin', str_starts_with($role, 'dedicated form grant'));
    ['a' => $groupA, 'b' => $groupB] = $fixture['groups'];
    $destination = $role === 'same group' ? $groupA : $groupB;
    $payload = importGroupMovePayload($fixture['form'], importGroupMoveConfig($destination));
    $payload['title'] = 'Import Move Updated ' . bin2hex(random_bytes(3));

    $result = importGroupMoveRun($fixture, $payload);

    expect($result['error'])->toBeNull()
        ->and($result['plan']['deniedGroup'])->toBeNull()
        ->and($result['form']->id)->toBe($fixture['form']->id)
        ->and(importGroupMoveStoredGroupId((int)$fixture['form']->id))->toBe((int)$destination->id)
        ->and(Formie::$plugin->getForms()->getFormById((int)$fixture['form']->id)?->title)->toBe($payload['title']);
})->with(['same group', 'managed destination', 'dedicated form grant in a managed destination', 'manage all forms', 'admin'])->group('security');

it('refuses an update import that moves a form with its own manage grant into a group the importer cannot manage', function(): void {
    $fixture = importGroupMoveFixture(fn(array $groups, Form $form) => [
        Formie::$plugin->getPermissions()->scopedPermission(Permissions::PERM_MANAGE_FORMS, Formie::$plugin->getPermissions()->formScope($form)),
        Permissions::PERM_ACCESS_SUBMISSIONS,
        importGroupMoveGrant(Permissions::PERM_VIEW_SUBMISSIONS, $groups['b']),
    ], dedicated: true);
    ['a' => $groupA, 'b' => $groupB] = $fixture['groups'];
    $payload = importGroupMovePayload($fixture['form'], importGroupMoveConfig($groupB));

    $result = importGroupMoveRun($fixture, $payload);

    expect($result['plan']['deniedGroup'])->toBe($groupB->name)
        ->and($result['error'])->toBe('You don’t have permission to move this form to the “' . $groupB->name . '” form group.')
        ->and(importGroupMoveStoredGroupId((int)$fixture['form']->id))->toBe((int)$groupA->id);

    // Updating the form in its own group still works.
    $payload = importGroupMovePayload($fixture['form'], importGroupMoveConfig($groupA));
    $payload['title'] = 'Import Move Dedicated Update';
    $result = importGroupMoveRun($fixture, $payload);

    expect($result['error'])->toBeNull()
        ->and(Formie::$plugin->getForms()->getFormById((int)$fixture['form']->id)?->title)->toBe('Import Move Dedicated Update');
})->group('security');
