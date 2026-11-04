<?php

declare(strict_types=1);

use craft\elements\Asset;
use craft\elements\User;
use craft\helpers\Assets;
use craft\helpers\StringHelper;
use craft\services\UserPermissions;
use craft\web\TemplateResponseBehavior;
use craft\web\TemplateResponseFormatter;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Tests\Support\PermissionTestHelper;
use Tests\Support\UploadTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\base\web\twig\Extension as BaseTwigExtension;
use verbb\formie\controllers\AddressController;
use verbb\formie\controllers\FileUploadController;
use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Address;
use verbb\formie\Formie;
use verbb\formie\helpers\BrowserRequestProfile;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\addressproviders\Google;
use verbb\formie\models\FormGroup;
use verbb\formie\services\Permissions;
use yii\web\BadRequestHttpException;
use yii\web\Response;

function cpScreenUser(array $grants): User
{
    $name = 'cpScreen' . bin2hex(random_bytes(6));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();

    Craft::$app->set('userPermissions', new UserPermissions());
    $grants = array_merge(['accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_SUBMISSIONS], $grants);

    foreach (Craft::$app->getSites()->getAllSites() as $site) {
        $grants[] = 'editSite:' . $site->uid;
    }

    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, PermissionTestHelper::withParents($grants)))->toBeTrue();

    return User::find()->id($user->id)->status(null)->one();
}

function cpScreenVolumeGrants(\craft\models\Volume $volume): array
{
    return ['viewAssets:' . $volume->uid, 'saveAssets:' . $volume->uid];
}

function cpScreenRequest(?User $user, callable $callback, array $options = []): mixed
{
    return WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $callback) {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity($user ? User::find()->id($user->id)->status(null)->one() : null);
        Craft::$app->getView()->registerTwigExtension(new BaseTwigExtension());

        return $callback($request);
    }, $options + ['method' => 'GET', 'requestUri' => '/admin/formie/submissions']);
}

function cpScreenRender(User $user, Form $form, ?Submission $submission = null): array
{
    return cpScreenRequest($user, function () use ($form, $submission): array {
        $response = (new SubmissionsController('submissions', Formie::$plugin))->actionEditSubmission($form->handle, $submission?->id);
        $modules = $response->getBehavior(TemplateResponseBehavior::NAME)->variables['formConfigJson']['modules'];
        (new TemplateResponseFormatter())->format($response);

        return ['html' => (string)$response->content, 'modules' => $modules['entries'] ?? []];
    });
}

/**
 * The element select on this screen uploads through Craft's own Assets action, which only accepts real multipart
 * requests. Store the asset the way that action does, within the same user's control panel request.
 */
function cpScreenUpload(User $user, \craft\models\Volume $volume, string $filename, string $contents): int
{
    return cpScreenRequest($user, function () use ($volume, $filename, $contents): int {
        $tempPath = Assets::tempFilePath($filename);
        file_put_contents($tempPath, $contents);

        $asset = new Asset();
        $asset->tempFilePath = $tempPath;
        $asset->setFilename($filename);
        $asset->newFolderId = Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id)->id;
        $asset->setVolumeId($volume->id);
        $asset->uploaderId = Craft::$app->getUser()->getId();
        $asset->avoidFilenameConflicts = true;
        $asset->setScenario(Asset::SCENARIO_CREATE);

        expect(Craft::$app->getElements()->saveElement($asset))->toBeTrue(json_encode($asset->getErrors()));

        return (int)$asset->id;
    }, ['method' => 'POST', 'requestUri' => '/admin/actions/assets/upload', 'headers' => ['Accept' => 'application/json']]);
}

function cpScreenSave(User $user, Form $form, array $fields, ?Submission $submission = null): ?Response
{
    return cpScreenRequest($user, fn() => (new SubmissionsController('submissions', Formie::$plugin))->actionSaveAdminSubmission(), [
        'method' => 'POST',
        'requestUri' => '/admin/actions/formie/submissions/save-admin-submission',
        'headers' => ['Accept' => 'application/json'],
        'bodyParams' => [
            'handle' => $form->handle,
            'submissionId' => $submission?->id,
            'expectedVersion' => $submission?->stateVersion ?? 0,
            'siteId' => $form->siteId,
            'fields' => $fields,
        ],
    ]);
}

