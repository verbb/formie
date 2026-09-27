<?php

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\elements\Form;
use verbb\formie\fields\Email;
use verbb\formie\fields\MissingField;
use verbb\formie\fields\Name;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\FormSerializer;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\models\FieldDefinition;
use verbb\formie\models\FieldLayout;
use verbb\formie\services\Fields;
use yii\base\Event;
use yii\base\InvalidConfigException;

it('separates immutable definition metadata from runtime instance and keeps PHP aliases', function () {
    $form = formie()->form()->singleLineTextField('identity')->create();
    $field = $form->getFieldByHandle('identity');
    $definition = Formie::$plugin->getFields()->getFieldDefinitionById($field->definitionId);
    expect($definition)->toBeInstanceOf(FieldDefinition::class)
        ->and($definition)->not->toBeInstanceOf(Field::class)
        ->and($definition->uid)->toBe($field->definitionUid)
        ->and($field->fieldId)->toBe($field->definitionId)
        ->and($field->getCpEditConfig()['id'])->toBe((string)$field->id);
    $alias = new Email(['fieldId' => $field->definitionId, 'syncId' => $field->definitionId]);
    expect($alias->definitionId)->toBe($field->definitionId)->and($alias->isSynced)->toBeTrue();
});

it('does not share mutable field prototypes and rejects invalid registration before construction', function () {
    $service = Formie::$plugin->getFields();
    $first = $service->getRegisteredFieldByType(Email::class);
    $first->label = 'Leaked';
    expect($service->getRegisteredFieldByType(Email::class)->label)->not->toBe('Leaked');
    $listener = function ($event) { $event->fields[] = stdClass::class; };
    Event::on(Fields::class, Fields::EVENT_REGISTER_FIELDS, $listener);
    $service->resetFieldRegistryCache();
    try {
        expect(fn() => $service->getResolvedRegisteredFieldTypes())->toThrow(InvalidConfigException::class, 'subclasses');
    } finally {
        Event::off(Fields::class, Fields::EVENT_REGISTER_FIELDS, $listener);
        $service->resetFieldRegistryCache();
    }
});

it('rejects another forms builder instance and ignores protected definition links', function () {
    $one = formie()->form()->emailField('one')->create();
    $two = formie()->form()->emailField('two')->create();
    $pages = $one->getFormLayout()->getFormBuilderConfig();
    expect(fn() => (new FormSerializer())->hydrateBuilder($pages, $two))->toThrow(InvalidConfigException::class);
    $pages[0]['rows'][0]['fields'][0]['definitionId'] = $two->getFields()[0]->definitionId;
    $pages[0]['rows'][0]['fields'][0]['fieldId'] = $two->getFields()[0]->definitionId;
    $normalized = (new FormSerializer())->hydrateBuilder($pages, $one);
    expect($normalized[0]['rows'][0]['fields'][0]['definitionId'])->toBe($one->getFields()[0]->definitionId);
    $one->getFields()[0]->definitionId = $two->getFields()[0]->definitionId;
    expect(Craft::$app->getElements()->saveElement($one))->toBeFalse();
});

it('rejects unregistered builder classes and fixed children at the root', function () {
    $form = new Form();
    foreach ([stdClass::class, \verbb\formie\fields\subfields\NameFirst::class] as $type) {
        $pages = [['label' => 'Page', 'rows' => [['fields' => [['type' => $type, 'handle' => 'attack']]]]]];
        expect(fn() => (new FormSerializer())->hydrateBuilder($pages, $form))->toThrow(InvalidConfigException::class);
    }
});

it('retains a renamed and moved instance by reference during update import', function () {
    $form = formie()->form()->multiPage(2)->onPage(1)->singleLineTextField('before')->create();
    $old = $form->getFieldByHandle('before');
    $export = ImportExportHelper::generateFormExport($form);
    $node = $export['pages'][0]['rows'][0]['fields'][0];
    $node['settings']['handle'] = 'after';
    $export['pages'][0]['rows'] = [];
    $export['pages'][1]['rows'] = [['fields' => [$node]]];
    $result = ImportExportHelper::updateFromImport($export, $form);
    $field = $result->form->getFieldByHandle('after');
    expect($field->id)->toBe($old->id)->and($field->uid)->toBe($old->uid)
        ->and($field->definitionId)->toBe($old->definitionId)->and($result->changes['removed'])->toBe([]);
});

