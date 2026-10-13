<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\controllers\FormsController;
use verbb\formie\elements\Form;
use verbb\formie\fields\SingleLineText;
use verbb\formie\Formie;
use verbb\formie\models\Notification;
use verbb\formie\services\Permissions;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

beforeEach(function (): void {
    if (!Craft::$app->getIsMultiSite()) {
        $this->markTestSkipped('Requires the default multisite runtime.');
    }
});

function translationAclFixture(bool $notifications = false, bool $allSites = true): array
{
    $foreign = formie()->form(['title' => 'Foreign translation form'])->singleLineTextField('privateField')->create();
    $rows = [['fields' => [['type' => SingleLineText::class, 'handle' => 'nested', 'label' => 'Nested']]]];
    $form = formie()->form(['title' => 'Translation form'])->settings(['usePerFormPermissions' => true])
        ->singleLineTextField('message')->groupField('details', ['rows' => $rows])
        ->repeaterField('items', ['rows' => $rows])->create();
    $notification = new Notification(['name' => 'Notice', 'handle' => 'notice', 'subject' => 'Source subject', 'to' => 'admin@example.test']);
    $form->setNotifications([$notification]);
    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();
    $source = Formie::$plugin->getFormSiteOverrides()->getSourceSiteId($form);
    $site = (int)current(array_filter(Craft::$app->getSites()->getAllSiteIds(), fn($id) => (int)$id !== $source));
    $name = 'translationAcl' . bin2hex(random_bytes(5));
    $user = new User(['username' => $name, 'email' => $name . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    $permissions = ['accessCp', 'accessPlugin-formie', Permissions::PERM_ACCESS_FORMS,
        Permissions::PERM_VIEW_FORMS, Permissions::PERM_MANAGE_FORMS . ':' . $form->uid];
    foreach ($allSites ? Craft::$app->getSites()->getAllSites() : [Craft::$app->getSites()->getSiteById($source)] as $editableSite) {
        $permissions[] = 'editSite:' . $editableSite->uid;
    }
    if ($notifications) {
        $permissions[] = 'formie-showNotifications:' . $form->uid;
    }
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $permissions))->toBeTrue();
    return compact('form', 'foreign', 'notification', 'source', 'site', 'user');
}

function translationAclSave(array $fixture, array $body, array $query = []): mixed
{
    return WebRequestTestHelper::withWebRequestContext(function ($request) use ($fixture): mixed {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($fixture['user']->id)->status(null)->one());
        Craft::$app->set('sites', new \craft\services\Sites());
        $request->setBodyParams(array_merge($request->getBodyParams(), [$request->csrfParam => $request->getCsrfToken()]));
        return (new FormsController('forms', Formie::$plugin))->runAction('save')?->data;
    }, ['method' => 'POST', 'headers' => ['Accept' => 'application/json'], 'queryParams' => $query,
        'bodyParams' => array_merge(['id' => $fixture['form']->id, 'siteId' => $fixture['site']], $body)]);
}

function translationAclSnapshot(): array
{
    $result = [];
    foreach (['formie_forms', 'formie_fields', 'formie_form_fields', 'formie_form_site_overrides', 'formie_field_site_overrides', 'formie_notifications', 'elements_sites'] as $table) {
        $result[$table] = (new Query())->from('{{%' . $table . '}}')->orderBy('id')->all();
    }
    return $result;
}

it('rejects foreign and malformed translation identities before saving any form data', function (string $variant): void {
    $fixture = translationAclFixture();
    ['form' => $form, 'foreign' => $foreign] = $fixture;
    $ownId = $form->getFieldByHandle('message')->definitionId;
    $foreignId = $foreign->getFieldByHandle('privateField')->definitionId;
    $overrides = [$ownId => ['label' => 'Changed'], $foreignId => ['label' => 'Foreign change']];
    $body = ['title' => 'Must not save', 'translations' => ['fieldOverrides' => $overrides]];
    if ($variant === 'numeric alias') {
        $body['translations']['fieldOverrides'] = [$foreignId . 'junk' => ['label' => 'Foreign change']];
    } elseif ($variant === 'posted layout') {
        $body['pages'] = $foreign->getFormLayout()->getFormBuilderConfig();
    } elseif ($variant === 'json') {
        $body['translations'] = json_encode($body['translations']);
    } elseif ($variant === 'malformed') {
        $body['translations']['fieldOverrides'] = 'invalid';
    } elseif ($variant === 'poisoned override') {
        Formie::$plugin->getFieldSiteOverrides()->saveOverride($ownId, $fixture['site'], ['definitionId' => $foreignId]);
    }
    $before = translationAclSnapshot();
    expect(fn() => translationAclSave($fixture, $body))->toThrow($variant === 'malformed' ? BadRequestHttpException::class : ForbiddenHttpException::class);
    expect(translationAclSnapshot())->toBe($before);
})->with(['mixed', 'numeric alias', 'posted layout', 'json', 'malformed', 'poisoned override'])->group('security');

