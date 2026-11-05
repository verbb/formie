<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\User;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Email;
use verbb\formie\fields\Group;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\helpers\Table;
use verbb\formie\services\Permissions;
use Tests\Support\PermissionTestHelper;
use yii\base\InvalidConfigException;
use yii\web\ForbiddenHttpException;

beforeEach(function(): void {
    Craft::$app->getProjectConfig()->flush();
    $this->syncedImportTransaction = Craft::$app->getDb()->beginTransaction();
});

afterEach(function(): void {
    $this->syncedImportTransaction->rollBack();
    Craft::$app->getInfo()->configVersion = (new Query())->select('configVersion')->from(\craft\db\Table::INFO)->scalar();
    Craft::$app->getProjectConfig()->reset();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    Formie::$plugin->getForms()->invalidateFormCaches();
});

function syncedImportReload(Form $form): Form
{
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    Formie::$plugin->getForms()->invalidateFormCaches();
    return Form::find()->withoutCpIndexScope()->id($form->id)->status(null)->one();
}

function syncedImportFixtureConfig(mixed $value): mixed
{
    if (!is_array($value)) {
        return $value;
    }
    foreach (['id', 'definitionId', 'layoutId', 'pageId', 'rowId', 'uid', 'reference', 'nestedLayoutId', 'errors', 'settings', 'isSynced'] as $key) {
        unset($value[$key]);
    }
    return array_map('syncedImportFixtureConfig', $value);
}

function syncedImportDocuments(bool $nested = false, bool $parent = false, bool $name = false): array
{
    $source = formie()->form(['title' => 'Shared source'])->addFieldConfig($parent ? [
        'type' => $name ? \verbb\formie\fields\Name::class : Group::class, 'label' => 'Contact', 'handle' => 'contact',
        ...($name ? ['useMultipleFields' => true] : []),
        'rows' => $name ? (new \verbb\formie\fields\Name(['useMultipleFields' => true]))->getSubFields() : [['fields' => [['type' => Email::class, 'label' => 'Email', 'handle' => 'email']]]],
    ] : ['type' => Email::class, 'label' => 'Shared email', 'handle' => 'email'])->create();
    $field = $source->getFields()[0];
    $config = syncedImportFixtureConfig($field->getFormBuilderConfig());
    $config['definitionId'] = $field->definitionId;
    foreach (['id', 'uid', 'reference', 'layoutId', 'pageId', 'rowId'] as $key) {
        unset($config[$key]);
    }
    $config['isSynced'] = true;
    $config['required'] = true;
    $target = formie()->form(['title' => 'Other shared form'])->addFieldConfig($nested ? [
        'type' => Group::class, 'label' => 'Contact', 'handle' => 'contact', 'rows' => [['fields' => [$config]]],
    ] : $config)->create();
    $documents = array_map(fn($form) => ImportExportHelper::generateFormExport(syncedImportReload($form)), [$source, $target]);
    $uid = $field->definitionUid;
    expect($documents[0]['pages'][0]['rows'][0]['fields'][0]['syncedDefinitionUid'])->toBe($uid);
    // The source environment no longer exists; every target definition must be created from the documents.
    Craft::$app->getElements()->deleteElement($target, true);
    Craft::$app->getElements()->deleteElement($source, true);
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    Formie::$plugin->getForms()->invalidateFormCaches();
    expect(Formie::$plugin->getFields()->getFieldDefinitionByUid($uid))->toBeNull();
    return [$documents, $uid];
}

function syncedImportIndependentCopy(array $document): array
{
    // Simulate a form imported before relationships were supported.
    $strip = function(array $rows) use (&$strip): array {
        foreach ($rows as &$row) {
            foreach ($row['fields'] as &$field) {
                unset($field['syncedDefinitionUid']);
                if (isset($field['settings']['rows'])) {
                    $field['settings']['rows'] = $strip($field['settings']['rows']);
                }
            }
        }
        return $rows;
    };
    foreach ($document['pages'] as &$page) {
        $page['rows'] = $strip($page['rows']);
    }
    return $document;
}