/**
 * Stages an upload and saves it in one control panel request, since staged uploads belong to the browser session.
 */
function cpScreenSaveStaged(User $user, Form $form, string $handle, string $filename, string $contents, array $fields, ?Submission $submission = null, array $retained = []): ?Response
{
    return cpScreenRequest($user, function ($request) use ($form, $handle, $filename, $contents, $fields, $submission, $retained): ?Response {
        $asset = UploadTestHelper::seedStagedAsset($form, $form->getFieldByHandle($handle)->uid, $handle, $filename, $contents, $submission?->id);
        $request->setBodyParams([
            'handle' => $form->handle,
            'submissionId' => $submission?->id,
            'expectedVersion' => $submission?->stateVersion ?? 0,
            'siteId' => $form->siteId,
            'fields' => $fields + [$handle => [...$retained, (int)$asset->id]],
        ]);

        return (new SubmissionsController('submissions', Formie::$plugin))->actionSaveAdminSubmission();
    }, [
        'method' => 'POST',
        'requestUri' => '/admin/actions/formie/submissions/save-admin-submission',
        'headers' => ['Accept' => 'application/json'],
    ]);
}

function cpScreenStoredFiles(Submission $submission, string $handle): array
{
    $saved = Submission::find()->id($submission->id)->isIncomplete(null)->isSpam(null)->status(null)->one();
    $files = [];

    foreach ($saved->getFieldValue($handle)->status(null)->orderBy(['elements.id' => SORT_ASC])->all() as $asset) {
        $files[$asset->filename] = $asset->getContents();
    }

    return $files;
}

function cpScreenFormRow(Form $form): Submission
{
    return Submission::find()->formId($form->id)->isIncomplete(null)->isSpam(null)->status(null)->one();
}

function cpScreenGeocodeForm(): array
{
    Craft::$app->getDb()->createCommand()->delete(Table::FORMIE_INTEGRATIONS, ['type' => Google::class])->execute();
    $handle = 'cpScreenGoogle' . bin2hex(random_bytes(4));
    Craft::$app->getDb()->createCommand()->insert(Table::FORMIE_INTEGRATIONS, [
        'name' => 'Screen Google',
        'handle' => $handle,
        'type' => Google::class,
        'scope' => 'site',
        'enabled' => '1',
        'sortOrder' => 1,
        'settings' => json_encode(['apiKey' => 'test-only-key', 'geocodingApiKey' => 'test-only-key']),
        'dateCreated' => '2026-01-01 00:00:00',
        'dateUpdated' => '2026-01-01 00:00:00',
        'uid' => StringHelper::UUID(),
    ])->execute();
    // The project config listeners hold the original service, so a replacement would go stale after later saves.
    (new ReflectionMethod(Formie::$plugin->getIntegrations(), '_resetIntegrationCaches'))->invoke(Formie::$plugin->getIntegrations());

    $rows = (new Address())->getSubFields();

    foreach ($rows as &$row) {
        foreach ($row['fields'] as &$child) {
            if (($child['handle'] ?? '') === 'autoComplete') {
                $child['enabled'] = true;
                $child['integrationHandle'] = $handle;
                $child['currentLocation'] = true;
            }
        }
    }

    unset($row, $child);

    return [formie()->form(['title' => 'Screen Geocode'])->addressField('location', ['rows' => $rows])->create(), $handle];
}

/**
 * Dispatches the geocode action against a stubbed provider client, so nothing leaves the process.
 */