it('regenerates new import identities and remaps dependent tokens without changing handles', function () {
    $form = formie()->form()->singleLineTextField('source')->create();
    $old = $form->getFields()[0];
    $export = ImportExportHelper::generateFormExport($form);
    $export['settings'] = ['submissionTitleFormat' => '{field:' . $old->reference . '}'];
    $result = ImportExportHelper::createFromImport($export);
    $field = $result->form->getFieldByHandle('source');
    expect($field->id)->not->toBe($old->id)->and($field->uid)->not->toBe($old->uid)
        ->and($field->definitionId)->not->toBe($old->definitionId)
        ->and($result->form->settings->submissionTitleFormat)->toBe('{field:' . $field->reference . '}')
        ->and($result->remaps[$old->reference])->toBe($field->reference);
});

it('preserves unknown imported types and settings through save and export', function () {
    $form = formie()->form()->singleLineTextField('unknown')->create();
    $data = ImportExportHelper::generateFormExport($form);
    $data['pages'][0]['rows'][0]['fields'][0]['type'] = 'absent\\CustomField';
    $data['pages'][0]['rows'][0]['fields'][0]['settings']['customOption'] = ['preserve' => true];
    $result = ImportExportHelper::createFromImport($data);
    $field = $result->form->getFields()[0];
    expect($field)->toBeInstanceOf(MissingField::class)
        ->and($field->getSettings()['customOption'])->toBe(['preserve' => true])
        ->and($result->missingTypes)->toBe(['absent\\CustomField']);
    $again = ImportExportHelper::generateFormExport($result->form);
    expect($again['pages'][0]['rows'][0]['fields'][0]['type'])->toBe('absent\\CustomField');
});

it('reflects descendant mutations after layout and page lookups without hydrating unrelated definitions', function () {
    $form = formie()->form()->singleLineTextField('before')->create();
    $layout = $form->getFormLayout();
    expect($layout->getFieldByHandle('before'))->not->toBeNull();
    $page = $layout->getPages()[0];
    $page->getFields();
    $row = $page->getRows()[0];
    $row->setFields([new Email(['handle' => 'after', 'enabled' => false])]);
    expect($layout->getFieldByHandle('before'))->toBeNull()
        ->and($layout->getFieldByHandle('after'))->toBeInstanceOf(Email::class)
        ->and($page->getEnabledFields())->toBe([]);
    $row->getFields()[0]->handle = 'renamed';
    expect($layout->getFieldByHandle('renamed'))->not->toBeNull()
        ->and($page->getFieldByHandle('after'))->toBeNull();
});

it('rolls back the complete import when site overrides fail', function () {
    $source = formie()->form()->singleLineTextField('rollback')->create();
    $data = ImportExportHelper::generateFormExport($source);
    $service = Formie::$plugin->getFormSiteOverrides();
    $secondary = current(array_filter(Craft::$app->getSites()->getAllSites(), fn($site) => (int)$site->id !== $service->getSourceSiteId($source)));
    expect($secondary)->not->toBeFalse();
    $data['siteOverrides'][$secondary->handle] = ['title' => 'Fail'];
    $before = Form::find()->status(null)->count();
    $groupHandle = 'rolledBack' . bin2hex(random_bytes(4));
    $data['dependencies'][] = ['kind' => 'group', 'config' => ['name' => 'Rollback group', 'handle' => $groupHandle]];
    Formie::$plugin->set('formSiteOverrides', new class extends \verbb\formie\services\FormSiteOverrides {
        public function saveOverrides(int $formId, int $siteId, array $overrides): void { throw new RuntimeException('override failure'); }
    });
    try {
        expect(fn() => ImportExportHelper::createFromImport($data))->toThrow(RuntimeException::class, 'override failure');
        expect(Form::find()->status(null)->count())->toBe($before)
            ->and(Formie::$plugin->getFormGroups()->getGroupByHandle($groupHandle))->toBeNull();
        $groups = Craft::$app->getProjectConfig()->get(\verbb\formie\services\FormGroups::CONFIG_GROUPS_KEY) ?? [];
        expect(array_column($groups, 'handle'))->not->toContain($groupHandle);
    } finally {
        Formie::$plugin->set('formSiteOverrides', $service);
    }
});

class Workstream04CountingField extends SingleLineText
{
    public static int $created = 0;
    public function init(): void { parent::init(); self::$created++; }
}