function syncedImportAdmin(bool $admin = true): User
{
    $name = 'syncAdmin' . bin2hex(random_bytes(5));
    $user = new User(['username' => $name, 'email' => $name . '@example.test', 'active' => true, 'admin' => $admin]);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    return $user;
}

function syncedImportStoreFile(array $document, User $user): array
{
    $files = new \verbb\formie\services\FormImportFiles(Craft::$app->getAssets()->getTempAssetUploadFs());
    $stream = fopen('php://temp', 'w+b');
    fwrite($stream, json_encode($document, JSON_THROW_ON_ERROR));
    rewind($stream);
    $filename = $files->store($stream, (int)$user->id);
    fclose($stream);
    // The fixture transaction spans simulated requests; release its queued setup lock as a committed setup would.
    Craft::$app->getMutex()->releaseQueuedLocks();
    return [$files, $filename];
}

function syncedImportRender(\craft\web\Response $response): string
{
    Craft::$app->getView()->registerTwigExtension(new \verbb\base\web\twig\Extension());
    (new \craft\web\TemplateResponseFormatter())->format($response);
    return $response->content;
}

function syncedImportPreview(string $filename, User $user): string
{
    return \Tests\Support\WebRequestTestHelper::withWebRequestContext(function($request) use ($filename, $user): string {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity($user);
        $controller = new \verbb\formie\controllers\ImportExportController('import-export', Formie::$plugin);
        return syncedImportRender($controller->runAction('import-configure', ['filename' => $filename]));
    }, ['method' => 'GET', 'headers' => ['Accept' => 'text/html']]);
}

function syncedImportComplete(string $filename, User $user, array $params): \craft\web\Response
{
    return \Tests\Support\WebRequestTestHelper::withWebRequestContext(function($request) use ($filename, $user, $params): \craft\web\Response {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity($user);
        $request->setBodyParams($params + [
            $request->csrfParam => $request->getCsrfToken(), 'filename' => $filename,
            'redirect' => Craft::$app->getSecurity()->hashData('formie/settings/import-export/import-completed/{id}'),
        ]);
        $controller = new \verbb\formie\controllers\ImportExportController('import-export', Formie::$plugin);
        $response = $controller->runAction('import-complete');
        if ($response->statusCode !== 302) {
            syncedImportRender($response);
        }
        return $response;
    }, ['method' => 'POST', 'headers' => ['Accept' => 'text/html']]);
}

function syncedImportXpath(string $html): \DOMXPath
{
    $dom = new \DOMDocument();
    @$dom->loadHTML($html);
    return new \DOMXPath($dom);
}

function syncedImportUpdateOptions(array $document, string $uid): array
{
    $plan = ImportExportHelper::planImport($document);
    return ['definitions' => [$uid => ['action' => 'update', 'expected' => $plan['syncedFields'][$uid]['expected']]]];
}

it('imports separate forms in either order with one shared definition and independent required states', function(bool $reverse, bool $nested): void {
    [$documents, $uid] = syncedImportDocuments($nested);
    if ($reverse) {
        $documents = array_reverse($documents);
    }
    $forms = array_map(fn($document) => ImportExportHelper::createFromImport($document)->form, $documents);
    $fields = array_map(function($form) {
        $form = syncedImportReload($form);
        return $form->getFieldByHandle('email') ?? $form->getFieldByHandle('contact')->getFields()[0];
    }, $forms);
    expect($fields[0]->definitionUid)->toBe($uid)
        ->and($fields[1]->definitionId)->toBe($fields[0]->definitionId)
        ->and($fields[0]->reference)->not->toBe($fields[1]->reference)
        ->and($fields[0]->required)->toBe($reverse)
        ->and($fields[1]->required)->toBe(!$reverse);
    $before = $fields[0]->reference;
    $updated = ImportExportHelper::updateFromImport($documents[0], syncedImportReload($forms[0]))->form;
    $field = $updated->getFieldByHandle('email') ?? $updated->getFieldByHandle('contact')->getFields()[0];
    expect($field->reference)->toBe($before)->and($field->definitionId)->toBe($fields[0]->definitionId);
})->with([[false, false], [true, false], [false, true], [true, true]]);

