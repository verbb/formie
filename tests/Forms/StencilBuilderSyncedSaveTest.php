<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use Tests\Support\PermissionTestHelper;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\StencilsController;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use yii\base\InvalidConfigException;

beforeEach(function(): void {
    Craft::$app->getProjectConfig()->flush();
    $this->stencilSaveTransaction = Craft::$app->getDb()->beginTransaction();
});

afterEach(function(): void {
    $this->stencilSaveTransaction->rollBack();
    Craft::$app->getInfo()->configVersion = (new Query())->select('configVersion')->from(\craft\db\Table::INFO)->scalar();
    Craft::$app->getProjectConfig()->reset();
    (new ReflectionProperty(Formie::$plugin->getStencils(), '_stencils'))->setValue(Formie::$plugin->getStencils(), null);
    Formie::$plugin->getForms()->invalidateFormCaches();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
});

function stencilSaveUser(): User
{
    $name = 'stencilSave' . bin2hex(random_bytes(5));
    $user = new User(['username' => $name, 'email' => $name . '@example.test', 'active' => true]);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, PermissionTestHelper::withParents([
        'accessCp', 'accessPlugin-formie', 'formie-accessStencils',
    ])))->toBeTrue();

    return $user;
}

function stencilSaveFixture(bool $nested = false, bool $synced = true): array
{
    $source = formie()->form()->emailField('email')->create();
    $field = $source->getFieldByHandle('email');
    $config = $field->getFormBuilderConfig();
    foreach (['id', 'uid', 'reference', 'layoutId', 'pageId', 'rowId'] as $key) {
        unset($config[$key]);
    }
    $config['isSynced'] = $synced;
    if (!$synced) {
        unset($config['definitionId'], $config['definitionUid']);
    }
    $factory = formie()->form()->singleLineTextField('message');
    $blueprint = $nested
        ? $factory->groupField('contact', ['rows' => [['fields' => [$config]]]])->create()
        : $factory->addFieldConfig($config)->create();
    $stencil = new Stencil(['name' => 'Shared starter', 'handle' => 'sharedStarter' . bin2hex(random_bytes(5)), 'scope' => 'project']);
    $stencil->data = new StencilData();
    $stencil->data->populateFormData($blueprint);
    expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();

    return [$stencil, $field->definitionId];
}

function stencilSaveRequest(User $user, string $action, array $body = [], array $arguments = []): array
{
    return WebRequestTestHelper::withWebRequestContext(function($request) use ($user, $action, $body, $arguments): array {
        $config = Craft::$app->getProjectConfig();
        $readOnly = $config->readOnly;
        $general = Craft::$app->getConfig()->getGeneral();
        $allowAdminChanges = $general->allowAdminChanges;
        $devMode = $general->devMode;
        try {
            $general->allowAdminChanges = false;
            $general->devMode = false;
            $config->readOnly = true;
            $request->setIsCpRequest(true);
            $request->setBodyParams([$request->csrfParam => $request->getCsrfToken(), ...$body]);
            Craft::$app->getUser()->setIdentity(User::find()->id($user->id)->status(null)->one());
            $response = (new StencilsController('stencils', Formie::$plugin))->runAction($action, $arguments);
            if ($action === 'save') {
                return $response->data;
            }
            $prefix = 'new Craft.Formie.FormBuilder(';
            foreach (Craft::$app->getView()->js as $scripts) {
                foreach ($scripts as $script) {
                    if (str_starts_with($script, $prefix)) {
                        return json_decode(substr($script, strlen($prefix), -2), true, flags: JSON_THROW_ON_ERROR);
                    }
                }
            }
            throw new RuntimeException('Expected the rendered stencil builder bootstrap.');
        } finally {
            $general->allowAdminChanges = $allowAdminChanges;
            $general->devMode = $devMode;
            $config->readOnly = $readOnly;
        }
    }, ['method' => $action === 'edit' ? 'GET' : 'POST', 'headers' => ['Accept' => 'application/json']]);
}

function stencilSaveChangeSynced(array &$data, callable $change): int
{
    $changed = 0;
    if (isset($data['type']) && !empty($data['isSynced'])) {
        $change($data);
        $changed++;
    }
    foreach ($data as &$value) {
        if (is_array($value)) {
            $changed += stencilSaveChangeSynced($value, $change);
        }
    }

    return $changed;
}