it('prevents the Formie 3 #2637 all-fields regression by hydrating only the requested layout', function () {
    $service = Formie::$plugin->getFields();
    $listener = function ($event) { $event->fields[] = Workstream04CountingField::class; };
    Event::on(Fields::class, Fields::EVENT_REGISTER_FIELDS, $listener);
    $service->resetFieldRegistryCache();
    try {
        $unrelated = formie()->form()->addFieldConfig(['type' => Workstream04CountingField::class, 'handle' => 'other', 'label' => 'Other'])->create();
        $target = formie()->form()->emailField('target')->create();
        $service->resetFieldRegistryCache();
        Workstream04CountingField::$created = 0;
        $layout = $service->getLayoutById($target->layoutId);
        expect(Workstream04CountingField::$created)->toBe(0);
        expect($layout->getFields()[0])->toBeInstanceOf(Email::class)
            ->and(Workstream04CountingField::$created)->toBe(0);
        $all = $service->getAllFields();
        expect(array_filter($all, fn($field) => !($field instanceof Field)))->toBe([])
            ->and(array_column($all, 'id'))->toContain($unrelated->getFields()[0]->id, $target->getFields()[0]->id);
    } finally {
        Event::off(Fields::class, Fields::EVENT_REGISTER_FIELDS, $listener);
        $service->resetFieldRegistryCache();
    }
});

it('remaps fixed and arbitrary nested children and conditions when duplicating a form', function () {
    $form = formie()->form()->nameField('person', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])
        ->addFieldConfig(['type' => \verbb\formie\fields\Repeater::class, 'handle' => 'items', 'label' => 'Items',
            'rows' => [['fields' => [['type' => Email::class, 'handle' => 'email', 'label' => 'Email']]]]])->create();
    $source = $form->getFieldsRecursively();
    $form->settings->submissionTitleFormat = '{field:' . $source[0]->reference . '}';
    $copy = Formie::$plugin->getForms()->duplicateForm($form);
    expect($copy->id)->not->toBe($form->id)->and(count($copy->getFieldsRecursively()))->toBe(count($source));
    foreach ($copy->getFieldsRecursively() as $index => $field) {
        expect($field->id)->not->toBe($source[$index]->id)
            ->and($field->uid)->not->toBe($source[$index]->uid)
            ->and($field->reference)->not->toBe($source[$index]->reference)
            ->and($field->definitionId)->not->toBe($source[$index]->definitionId);
    }
    expect($copy->settings->submissionTitleFormat)->toBe('{field:' . $copy->getFields()[0]->reference . '}');
});

it('rejects forged synced selections and fixed-child reparenting', function () {
    $form = formie()->form()->nameField('first', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])
        ->nameField('second', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])->create();
    $pages = $form->getFormLayout()->getFormBuilderConfig();
    $fields = &$pages[0]['rows'][0]['fields'];
    // Factory fields may occupy separate rows; use the full graph to select the two parents.
    $parents = [];
    foreach ($pages[0]['rows'] as $row) { foreach ($row['fields'] as $field) { $parents[] = $field; } }
    $parents[1]['rows'] = $parents[0]['rows'];
    $pages[0]['rows'] = [['fields' => [$parents[1]]]];
    expect(fn() => (new FormSerializer())->hydrateBuilder($pages, $form))->toThrow(InvalidConfigException::class, 'different parent');
    $pages = [['label' => 'Page', 'rows' => [['fields' => [['type' => Email::class, 'handle' => 'email', 'label' => 'Email', 'isSynced' => true, 'definitionId' => $form->getFields()[0]->definitionId]]]]]];
    expect(fn() => (new FormSerializer())->hydrateBuilder($pages, $form))->toThrow(InvalidConfigException::class, 'synced field selection');
});

class Workstream04RecoverableField extends SingleLineText
{
    public ?string $customSetting = null;
    public function settingsAttributes(): array { return [...parent::settingsAttributes(), 'customSetting']; }
}

it('recovers preserved missing field settings after the extension is registered', function () {
    $form = formie()->form()->singleLineTextField('recover')->create();
    $data = ImportExportHelper::generateFormExport($form);
    $data['pages'][0]['rows'][0]['fields'][0]['type'] = Workstream04RecoverableField::class;
    $data['pages'][0]['rows'][0]['fields'][0]['settings']['customSetting'] = 'preserved';
    $result = ImportExportHelper::createFromImport($data);
    $missing = $result->form->getFields()[0];
    expect($missing)->toBeInstanceOf(MissingField::class);
    $listener = function ($event) { $event->fields[] = Workstream04RecoverableField::class; };
    Event::on(Fields::class, Fields::EVENT_REGISTER_FIELDS, $listener);
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    try {
        $field = Formie::$plugin->getFields()->getFieldById($missing->id);
        expect($field)->toBeInstanceOf(Workstream04RecoverableField::class)
            ->and($field->customSetting)->toBe('preserved')
            ->and($field->uid)->toBe($missing->uid)
            ->and($field->definitionId)->toBe($missing->definitionId);
    } finally {
        Event::off(Fields::class, Fields::EVENT_REGISTER_FIELDS, $listener);
        Formie::$plugin->getFields()->resetFieldRegistryCache();
    }
});