it('previews conflicts and only changes other forms when updating is explicitly selected', function(): void {
    [$documents, $uid] = syncedImportDocuments();
    $first = ImportExportHelper::createFromImport($documents[0])->form;
    $documents[1]['pages'][0]['rows'][0]['fields'][0]['settings']['label'] = 'Updated locally';
    $plan = ImportExportHelper::planImport($documents[1]);
    expect($plan['syncedFields'][$uid]['status'])->toBe('conflict')
        ->and(array_column($plan['syncedFields'][$uid]['forms'], 'id'))->toContain($first->id);
    $second = ImportExportHelper::createFromImport($documents[1])->form;
    expect(syncedImportReload($second)->getFieldByHandle('email')->label)->toBe('Shared email');
    ImportExportHelper::updateFromImport($documents[1], syncedImportReload($second), syncedImportUpdateOptions($documents[1], $uid));
    expect(syncedImportReload($first)->getFieldByHandle('email')->label)->toBe('Updated locally');
});

it('keeps unrelated matching handles independent while preserving exported relationships', function(): void {
    [$documents, $uid] = syncedImportDocuments();
    $unrelated = formie()->form()->emailField('email')->create();
    $first = ImportExportHelper::createFromImport($documents[0])->form;
    $second = ImportExportHelper::createFromImport($documents[1])->form;
    expect($first->getFieldByHandle('email')->definitionId)->not->toBe($unrelated->getFieldByHandle('email')->definitionId)
        ->and($second->getFieldByHandle('email')->definitionUid)->toBe($uid);
});

it('links an earlier independent import automatically and retains placement identity', function(): void {
    [$documents, $uid] = syncedImportDocuments();
    $first = ImportExportHelper::createFromImport($documents[0])->form;
    $second = ImportExportHelper::createFromImport(syncedImportIndependentCopy($documents[1]))->form;
    $field = $second->getFieldByHandle('email');
    expect($field->definitionUid)->not->toBe($uid)
        ->and(ImportExportHelper::planImport($documents[1], $second)['syncedFieldRelinks'])->toBe([$field->reference => ['label' => $field->label, 'canRelink' => true]]);
    $submission = formie()->submission($second)->with(['email' => 'ada@example.test'])->save();
    $updated = ImportExportHelper::updateFromImport($documents[1], syncedImportReload($second))->form;
    expect($updated->getFieldByHandle('email')->id)->toBe($field->id)
        ->and($updated->getFieldByHandle('email')->reference)->toBe($field->reference)
        ->and($updated->getFieldByHandle('email')->definitionId)->toBe($first->getFieldByHandle('email')->definitionId)
        ->and(Submission::find()->id($submission->id)->status(null)->one()->getFieldValue('email'))->toBe('ada@example.test');
});

it('rejects stale updates and malformed or contradictory shared definitions without partial imports', function(string $variant): void {
    [$documents, $uid] = syncedImportDocuments();
    $first = ImportExportHelper::createFromImport($documents[0])->form;
    $options = syncedImportUpdateOptions($documents[1], $uid);
    if ($variant === 'stale') {
        $field = $first->getFieldByHandle('email');
        $field->label = 'Changed since preview';
        expect(Formie::$plugin->getFields()->saveField($field))->toBeTrue();
    } elseif ($variant === 'uid') {
        $documents[1]['pages'][0]['rows'][0]['fields'][0]['syncedDefinitionUid'] = 'invalid';
    } else {
        $copy = $documents[1]['pages'][0]['rows'][0]['fields'][0];
        $copy['settings']['label'] = 'Contradictory';
        $documents[1]['pages'][0]['rows'][0]['fields'][] = $copy;
    }
    $count = (new Query())->from(Table::FORMIE_FORMS)->count();
    expect(fn() => ImportExportHelper::createFromImport($documents[1], $options))->toThrow(InvalidConfigException::class);
    expect((new Query())->from(Table::FORMIE_FORMS)->count())->toBe($count);
})->with(['stale', 'uid', 'duplicate']);