function cpScreenGeocode(?User $user, Form $form, bool $cpRequest = true, array $headers = [], ?callable $prepare = null): array
{
    $sent = [];
    $stack = HandlerStack::create(new MockHandler([new PsrResponse(200, [], '{"status":"OK","results":[{"formatted_address":"Stubbed Place"}]}')]));
    $stack->push(Middleware::history($sent));
    Craft::$container->set(Client::class, static fn() => new Client(['handler' => $stack]));

    try {
        $data = WebRequestTestHelper::withWebRequestContext(function ($request) use ($user, $form, $cpRequest, $prepare) {
            $prepare && $prepare($request);
            $request->setIsCpRequest($cpRequest);
            Craft::$app->getUser()->setIdentity($user ? User::find()->id($user->id)->status(null)->one() : null);
            $request->setBodyParams(['latlng' => '-37.8136,144.9631', 'handle' => $form->handle, 'fieldHandle' => 'location']);

            return (new AddressController('address', Formie::$plugin))->runAction('google-places-geocode')->data;
        }, ['method' => 'POST', 'requestUri' => '/admin/actions/formie/address/google-places-geocode', 'headers' => ['Accept' => 'application/json'] + $headers, 'remoteAddr' => '198.51.100.' . random_int(40, 250)]);
    } finally {
        Craft::$container->clear(Client::class);
    }

    return [$data, $sent];
}

it('renders file uploads on the control panel screen as an asset selection without Formie upload endpoints', function (string $displayType): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form(['title' => 'Screen Endpoints'])
        ->multiPage(2)
        ->onPage(1)
            ->fileUploadField('document', ['restrictFiles' => false, 'displayType' => $displayType])
        ->onPage(2)
            ->singleLineTextField('note')
        ->create();
    $page = cpScreenRender(cpScreenUser([Permissions::PERM_VIEW_SUBMISSIONS, Permissions::PERM_CREATE_SUBMISSIONS]), $form);
    $moduleIds = array_column($page['modules'], 'moduleId');

    expect($page['html'])->toContain('id="fields-document" class="elementselect"')
        ->and($page['html'])->not->toContain('formie/file-upload/')
        ->and($page['html'])->not->toContain('type="file"')
        ->and($page['html'])->not->toContain('submissions/set-page')
        ->and($moduleIds)->not->toContain('formie:file-upload')
        ->and($moduleIds)->not->toContain('formie:upload-manager');
})->with(['file input' => 'fileInput', 'upload manager' => 'uploadManager']);

it('lets a creator without form access save a staged upload on a new submission', function (): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form(['title' => 'Creator Upload'])->singleLineTextField('message')->fileUploadField('document', ['restrictFiles' => false])->create();
    $user = cpScreenUser([Permissions::PERM_VIEW_SUBMISSIONS, Permissions::PERM_CREATE_SUBMISSIONS]);
    $filename = 'creator-' . bin2hex(random_bytes(4)) . '.txt';

    $response = cpScreenSaveStaged($user, $form, 'document', $filename, 'creator contents', ['message' => 'With file']);
    expect($response?->data['success'] ?? false)->toBeTrue(json_encode($response?->data));

    $saved = cpScreenFormRow($form);
    expect($saved->getFieldValue('message'))->toBe('With file')
        ->and(array_values(cpScreenStoredFiles($saved, 'document')))->toBe(['creator contents']);
});

it('lets a saver without form access add a staged upload and keep existing files', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()->form(['title' => 'Saver Upload'])->fileUploadField('document', ['restrictFiles' => false])->create();
    $existingName = 'existing-' . bin2hex(random_bytes(4)) . '.txt';
    $existing = UploadTestHelper::seedAsset($existingName, 'existing contents', $volume);
    $submission = formie()->submission($form)->save();
    $submission->setFieldValue('document', [$existing->id]);
    expect(Craft::$app->getElements()->saveElement($submission, false))->toBeTrue();
    $submission = Submission::find()->id($submission->id)->one();
    $user = cpScreenUser([Permissions::PERM_VIEW_SUBMISSIONS, Permissions::PERM_SAVE_SUBMISSIONS]);

    expect(cpScreenRender($user, $form, $submission)['html'])->toContain($existingName);

    $response = cpScreenSave($user, $form, ['document' => [$existing->id]], $submission);
    expect($response?->data['success'] ?? false)->toBeTrue(json_encode($response?->data));
    expect(cpScreenStoredFiles($submission, 'document'))->toBe([$existingName => 'existing contents']);

    $submission = Submission::find()->id($submission->id)->one();
    $response = cpScreenSaveStaged($user, $form, 'document', 'saver-' . bin2hex(random_bytes(4)) . '.txt', 'saver contents', [], $submission, [$existing->id]);
    expect($response?->data['success'] ?? false)->toBeTrue(json_encode($response?->data));
    expect(array_values(cpScreenStoredFiles($submission, 'document')))->toBe(['existing contents', 'saver contents']);
});