it('accepts a signed existing field selection only for its target form', function () {
    $source = formie()->form()->emailField('shared')->create();
    $target = formie()->form()->singleLineTextField('other')->create();
    $service = Formie::$plugin->getFields();
    $config = $service->getExistingFieldConfigs([$source->getFields()[0]->id], $target)[0]['field'];
    unset($config['id'], $config['uid'], $config['reference']);
    $config['isSynced'] = true;
    $pages = [['label' => 'Page', 'rows' => [['fields' => [$config]]]]];
    $fields = (new FormSerializer())->hydrateBuilder($pages, $target)[0]['rows'][0]['fields'];
    expect($fields[0]['definitionId'])->toBe($source->getFields()[0]->definitionId);
    expect(fn() => (new FormSerializer())->hydrateBuilder($pages, new Form()))->toThrow(InvalidConfigException::class, 'synced field selection');
});

it('keeps disabled imported types recoverable after reloading from storage', function () {
    $form = formie()->form()->emailField('disabled')->create();
    $data = ImportExportHelper::generateFormExport($form);
    $palette = Formie::$plugin->getFieldPalette();
    Formie::$plugin->set('fieldPalette', new class extends \verbb\formie\services\FieldPalette {
        public function isFieldClassEnabled(string $class): bool { return $class !== Email::class; }
    });
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    try {
        $result = ImportExportHelper::createFromImport($data);
        $field = Formie::$plugin->getFields()->getFieldById($result->form->getFields()[0]->id);
        expect($field)->toBeInstanceOf(MissingField::class)->and($field->expectedType)->toBe(Email::class)
            ->and($result->missingTypes)->toContain(Email::class);
    } finally {
        Formie::$plugin->set('fieldPalette', $palette);
        Formie::$plugin->getFields()->resetFieldRegistryCache();
    }
});

it('plans resource reuse fallback creation and destructive removals without writing', function () {
    $form = formie()->form()->emailField('remove')->create();
    $data = ImportExportHelper::generateFormExport($form);
    $plan = ImportExportHelper::planImport($data, $form);
    expect(array_column($plan['dependencies'], 'action'))->toContain('reuseUid');
    $missingDependency = $data;
    unset($missingDependency['formTemplate']);
    $missingDependency['formTemplateUid'] = 'unavailable-template';
    $missingDependency['dependencies'] = [];
    expect(ImportExportHelper::planImport($missingDependency)['warnings'])->toContain('Unavailable form template dependency: unavailable-template');
    foreach ($data['dependencies'] as &$dependency) { unset($dependency['config']['uid']); }
    unset($dependency);
    $data['pages'][0]['rows'] = [];
    $data['dependencies'][] = ['kind' => 'group', 'config' => ['name' => 'Imported resource', 'handle' => 'resource' . bin2hex(random_bytes(4))]];
    $plan = ImportExportHelper::planImport($data, $form);
    expect(array_column($plan['dependencies'], 'action'))->toContain('reuseHandle', 'create')
        ->and($plan['changes']['removed'])->toContain($form->getFields()[0]->reference)
        ->and($plan['warnings'])->not->toBeEmpty();
    $result = ImportExportHelper::createFromImport($data);
    expect($result->form->getGroup()->handle)->toBe(end($data['dependencies'])['config']['handle']);
});

it('does not activate protected settings when a missing parent type recovers', function () {
    $other = formie()->form()->nameField('other', ['rows' => (new Name())->getSubFields()])->create();
    $settings = ['handle' => 'safe', 'label' => 'Safe', 'nestedLayoutId' => $other->getFields()[0]->nestedLayoutId,
        'definitionId' => $other->getFields()[0]->definitionId, 'class' => stdClass::class];
    $safe = (new FormSerializer())->recoverSettings(Name::class, $settings);
    expect($safe)->not->toHaveKeys(['nestedLayoutId', 'definitionId', 'class']);
});