it('imports a synced parent with its shared nested layout', function(): void {
    [$documents, $uid] = syncedImportDocuments(false, true);
    $first = ImportExportHelper::createFromImport($documents[0])->form;
    $second = ImportExportHelper::createFromImport($documents[1])->form;
    $a = syncedImportReload($first)->getFieldByHandle('contact');
    $b = syncedImportReload($second)->getFieldByHandle('contact');
    expect($a->definitionUid)->toBe($uid)
        ->and($b->definitionId)->toBe($a->definitionId)
        ->and($b->nestedLayoutId)->toBe($a->nestedLayoutId)
        ->and($b->getFields()[0]->reference)->toBe($a->getFields()[0]->reference);
});

it('does not let import permission modify or attach to inaccessible shared definitions', function(): void {
    [$documents, $uid] = syncedImportDocuments(true);
    $first = ImportExportHelper::createFromImport($documents[1])->form;
    $name = 'syncImporter' . bin2hex(random_bytes(5));
    $user = new User(['username' => $name, 'email' => $name . '@example.test', 'active' => true]);
    expect(Craft::$app->getElements()->saveElement($user))->toBeTrue();
    Craft::$app->getUserPermissions()->saveUserPermissions($user->id, PermissionTestHelper::withParents([Permissions::PERM_IMPORT_FORMS]));
    $plan = ImportExportHelper::planImport($documents[0], null, $user);
    expect($plan['syncedFields'][$uid]['canReuse'])->toBeFalse()->and($plan['syncedFields'][$uid]['forms'])->toBe([]);
    expect(fn() => ImportExportHelper::importForUser($documents[0], null, $user))->toThrow(ForbiddenHttpException::class);
    expect(fn() => ImportExportHelper::importForUser($documents[0], null, $user, ['mode' => 'independent']))->toThrow(InvalidConfigException::class);
});


it('updates a shared parent without replacing child identities or losing existing values', function(): void {
    [$documents, $uid] = syncedImportDocuments(false, true);
    $first = ImportExportHelper::createFromImport($documents[0])->form;
    $child = $first->getFieldByHandle('contact')->getFields()[0];
    $submission = formie()->submission($first)->with(['contact' => ['email' => 'ada@example.test']])->save();
    $documents[1]['pages'][0]['rows'][0]['fields'][0]['settings']['rows'][0]['fields'][0]['settings']['label'] = 'Updated child';
    $second = ImportExportHelper::createFromImport($documents[1], syncedImportUpdateOptions($documents[1], $uid))->form;
    $updated = syncedImportReload($first)->getFieldByHandle('contact')->getFields()[0];
    expect($updated->label)->toBe('Updated child')
        ->and($updated->uid)->toBe($child->uid)
        ->and($updated->reference)->toBe($child->reference)
        ->and(Submission::find()->id($submission->id)->status(null)->one()->getFieldValue('contact.email'))->toBe('ada@example.test');
});

it('shares repeated nested placements in one import while keeping their Required settings', function(): void {
    [$documents, $uid] = syncedImportDocuments();
    $field = $documents[0]['pages'][0]['rows'][0]['fields'][0];
    $other = $field;
    $other['reference'] = \craft\helpers\StringHelper::UUID();
    $other['uid'] = \craft\helpers\StringHelper::UUID();
    $other['instanceSettings']['required'] = true;
    $documents[0]['pages'][0]['rows'][0]['fields'] = array_map(fn($handle, $child) => [
        'type' => Group::class, 'settings' => ['label' => $handle, 'handle' => $handle, 'rows' => [['fields' => [$child]]]],
    ], ['billing', 'shipping'], [$field, $other]);
    $form = ImportExportHelper::createFromImport($documents[0])->form;
    $a = $form->getFieldByHandle('billing')->getFields()[0];
    $b = $form->getFieldByHandle('shipping')->getFields()[0];
    expect($a->definitionUid)->toBe($uid)->and($b->definitionId)->toBe($a->definitionId)
        ->and($a->required)->toBeFalse()->and($b->required)->toBeTrue();
});