it('copies and resaves the rendered production stencil while retaining its synced field', function(bool $nested): void {
    [$stencil, $definitionId] = stencilSaveFixture($nested);
    $actor = stencilSaveUser();
    $before = (new Query())->from('{{%formie_stencils}}')->where(['id' => $stencil->id])->one();
    $builder = stencilSaveRequest($actor, 'edit', arguments: ['segments' => (string)$stencil->id]);
    expect($builder['canEdit'])->toBeFalse()->and($builder['saveDuplicateRequestData'])->toBe(['duplicateToSite' => true]);
    $saved = stencilSaveRequest($actor, 'save', [...$builder['data'], ...$builder['saveRequestData'], ...$builder['saveDuplicateRequestData']]);
    expect($saved['success'] ?? false)->toBeTrue()->and($saved['entityId'])->not->toBe($stencil->id);
    $copy = Formie::$plugin->getStencils()->getStencilById($saved['entityId']);
    expect($copy->scope)->toBe('site')
        ->and(json_encode($copy->data->getSerializedData()))->not->toContain('definitionToken');

    $savedAgain = stencilSaveRequest($actor, 'save', [...$saved['data'], ...$saved['saveRequestData']]);
    expect($savedAgain['success'] ?? false)->toBeTrue();
    $live = new Form(['title' => 'Copied starter', 'handle' => 'copiedStarter' . bin2hex(random_bytes(5))]);
    Formie::$plugin->getStencils()->getStencilById($saved['entityId'])->applyStencilToForm($live, true);
    expect(Craft::$app->getElements()->saveElement($live))->toBeTrue();
    $reloaded = Form::find()->id($live->id)->one();
    $email = $nested ? $reloaded->getFieldByHandle('contact')->getFieldByHandle('email') : $reloaded->getFieldByHandle('email');
    expect($email->definitionId)->toBe($definitionId)
        ->and((new Query())->from('{{%formie_stencils}}')->where(['id' => $stencil->id])->one())->toBe($before);
})->with([false, true]);

it('still copies an ordinary rendered project stencil without a shared definition', function(): void {
    [$stencil] = stencilSaveFixture(synced: false);
    $actor = stencilSaveUser();
    $builder = stencilSaveRequest($actor, 'edit', arguments: ['segments' => (string)$stencil->id]);
    $saved = stencilSaveRequest($actor, 'save', [...$builder['data'], ...$builder['saveRequestData'], ...$builder['saveDuplicateRequestData']]);
    expect($saved['success'] ?? false)->toBeTrue();
});

it('rejects another actor replaying a rendered synced-field selection', function(): void {
    [$stencil] = stencilSaveFixture();
    $first = stencilSaveUser();
    $second = stencilSaveUser();
    $builder = stencilSaveRequest($first, 'edit', arguments: ['segments' => (string)$stencil->id]);
    $count = (new Query())->from('{{%formie_stencils}}')->count();
    expect(fn() => stencilSaveRequest($second, 'save', [...$builder['data'], ...$builder['saveRequestData'], ...$builder['saveDuplicateRequestData']]))
        ->toThrow(InvalidConfigException::class, 'Invalid synced field selection');
    expect((new Query())->from('{{%formie_stencils}}')->count())->toBe($count);
});

it('rejects a tampered synced-field selection without saving a copy', function(): void {
    [$stencil] = stencilSaveFixture();
    $actor = stencilSaveUser();
    $builder = stencilSaveRequest($actor, 'edit', arguments: ['segments' => (string)$stencil->id]);
    expect(stencilSaveChangeSynced($builder['data'], static function(array &$field): void {
        $field['definitionToken'] = ($field['definitionToken'] ?? '') . 'tampered';
    }))->toBe(1);
    $count = (new Query())->from('{{%formie_stencils}}')->count();
    expect(fn() => stencilSaveRequest($actor, 'save', [...$builder['data'], ...$builder['saveRequestData'], ...$builder['saveDuplicateRequestData']]))
        ->toThrow(InvalidConfigException::class, 'Invalid synced field selection');
    expect((new Query())->from('{{%formie_stencils}}')->count())->toBe($count);
});

it('uses the signed definition instead of substituted numeric definition metadata', function(): void {
    [$stencil, $definitionId] = stencilSaveFixture();
    $other = formie()->form()->emailField('otherEmail')->create()->getFieldByHandle('otherEmail');
    $actor = stencilSaveUser();
    $builder = stencilSaveRequest($actor, 'edit', arguments: ['segments' => (string)$stencil->id]);
    expect(stencilSaveChangeSynced($builder['data'], static function(array &$field) use ($other): void {
        $field['definitionId'] = $other->definitionId;
        $field['definitionUid'] = $other->definitionUid;
        $field['handle'] = $other->handle;
    }))->toBe(1);
    $saved = stencilSaveRequest($actor, 'save', [...$builder['data'], ...$builder['saveRequestData'], ...$builder['saveDuplicateRequestData']]);
    expect($saved['success'] ?? false)->toBeTrue();
    $live = new Form(['title' => 'Signed selection', 'handle' => 'signedSelection' . bin2hex(random_bytes(5))]);
    Formie::$plugin->getStencils()->getStencilById($saved['entityId'])->applyStencilToForm($live, true);
    expect(Craft::$app->getElements()->saveElement($live))->toBeTrue();
    $reloaded = Form::find()->id($live->id)->one();
    expect($reloaded->getFieldByHandle('email')->definitionId)->toBe($definitionId)
        ->and($reloaded->getFieldByHandle('otherEmail'))->toBeNull();
});