it('applies the same upload ownership rule to a selected asset whatever the form access', function (string $role): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $form = formie()->form(['title' => 'Selected Asset'])->fileUploadField('document', ['restrictFiles' => false])->create();
    $user = match ($role) {
        'admin' => User::find()->admin(true)->one(),
        'creator with form access' => cpScreenUser([Permissions::PERM_VIEW_SUBMISSIONS, Permissions::PERM_CREATE_SUBMISSIONS, Permissions::PERM_ACCESS_FORMS, Permissions::PERM_VIEW_FORMS, ...cpScreenVolumeGrants($volume)]),
        default => cpScreenUser([Permissions::PERM_VIEW_SUBMISSIONS, Permissions::PERM_CREATE_SUBMISSIONS, ...cpScreenVolumeGrants($volume)]),
    };
    $assetId = cpScreenUpload($user, $volume, 'selected-' . bin2hex(random_bytes(4)) . '.txt', 'selected');

    $response = cpScreenSave($user, $form, ['document' => [$assetId]]);

    expect($response?->data['success'] ?? null)->toBeFalse()
        ->and($response?->data['errors']['form'] ?? [])->toBe(['Invalid upload ownership.'])
        ->and(Submission::find()->formId($form->id)->isIncomplete(null)->isSpam(null)->status(null)->ids())->toBe([]);
})->with(['admin', 'creator with form access', 'creator without form access']);

it('keeps saving closed for a form outside the creator grant', function (): void {
    $volume = UploadTestHelper::ensureUploadVolume();
    $allowedGroup = new FormGroup(['name' => 'Upload Allowed', 'handle' => 'uploadAllowed' . bin2hex(random_bytes(5))]);
    $deniedGroup = new FormGroup(['name' => 'Upload Denied', 'handle' => 'uploadDenied' . bin2hex(random_bytes(5))]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($allowedGroup))->toBeTrue()
        ->and(Formie::$plugin->getFormGroups()->saveGroup($deniedGroup))->toBeTrue();
    $denied = formie()->form(['groupId' => $deniedGroup->id])->fileUploadField('document', ['restrictFiles' => false])->create();
    $permissions = Formie::$plugin->getPermissions();
    $scope = $permissions->groupScope($allowedGroup->handle);
    $user = cpScreenUser([
        $permissions->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $scope),
        $permissions->scopedPermission(Permissions::PERM_CREATE_SUBMISSIONS, $scope),
        ...cpScreenVolumeGrants($volume),
    ]);
    $assetId = cpScreenUpload($user, $volume, 'denied-form-' . bin2hex(random_bytes(4)) . '.txt', 'denied form');

    expect(fn() => cpScreenSave($user, $denied, ['document' => [$assetId]]))->toThrow(BadRequestHttpException::class);
    expect(Submission::find()->formId($denied->id)->isIncomplete(null)->isSpam(null)->status(null)->ids())->toBe([]);
});

it('keeps the Formie upload endpoints on the form index scope in the control panel', function (string $action): void {
    UploadTestHelper::ensureUploadVolume();
    $form = formie()->form()->fileUploadField('document', ['restrictFiles' => false])->create();
    $user = cpScreenUser([Permissions::PERM_VIEW_SUBMISSIONS, Permissions::PERM_CREATE_SUBMISSIONS]);

    cpScreenRequest($user, function ($request) use ($form, $action): void {
        $request->setBodyParams(['handle' => $form->handle, 'fieldHandle' => 'document', 'assetId' => 1, 'assetIds' => [1]]);
        $controller = new FileUploadController('file-upload', Formie::$plugin);

        expect(fn() => $controller->{$action}())->toThrow(BadRequestHttpException::class);
    }, ['method' => 'POST', 'requestUri' => '/admin/actions/formie/file-upload/upload', 'headers' => ['Accept' => 'application/json']]);
})->with(['upload' => 'actionUpload', 'delete' => 'actionDelete', 'hydrate' => 'actionHydrate']);