it('keeps references to a reused shared parent child valid in imported form settings', function(): void {
    [$documents, $uid] = syncedImportDocuments(false, true);
    $first = ImportExportHelper::createFromImport($documents[0])->form;
    $sourceReference = $documents[1]['pages'][0]['rows'][0]['fields'][0]['settings']['rows'][0]['fields'][0]['reference'];
    $documents[1]['settings'] = \craft\helpers\Json::decodeIfJson($documents[1]['settings']);
    $documents[1]['settings']['submissionTitleFormat'] = '{field:' . $sourceReference . '}';
    $second = ImportExportHelper::createFromImport($documents[1])->form;
    $targetReference = syncedImportReload($first)->getFieldByHandle('contact')->getFields()[0]->reference;
    expect($second->settings->submissionTitleFormat)->toBe('{field:' . $targetReference . '}');
});

it('rejects incompatible definitions without detaching the field', function(): void {
    [$documents, $uid] = syncedImportDocuments();
    $first = ImportExportHelper::createFromImport($documents[0])->form;
    $documents[1]['pages'][0]['rows'][0]['fields'][0]['type'] = \verbb\formie\fields\SingleLineText::class;
    expect(ImportExportHelper::planImport($documents[1])['syncedFields'][$uid]['status'])->toBe('incompatible');
    expect(fn() => ImportExportHelper::createFromImport($documents[1]))->toThrow(InvalidConfigException::class, 'different type');
    expect(fn() => ImportExportHelper::createFromImport($documents[1], ['definitions' => [$uid => ['action' => 'independent']]]))->toThrow(InvalidConfigException::class);
    expect(syncedImportReload($first)->getFieldByHandle('email')->definitionUid)->toBe($uid);
});

it('rolls back newly created shared definitions when the form fails validation', function(): void {
    [$documents, $uid] = syncedImportDocuments();
    $documents[0]['pages'][0]['rows'][0]['fields'][0]['settings']['handle'] = 'not a handle';
    $count = (new Query())->from(Table::FORMIE_FIELDS)->count();
    expect(fn() => ImportExportHelper::createFromImport($documents[0]))->toThrow(Exception::class);
    expect((new Query())->from(Table::FORMIE_FIELDS)->count())->toBe($count)
        ->and(Formie::$plugin->getFields()->getFieldDefinitionByUid($uid))->toBeNull();
});

it('retains destination translations when keeping shared settings', function(bool $parent): void {
    if (!Craft::$app->getIsMultiSite()) {
        test()->markTestSkipped('Requires multisite.');
    }
    [$documents, $uid] = syncedImportDocuments(false, $parent);
    $first = ImportExportHelper::createFromImport($documents[0])->form;
    $site = array_values(array_filter(Craft::$app->getSites()->getAllSites(), fn($site) => $site->id !== Formie::$plugin->getFormSiteOverrides()->getSourceSiteId($first)))[0];
    $fieldId = $parent ? $first->getFieldByHandle('contact')->getFields()[0]->definitionId : $first->getFieldByHandle('email')->definitionId;
    Formie::$plugin->getFieldSiteOverrides()->saveOverrides($site->id, [$fieldId => ['label' => 'Destination translation']]);
    $node = $documents[1]['pages'][0]['rows'][0]['fields'][0];
    $reference = $parent ? $node['settings']['rows'][0]['fields'][0]['reference'] : $node['reference'];
    $documents[1]['fieldSiteOverrides'] = [$site->handle => [$reference => ['label' => 'Incoming translation']]];
    expect(ImportExportHelper::planImport($documents[1])['syncedFields'][$uid]['status'])->toBe('conflict');
    $second = ImportExportHelper::createFromImport($documents[1])->form;
    expect(Formie::$plugin->getFieldSiteOverrides()->getOverride($fieldId, $site->id)['label'])->toBe('Destination translation');
    ImportExportHelper::updateFromImport($documents[1], syncedImportReload($second), syncedImportUpdateOptions($documents[1], $uid));
    expect(Formie::$plugin->getFieldSiteOverrides()->getOverride($fieldId, $site->id)['label'])->toBe('Incoming translation');
})->with([false, true]);