it('authorizes the same effective site used by form hydration before saving or duplicating', function (bool $conflictingQuery, bool $duplicate): void {
    $fixture = translationAclFixture(allSites: false);
    $before = translationAclSnapshot();
    $body = ['title' => 'Must not save', 'saveAsNew' => $duplicate];
    $query = [];
    if ($conflictingQuery) {
        $body['siteId'] = $fixture['source'];
        $query['siteId'] = $fixture['site'];
    }
    expect(fn() => translationAclSave($fixture, $body, $query))->toThrow(ForbiddenHttpException::class);
    expect(translationAclSnapshot())->toBe($before);
})->with([false, true])->with([false, true])->group('security');

it('preserves notification translations for editors without notification access', function (string $variant): void {
    $fixture = translationAclFixture();
    ['form' => $form, 'site' => $site] = $fixture;
    $overrides = Formie::$plugin->getFormSiteOverrides();
    $overrides->saveTranslationBundle($form->id, $site, ['notifications' => ['notice' => ['subject' => 'Stored subject', 'content' => 'Stored content']]]);
    $original = $overrides->getOverrides($form->id, $site)['notifications'];
    $translations = ['title' => 'Translated title', 'fieldOverrides' => [$form->getFieldByHandle('message')->definitionId => ['label' => 'Translated label']]];
    if ($variant !== 'omitted') {
        $translations['notifications'] = $variant === 'empty' ? [] : ['notice' => ['subject' => 'Forged subject', 'content' => 'Forged content']];
    }
    $response = translationAclSave($fixture, ['translations' => $variant === 'json' ? json_encode($translations) : $translations]);
    expect($response)->not->toHaveKey('errors')
        ->and($overrides->getOverrides($form->id, $site)['notifications'])->toBe($original)
        ->and($overrides->getOverrides($form->id, $site)['title'])->toBe('Translated title')
        ->and(Formie::$plugin->getFieldSiteOverrides()->getOverride($form->getFieldByHandle('message')->definitionId, $site)['label'])->toBe('Translated label');
})->with(['forged', 'empty', 'omitted', 'json'])->group('security');

it('allows authorized nested and shared definition translations and notification updates', function (): void {
    $fixture = translationAclFixture(notifications: true);
    ['form' => $form, 'site' => $site] = $fixture;
    $sharedId = $form->getFieldByHandle('message')->definitionId;
    $shared = formie()->form(['title' => 'Shared translation consumer'])->addFieldConfig([
        'type' => SingleLineText::class, 'handle' => 'message', 'label' => 'Message', 'definitionId' => $sharedId, 'isSynced' => true,
    ])->create();
    $ids = [$sharedId, $form->getFieldByHandle('details')->getFieldByHandle('nested')->definitionId,
        $form->getFieldByHandle('items')->getFieldByHandle('nested')->definitionId];
    $translations = ['notifications' => ['notice' => ['subject' => 'Allowed subject']], 'fieldOverrides' => []];
    foreach ($ids as $id) {
        $translations['fieldOverrides'][$id] = ['label' => 'Translated ' . $id];
    }
    $response = translationAclSave($fixture, ['translations' => $translations,
        'notifications' => Formie::$plugin->getNotifications()->getNotificationsConfig($form->getNotifications())]);
    expect($response)->not->toHaveKey('errors')
        ->and(Formie::$plugin->getFormSiteOverrides()->getOverrides($form->id, $site)['notifications']['notice']['subject'])->toBe('Allowed subject');
    foreach ($ids as $id) {
        expect(Formie::$plugin->getFieldSiteOverrides()->getOverride($id, $site)['label'])->toBe('Translated ' . $id);
    }
    Formie::$plugin->getForms()->invalidateFormCaches();
    expect(Formie::$plugin->getForms()->getFormById($shared->id, $site)->getFieldByHandle('message')->label)->toBe('Translated ' . $sharedId);
});

it('keeps historical undeclared translations out of a successful secondary-site save response', function (): void {
    $fixture = translationAclFixture();
    ['form' => $form, 'site' => $site] = $fixture;
    $field = $form->getFieldByHandle('message');
    $stored = ['label' => 'Translated message', 'handle' => 'forgedHandle'];
    expect((new \verbb\formie\records\FieldSiteOverride([
        'fieldId' => $field->definitionId, 'siteId' => $site, 'overrides' => $stored,
    ]))->save(false))->toBeTrue();

    $response = translationAclSave($fixture, ['translations' => ['title' => 'Translated form']]);
    expect($response)->not->toHaveKey('errors');
    expect($response['multiSite']['fieldOverrides'][$site][$field->definitionId])->toBe(['label' => 'Translated message']);
    expect($response['data']['pages'][0]['rows'][0]['fields'][0]['handle'])->toBe('message');
    expect(Formie::$plugin->getFieldSiteOverrides()->getOverride($field->definitionId, $site))->toBe($stored);
})->group('security');

it('keeps a permitted source-site save working when the body names a different site', function (): void {
    $fixture = translationAclFixture(allSites: false);
    $response = translationAclSave($fixture, ['title' => 'Allowed source title'], ['siteId' => $fixture['source']]);
    expect($response)->not->toHaveKey('errors');
    Formie::$plugin->getForms()->invalidateFormCaches();
    expect(Formie::$plugin->getForms()->getFormById($fixture['form']->id, $fixture['source'])->title)->toBe('Allowed source title');
});