it('offers the geocode endpoint to the control panel screen', function (): void {
    [$form] = cpScreenGeocodeForm();
    $page = cpScreenRender(cpScreenUser([Permissions::PERM_VIEW_SUBMISSIONS, Permissions::PERM_CREATE_SUBMISSIONS]), $form);
    $module = current(array_filter($page['modules'], static fn(array $entry): bool => $entry['moduleId'] === 'formie:google-address'));

    expect($module['config']['geocodeEndpoint'] ?? '')->toContain('formie/address/google-places-geocode');
});

it('geocodes for control panel users holding a submission grant without form access', function (string $grant): void {
    [$form] = cpScreenGeocodeForm();
    [$data, $sent] = cpScreenGeocode(cpScreenUser([Permissions::PERM_VIEW_SUBMISSIONS, $grant]), $form);

    expect($data['results'][0]['formatted_address'] ?? null)->toBe('Stubbed Place', json_encode($data))
        ->and($sent)->toHaveCount(1)
        ->and((string)$sent[0]['request']->getUri())->toContain('latlng=-37.8136')
        ->and((string)$sent[0]['request']->getUri())->toContain('key=test-only-key');
})->with(['creator' => Permissions::PERM_CREATE_SUBMISSIONS, 'saver' => Permissions::PERM_SAVE_SUBMISSIONS]);

it('does not geocode for control panel users without a grant for that form', function (): void {
    [$form] = cpScreenGeocodeForm();
    $group = new FormGroup(['name' => 'Geocode Other', 'handle' => 'geocodeOther' . bin2hex(random_bytes(5))]);
    expect(Formie::$plugin->getFormGroups()->saveGroup($group))->toBeTrue();
    $permissions = Formie::$plugin->getPermissions();
    $scope = $permissions->groupScope($group->handle);

    $users = [
        cpScreenUser([Permissions::PERM_VIEW_SUBMISSIONS]),
        cpScreenUser([$permissions->scopedPermission(Permissions::PERM_VIEW_SUBMISSIONS, $scope), $permissions->scopedPermission(Permissions::PERM_CREATE_SUBMISSIONS, $scope)]),
        null,
    ];

    foreach ($users as $user) {
        [$data, $sent] = cpScreenGeocode($user, $form);

        expect($data['error'] ?? null)->toBe('Unable to geocode location.')
            ->and($sent)->toBe([]);
    }
});

it('does not let a cross-origin profile use a control panel grant', function (): void {
    [$form] = cpScreenGeocodeForm();
    $user = cpScreenUser([Permissions::PERM_VIEW_SUBMISSIONS, Permissions::PERM_CREATE_SUBMISSIONS]);
    $settings = Formie::$plugin->getSettings();
    $allowedOrigins = $settings->allowedOrigins;
    $token = bin2hex(random_bytes(16));

    try {
        [$data, $sent] = cpScreenGeocode($user, $form, true, [
            'X-Formie-Profile' => BrowserRequestProfile::CROSS_ORIGIN,
            'X-Formie-Session' => $token,
            'Origin' => 'https://public.example.test',
        ], static function () use ($settings, $token): void {
            $settings->allowedOrigins = ['https://public.example.test'];
            Craft::$app->getCache()->set(['formie-public-session', hash('sha256', $token)], bin2hex(random_bytes(24)), 60);
        });
    } finally {
        $settings->allowedOrigins = $allowedOrigins;
    }

    expect($data['error'] ?? null)->toBe('Unable to geocode location.')
        ->and($sent)->toBe([]);
});

it('leaves site request geocoding independent of the control panel scope', function (): void {
    [$form] = cpScreenGeocodeForm();
    [$data, $sent] = cpScreenGeocode(null, $form, false);

    expect($data['results'][0]['formatted_address'] ?? null)->toBe('Stubbed Place', json_encode($data))
        ->and($sent)->toHaveCount(1);
});