it('does not replace the child identities of an earlier independent parent import', function(): void {
    [$documents, $uid] = syncedImportDocuments(false, true);
    $first = ImportExportHelper::createFromImport($documents[0])->form;
    $second = ImportExportHelper::createFromImport(syncedImportIndependentCopy($documents[1]))->form;
    $before = $second->getFieldByHandle('contact')->getFields()[0]->uid;
    expect(array_column(ImportExportHelper::planImport($documents[1], $second)['syncedFieldRelinks'], 'canRelink'))->toContain(false);
    expect(fn() => ImportExportHelper::updateFromImport($documents[1], syncedImportReload($second)))->toThrow(InvalidConfigException::class, 'Create a new form');
    expect(syncedImportReload($second)->getFieldByHandle('contact')->getFields()[0]->uid)->toBe($before);
});


it('renders conflict choices and affected forms in the real import preview and submits a selected update', function(): void {
    [$documents, $uid] = syncedImportDocuments();
    $first = ImportExportHelper::createFromImport($documents[0])->form;
    $documents[1]['pages'][0]['rows'][0]['fields'][0]['settings']['label'] = '<script>Updated</script>';
    $user = syncedImportAdmin();
    [$files, $filename] = syncedImportStoreFile($documents[1], $user);
    try {
        $html = syncedImportPreview($filename, $user);
        expect($html)->toContain('Keep this site’s settings', 'Update synced field', $first->handle, 'different settings')
            ->not->toContain('<script>Updated</script>');
        $response = syncedImportComplete($filename, $user, ['formAction' => 'create', 'syncedFields' => syncedImportUpdateOptions($documents[1], $uid)]);
        expect($response->statusCode)->toBe(302, json_encode(Craft::$app->getUrlManager()->getRouteParams()));
        expect(syncedImportReload($first)->getFieldByHandle('email')->label)->toBe('<script>Updated</script>');
    } finally {
        $files->delete($filename, (int)$user->id);
    }
});

it('imports repeated shared parents with one child layout and remaps references to its persisted children', function(): void {
    [$documents, $uid] = syncedImportDocuments(false, true, true);
    $parent = $documents[0]['pages'][0]['rows'][0]['fields'][0];
    $other = $parent;
    $other['reference'] = \craft\helpers\StringHelper::UUID();
    $other['uid'] = \craft\helpers\StringHelper::UUID();
    $documents[0]['pages'][0]['rows'][0]['fields'] = array_map(fn($handle, $child) => [
        'type' => Group::class, 'settings' => ['label' => $handle, 'handle' => $handle, 'rows' => [['fields' => [$child]]]],
    ], ['billing', 'shipping'], [$parent, $other]);
    $sourceReference = $parent['settings']['rows'][0]['fields'][0]['reference'];
    $documents[0]['settings'] = \craft\helpers\Json::decodeIfJson($documents[0]['settings']);
    $documents[0]['settings']['submissionTitleFormat'] = '{field:' . $sourceReference . '}';
    $form = ImportExportHelper::createFromImport($documents[0])->form;
    $form = syncedImportReload($form);
    $a = $form->getFieldByHandle('billing')->getFields()[0];
    $b = $form->getFieldByHandle('shipping')->getFields()[0];
    expect($a->definitionUid)->toBe($uid)->and($b->nestedLayoutId)->toBe($a->nestedLayoutId)
        ->and($form->settings->submissionTitleFormat)->toBe('{field:' . $a->getFields()[0]->reference . '}');
});


