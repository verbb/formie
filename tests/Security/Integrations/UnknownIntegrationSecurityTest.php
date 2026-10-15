<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use craft\helpers\Json;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\Formie;
use verbb\formie\controllers\FormsController;
use verbb\formie\controllers\StencilsController;
use verbb\formie\elements\Form;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\elements\User as UserIntegration;
use verbb\formie\models\MissingIntegration;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\services\Integrations;
use verbb\formie\services\Permissions;
use verbb\formie\services\Stencils;
use yii\web\BadRequestHttpException;

function withUnknownIntegrationEditor(callable $callback, bool $missingClass = false, bool $integrationTab = true): void
{
    $previous = Formie::$plugin->getIntegrations();
    $registry = new class extends Integrations {
        public array $definitions = [];
        public function getAllIntegrations(): array { return $this->definitions; }
        public function getAllCaptchas(): array { return []; }
    };
    if ($missingClass) {
        $registry->definitions = [new MissingIntegration(['handle' => 'futureUsers', 'enabled' => true, 'expectedType' => 'UnavailableProvider'])];
    }
    Formie::$plugin->set('integrations', $registry);
    try {
        $suffix = bin2hex(random_bytes(5));
        $actor = new User(['username' => 'unknownEditor' . $suffix, 'email' => 'unknownEditor' . $suffix . '@example.test']);
        expect(Craft::$app->getElements()->saveElement($actor))->toBeTrue();
        Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
        expect(Craft::$app->getUserPermissions()->saveUserPermissions($actor->id, [
            'accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_FORMS, Permissions::PERM_MANAGE_FORMS,
            Permissions::PERM_IMPORT_FORMS, 'formie-accessStencils',
            ...($integrationTab ? ['formie-showFormIntegrations'] : []),
            ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
        ]))->toBeTrue();
        $form = formie()->form(['handle' => 'unknownForm' . $suffix])->singleLineTextField('email')->create();
        $projectConfig = Craft::$app->getProjectConfig();
        $mutex = Craft::$app->getMutex();
        WebRequestTestHelper::withWebRequestContext(function($request) use ($callback, $form, $actor, $registry, $projectConfig, $mutex): void {
            Craft::$app->set('projectConfig', $projectConfig);
            Craft::$app->set('mutex', $mutex);
            $request->setIsCpRequest(true);
            $actor = User::find()->id($actor->id)->status(null)->one();
            Craft::$app->getUser()->setIdentity($actor);
            $callback($request, $form, $actor, $registry);
        }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json']]);
    } finally {
        Formie::$plugin->set('integrations', $previous);
        Formie::$plugin->getForms()->invalidateFormCaches();
    }
}

function unknownBinding(): array
{
    return ['enabled' => true, 'groupUids' => ['future-group'], 'customSetting' => 'Preserved value'];
}

function unknownBindingPost($form, array $binding): array
{
    return ['id' => $form->id, 'siteId' => $form->siteId, 'title' => $form->title, 'handle' => $form->handle,
        'settings' => ['integrations' => ['futureUsers' => $binding]]];
}

function saveUnknownBinding($request, $form, $actor, array $binding, string $route): mixed
{
    if ($route === 'import') {
        $data = ImportExportHelper::generateFormExport($form);
        $data['settings'] = Json::decodeIfJson($data['settings']);
        $data['settings']['integrations']['futureUsers'] = $binding;
        return ImportExportHelper::importForUser($data, $form, $actor);
    }
    $request->setBodyParams(unknownBindingPost($form, $binding));
    return (new FormsController('forms', Formie::$plugin))->actionSave();
}

it('rejects planting changing or enabling unknown bindings before saving', function(string $operation, string $route, bool $missingClass): void {
    withUnknownIntegrationEditor(function($request, $form, $actor) use ($operation, $route): void {
        if ($operation !== 'new') {
            $form->settings->integrations = ['futureUsers' => unknownBinding()];
            if ($operation === 'enable') {
                $form->settings->integrations['futureUsers']['enabled'] = false;
            }
            expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        }
        $binding = unknownBinding();
        if ($operation === 'change') {
            $binding['customSetting'] = 'Changed';
        }
        if ($operation === 'truthy') {
            $binding['enabled'] = 'true';
        }
        $before = (new Query())->from(Table::FORMIE_FORMS)->all();
        expect(fn() => saveUnknownBinding($request, $form, $actor, $binding, $route))->toThrow(BadRequestHttpException::class, 'Configure the integration');
        expect((new Query())->from(Table::FORMIE_FORMS)->all())->toBe($before);
    }, $missingClass);
})->with(['new', 'change', 'enable', 'truthy'])->with(['form', 'import'])->with([false, true])->group('security');

it('preserves disabled portable bindings and unchanged historical settings', function(string $operation, string $route, bool $missingClass): void {
    withUnknownIntegrationEditor(function($request, $form, $actor) use ($operation, $route): void {
        $binding = unknownBinding();
        if ($operation === 'unchanged') {
            $form->settings->integrations = ['futureUsers' => $binding];
            expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        } else {
            $binding['enabled'] = false;
        }
        $result = saveUnknownBinding($request, $form, $actor, $binding, $route);
        expect($route === 'form' ? ($result?->data['errors'] ?? []) : [])->toBe([]);
        $stored = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
        expect($stored['integrations']['futureUsers'])->toBe($binding);
    }, $missingClass);
})->with(['disabled', 'unchanged'])->with(['form', 'import'])->with([false, true])->group('security');

it('rejects enabled unknown stencil copies even without the integrations tab', function(bool $missingClass, bool $integrationTab): void {
    withUnknownIntegrationEditor(function($request, $form): void {
        $form->settings->integrations = ['futureUsers' => unknownBinding()];
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        $before = (new Query())->from(Table::FORMIE_STENCILS)->all();
        $source = (new Query())->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->one();
        $post = unknownBindingPost($form, unknownBinding());
        $post['handle'] = 'unknownCopy' . bin2hex(random_bytes(5));
        $request->setBodyParams($post + [$request->csrfParam => $request->getCsrfToken()]);
        expect(fn() => (new FormsController('forms', Formie::$plugin))->runAction('save-as-stencil'))->toThrow(BadRequestHttpException::class, 'Configure the integration');
        expect((new Query())->from(Table::FORMIE_STENCILS)->all())->toBe($before)
            ->and((new Query())->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->one())->toBe($source);
    }, $missingClass, $integrationTab);
})->with([false, true])->with([false, true])->group('security');

it('uses the saved unknown stencil baseline for editing and preview but rejects new copies', function(string $operation): void {
    withUnknownIntegrationEditor(function($request, $form) use ($operation): void {
        $form->settings->integrations = ['futureUsers' => unknownBinding()];
        $stencil = new Stencil(['name' => 'Unknown stencil', 'handle' => 'unknownStencil' . bin2hex(random_bytes(5)), 'scope' => Stencils::SCOPE_SITE, 'data' => new StencilData()]);
        $stencil->data->populateFormData($form);
        expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();
        $binding = $stencil->data->settings->integrations['futureUsers'];
        if ($operation === 'change') {
            $binding['customSetting'] = 'Changed';
        }
        $post = ['stencilId' => $stencil->id, 'title' => $stencil->name, 'handle' => $stencil->handle,
            'settings' => ['integrations' => ['futureUsers' => $binding]]];
        if ($operation === 'duplicate') {
            $post['duplicateStencil'] = true;
        }
        $request->setBodyParams($post);
        $before = (new Query())->from(Table::FORMIE_STENCILS)->all();
        if (in_array($operation, ['change', 'duplicate'], true)) {
            expect(fn() => (new StencilsController('stencils', Formie::$plugin))->actionSave())->toThrow(BadRequestHttpException::class);
            expect((new Query())->from(Table::FORMIE_STENCILS)->all())->toBe($before);
        } elseif ($operation === 'preview') {
            $preview = Formie::$plugin->getFormPreview()->buildFormFromBodyParams($post + ['entityType' => 'stencil']);
            expect($preview->settings->integrations['futureUsers'])->toBe($binding);
        } else {
            $result = (new StencilsController('stencils', Formie::$plugin))->actionSave();
            expect($result?->data['errors'] ?? [])->toBe([]);
        }
    });
})->with(['unchanged', 'change', 'duplicate', 'preview'])->group('security');

it('keeps disabled unknown imports inactive when the definition becomes available', function(): void {
    withUnknownIntegrationEditor(function($request, $form, $actor, $registry): void {
        $group = new \craft\models\UserGroup(['name' => 'Later group ' . bin2hex(random_bytes(5)), 'handle' => 'laterGroup' . bin2hex(random_bytes(5))]);
        expect(Craft::$app->getUserGroups()->saveGroup($group))->toBeTrue();
        $binding = unknownBinding();
        $binding['groupUids'] = [$group->uid];
        $binding['enabled'] = false;
        saveUnknownBinding($request, $form, $actor, $binding, 'import');
        $registry->definitions = [new UserIntegration(['handle' => 'futureUsers', 'enabled' => true, 'groupUids' => []])];
        $loaded = Form::find()->id($form->id)->one();
        expect(Formie::$plugin->getIntegrations()->getEnabledFormIntegrations($loaded))->toBe([]);
        $binding['enabled'] = true;
        expect(fn() => saveUnknownBinding($request, $loaded, $actor, $binding, 'form'))->toThrow(\yii\web\ForbiddenHttpException::class, $group->name);
    });
})->group('security');

it('requires configuration for enabled new imports while permitting disabled imports', function(bool $enabled, bool $missingClass): void {
    withUnknownIntegrationEditor(function($request, $form, $actor) use ($enabled): void {
        $data = ImportExportHelper::generateFormExport($form);
        $data['settings'] = Json::decodeIfJson($data['settings']);
        $data['settings']['integrations'] = ['futureUsers' => ['enabled' => $enabled, 'customSetting' => 'Portable']];
        $before = (new Query())->from(Table::FORMIE_FORMS)->count();
        if ($enabled) {
            expect(fn() => ImportExportHelper::importForUser($data, null, $actor))->toThrow(BadRequestHttpException::class);
            expect((new Query())->from(Table::FORMIE_FORMS)->count())->toBe($before);
        } else {
            $result = ImportExportHelper::importForUser($data, null, $actor);
            expect($result->form->settings->integrations['futureUsers'])->toBe($data['settings']['integrations']['futureUsers']);
        }
    }, $missingClass);
})->with([false, true])->with([false, true])->group('security');

it('preserves unrelated form edits but rejects differently represented enabled unknown settings', function(string $operation): void {
    withUnknownIntegrationEditor(function($request, $form) use ($operation): void {
        $form->settings->integrations = ['futureUsers' => unknownBinding()];
        expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
        $post = unknownBindingPost($form, unknownBinding());
        if ($operation === 'omitted') {
            unset($post['settings']);
            $post['title'] = 'Unrelated edit';
        } elseif ($operation === 'reordered') {
            $post['settings']['integrations']['futureUsers'] = array_reverse(unknownBinding(), true);
        } else {
            $post['settings']['integrations']['futureUsers']['enabled'] = 1;
        }
        $request->setBodyParams($post);
        $run = fn() => (new FormsController('forms', Formie::$plugin))->actionSave();
        if ($operation === 'omitted') {
            expect($run()?->data['errors'] ?? [])->toBe([]);
            expect(Form::find()->id($form->id)->one()->settings->integrations['futureUsers'])->toBe(unknownBinding());
        } else {
            expect($run)->toThrow(BadRequestHttpException::class);
        }
    });
})->with(['omitted', 'reordered', 'enabled-integer'])->group('security');