it('rejects detaching options without changing existing synced fields', function(bool $perField): void {
    [$documents, $uid] = syncedImportDocuments();
    $first = ImportExportHelper::createFromImport($documents[0])->form;
    $before = $first->getFieldByHandle('email')->definitionId;
    $options = $perField ? ['definitions' => [$uid => ['action' => 'independent']]] : ['mode' => 'independent'];
    expect(fn() => ImportExportHelper::updateFromImport($documents[0], syncedImportReload($first), $options))->toThrow(InvalidConfigException::class);
    expect(syncedImportReload($first)->getFieldByHandle('email')->definitionId)->toBe($before);
})->with([false, true]);

it('requires a choice for a conflicting synced field before an interactive import changes anything', function(bool $unselected): void {
    [$documents, $uid] = syncedImportDocuments();
    ImportExportHelper::createFromImport($documents[0]);
    $documents[1]['pages'][0]['rows'][0]['fields'][0]['settings']['label'] = 'Updated locally';
    $user = syncedImportAdmin();
    $count = (new Query())->from(Table::FORMIE_FORMS)->count();
    $options = $unselected ? ['definitions' => [$uid => ['action' => '']]] : [];
    expect(fn() => ImportExportHelper::importForUser($documents[1], null, $user, $options))->toThrow(InvalidConfigException::class, 'Updated locally');
    expect((new Query())->from(Table::FORMIE_FORMS)->count())->toBe($count);
    $form = ImportExportHelper::importForUser($documents[1], null, $user, ['definitions' => [$uid => ['action' => 'keep']]])->form;
    expect(syncedImportReload($form)->getFieldByHandle('email')->label)->toBe('Shared email');
})->with([false, true]);

it('does not create a form when an existing form has no selected import action', function(string $formAction): void {
    [$documents] = syncedImportDocuments();
    ImportExportHelper::createFromImport($documents[0]);
    $user = syncedImportAdmin();
    [$files, $filename] = syncedImportStoreFile($documents[0], $user);
    $count = (new Query())->from(Table::FORMIE_FORMS)->count();
    try {
        $response = syncedImportComplete($filename, $user, $formAction === 'missing' ? [] : ['formAction' => $formAction]);
        expect((new Query())->from(Table::FORMIE_FORMS)->count())->toBe($count)
            ->and($response->statusCode)->not->toBe(302)
            ->and(trim(syncedImportXpath($response->content)->query('//p[@class="error"]')->item(0)->textContent))->toBe('Choose how to handle the existing form.');
    } finally {
        $files->delete($filename, (int)$user->id);
    }
})->with(['missing', '', 'other']);

it('shows only the review log and real decisions in the import preview', function(string $state): void {
    [$documents, $uid] = syncedImportDocuments();
    if ($state !== 'create') {
        ImportExportHelper::createFromImport($documents[0]);
    }
    if ($state === 'incompatible') {
        $documents[1]['pages'][0]['rows'][0]['fields'][0]['type'] = \verbb\formie\fields\SingleLineText::class;
    }
    if ($state === 'conflict') {
        $documents[1]['pages'][0]['rows'][0]['fields'][0]['settings']['label'] = 'Updated locally';
    }
    $user = syncedImportAdmin($state !== 'permission');
    if ($state === 'permission') {
        Craft::$app->getUserPermissions()->saveUserPermissions($user->id, PermissionTestHelper::withParents(['accessCp', 'accessPlugin-formie', Permissions::PERM_IMPORT_FORMS]));
    }
    [$files, $filename] = syncedImportStoreFile($documents[1], $user);
    try {
        $html = syncedImportPreview($filename, $user);
        expect($html)->not->toContain('syncedFields[mode]', 'independent', 'syncedFields[relink]', 'Retained:', 'Added:', 'Removed:', 'RemovedNotifications:', 'reuseUid', 'reuseHandle');
        $xpath = syncedImportXpath($html);
        $line = fn(string $color): string => implode(' ', array_map(fn($node) => $node->textContent, iterator_to_array($xpath->query('//div[contains(@class, "log-label") and contains(@class, "color-' . $color . '")]'))));
        expect($xpath->query('//select[starts-with(@name, "syncedFields[")]')->length)->toBe($state === 'conflict' ? 1 : 0)
            ->and($xpath->query('//input[@type="submit" and @disabled]')->length)->toBe(in_array($state, ['permission', 'incompatible'], true) ? 1 : 0);
        match ($state) {
            'create' => expect($line('32'))->toContain('Synced field, will be created.'),
            'reuse' => expect($line('32'))->toContain('Synced field, already on this site.'),
            'conflict' => expect($line('31'))->toContain('Synced field, settings differ on this site.'),
            'incompatible' => expect($line('31'))->toContain('Synced field, different field type on this site.'),
            'permission' => expect($line('31'))->toContain('you can’t manage every form using it'),
        };
        if ($state === 'conflict') {
            expect($xpath->query('//select[starts-with(@name, "syncedFields[")]/option[1]')->item(0)->getAttribute('value'))->toBe('')
                ->and($xpath->query('//input[@name="syncedFields[definitions][' . $uid . '][expected]"]')->length)->toBe(1);
        }
    } finally {
        $files->delete($filename, (int)$user->id);
    }
})->with(['create', 'reuse', 'conflict', 'permission', 'incompatible']);

it('names what an update removes or links, and prevents updates that would orphan answers', function(): void {
    [$documents] = syncedImportDocuments();
    $document = $documents[0];
    $withExtras = $document;
    $withExtras['pages'][0]['rows'][] = ['fields' => [['type' => Email::class, 'settings' => ['label' => 'Soon removed', 'handle' => 'soonRemoved']]]];
    $withExtras['notifications'] = [['name' => 'Soon removed notice', 'handle' => 'soonRemovedNotice', 'enabled' => true, 'subject' => 'Hi', 'to' => 'ada@example.test', 'content' => '[]']];
    $form = ImportExportHelper::createFromImport(syncedImportIndependentCopy($withExtras))->form;
    $plan = ImportExportHelper::planImport($document, syncedImportReload($form));
    expect($plan['removals'])->toBe(['fields' => ['Soon removed'], 'notifications' => ['Soon removed notice']]);
    $user = syncedImportAdmin();
    [$files, $filename] = syncedImportStoreFile($document, $user);
    try {
        $html = syncedImportPreview($filename, $user);
        $update = syncedImportXpath($html)->query('//div[@id="action-update"]')->item(0)->textContent;
        expect($update)->toContain('Soon removed', 'Soon removed notice', 'Shared email')
            ->and($html)->not->toContain($form->getFieldByHandle('soonRemoved')->reference);
        $response = syncedImportComplete($filename, $user, ['formAction' => 'update']);
        expect($response->statusCode)->toBe(302, json_encode(Craft::$app->getUrlManager()->getRouteParams()));
    } finally {
        $files->delete($filename, (int)$user->id);
    }

    [$parentDocuments] = syncedImportDocuments(false, true);
    ImportExportHelper::createFromImport(syncedImportIndependentCopy($parentDocuments[0]));
    [$files, $filename] = syncedImportStoreFile($parentDocuments[0], $user);
    try {
        $xpath = syncedImportXpath(syncedImportPreview($filename, $user));
        expect($xpath->query('//select[@name="formAction"]/option[@value="update"]/@disabled')->length)->toBe(1)
            ->and($xpath->query('//div[contains(@class, "color-31") and contains(., "Create a new form instead")]')->length)->toBe(1);
    } finally {
        $files->delete($filename, (int)$user->id);
    }
});
