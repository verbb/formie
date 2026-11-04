<?php

declare(strict_types=1);

use craft\base\Element;
use craft\db\Query;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\errors\GqlException;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\CategoryGroup;
use craft\models\CategoryGroup_SiteSettings;
use craft\models\GqlSchema;
use verbb\formie\elements\Form;
use verbb\formie\fields\Categories;
use verbb\formie\fields\Entries;
use verbb\formie\fields\Tags;
use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\options\ElementOptionSourceHelper;

function categorySourcesGroup(string $name, array $titles): array
{
    $group = new CategoryGroup(['name' => $name, 'handle' => 'categorySources' . StringHelper::randomString(8)]);
    $siteSettings = [];

    foreach (Craft::$app->getSites()->getAllSites() as $site) {
        $siteSettings[$site->id] = new CategoryGroup_SiteSettings(['siteId' => $site->id, 'hasUrls' => false]);
    }

    $group->setSiteSettings($siteSettings);
    expect(Craft::$app->getCategories()->saveGroup($group))->toBeTrue();

    $ids = [];
    $parent = null;

    foreach ($titles as $title) {
        $category = new Category(['groupId' => $group->id, 'title' => $title]);

        // The second title sits beneath the first so structure settings have a branch to work with.
        if ($parent && count($ids) === 1) {
            $category->setParentId($parent->id);
        }

        expect(Craft::$app->getElements()->saveElement($category))->toBeTrue();
        $parent ??= $category;
        $ids[] = (int)$category->id;
    }

    return ['group' => $group, 'source' => 'group:' . $group->uid, 'ids' => $ids];
}

// Craft remembers element sources for the length of a request, which here is the whole test run.
function categorySourcesForgetElementSources(): void
{
    $service = Craft::$app->getElementSources();

    (new ReflectionProperty($service, 'sources'))->setValue($service, []);
    (new ReflectionProperty(Element::class, 'sources'))->setValue(null, []);
}

function categorySourcesAllIds(): array
{
    $ids = array_map('intval', Category::find()->ids());
    sort($ids);

    return $ids;
}

function categorySourcesFieldIds(Categories $field): array
{
    $ids = array_map(static fn(array $option): int => (int)$option['value'], $field->getFieldOptions());
    sort($ids);

    return $ids;
}

function categorySourcesReload(Form $form): Form
{
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    Formie::$plugin->getForms()->invalidateFormCaches();
    Craft::$app->getElements()->invalidateAllCaches();

    return Form::find()->id($form->id)->one();
}

function categorySourcesStored(Categories $field): array
{
    return Json::decode((new Query())->select('settings')->from(Table::FORMIE_FIELDS)->where(['id' => $field->definitionId])->scalar());
}

function categorySourcesStore(Categories $field, callable $update): void
{
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FIELDS, [
        'settings' => Json::encode($update(categorySourcesStored($field))),
    ], ['id' => $field->definitionId], [], false)->execute();
}

// Replace the field with what the builder posts, across the JSON boundary it crosses both ways.
function categorySourcesBuilderSave(Form $form, string $handle, array $changes = []): Form
{
    $field = $form->getFieldByHandle($handle);
    $settings = json_decode(json_encode($field->getFormBuilderConfig(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    $layout = $form->getFormLayout();
    $pages = $layout->getPages();

    foreach ($pages[0]->getRows() as $row) {
        foreach ($row->getFields() as $index => $rowField) {
            if ($rowField->handle === $handle) {
                $fields = $row->getFields();
                $fields[$index] = array_merge($settings, $changes);
                $row->setFields($fields);
            }
        }
    }

    $layout->setPages($pages);
    $form->setFormLayout($layout);

    expect(Craft::$app->getElements()->saveElement($form))->toBeTrue();

    return categorySourcesReload($form);
}

function categorySourcesGraphqlIds(Form $form, string $handle): array
{
    $gql = Craft::$app->getGql();
    $previous = null;

    try {
        $previous = $gql->getActiveSchema();
    } catch (GqlException) {
    }

    $schema = new GqlSchema([
        'name' => 'Category sources',
        'uid' => StringHelper::UUID(),
        'scope' => [
            "formieForms.{$form->uid}:read",
            ...array_map(static fn(CategoryGroup $group): string => "categorygroups.{$group->uid}:read", Craft::$app->getCategories()->getAllGroups()),
        ],
    ]);
    $gql->flushCaches();
    $gql->setActiveSchema($schema);

    try {
        $result = $gql->executeQuery(
            $schema,
            'query($handle: [String]) { formieForm(handle: $handle) { formFields { handle ... on Field_Categories { sources source categories { id } } } } }',
            ['handle' => [$form->handle]],
        );
    } finally {
        $gql->flushCaches();

        if ($previous) {
            $gql->setActiveSchema($previous);
        }
    }

    expect($result)->not->toHaveKey('errors');

    foreach ($result['data']['formieForm']['formFields'] as $definition) {
        if ($definition['handle'] === $handle) {
            $ids = array_map(static fn(array $category): int => (int)$category['id'], $definition['categories']);
            sort($ids);

            return $ids;
        }
    }

    return [];
}

beforeEach(function(): void {
    $this->fruit = categorySourcesGroup('Fruit', ['Apple', 'Apricot', 'Avocado']);
    $this->colours = categorySourcesGroup('Colours', ['Red', 'Rust']);
    $this->shapes = categorySourcesGroup('Shapes', ['Round']);
    categorySourcesForgetElementSources();
});

afterEach(function(): void {
    foreach ([$this->fruit, $this->colours, $this->shapes] as $fixture) {
        Craft::$app->getCategories()->deleteGroup($fixture['group']);
    }

    categorySourcesForgetElementSources();
    Formie::$plugin->getFields()->resetFieldRegistryCache();
    Formie::$plugin->getForms()->invalidateFormCaches();
});

it('offers every category on a new field and keeps that choice through the builder', function(): void {
    $form = categorySourcesReload(formie()->form()->categoriesField('topics')->create());

    expect(categorySourcesStored($form->getFieldByHandle('topics'))['allowMultipleSources'])->toBeTrue()
        ->and(categorySourcesFieldIds($form->getFieldByHandle('topics')))->toBe(categorySourcesAllIds());

    $form = categorySourcesBuilderSave($form, 'topics');
    $field = $form->getFieldByHandle('topics');

    expect($field->sources)->toBe('*')
        ->and($field->getFormBuilderConfig()['sources'])->toBe('*')
        ->and(categorySourcesFieldIds($field))->toBe(categorySourcesAllIds());
});

it('saves several chosen sources through the builder and offers exactly those categories', function(): void {
    $form = categorySourcesReload(formie()->form()->categoriesField('topics')->create());
    $chosen = [$this->fruit['source'], $this->colours['source']];
    $expected = [...$this->fruit['ids'], ...$this->colours['ids']];
    sort($expected);

    foreach ([1, 2] as $save) {
        $form = categorySourcesBuilderSave($form, 'topics', $save === 1 ? ['sources' => $chosen] : []);
        $field = $form->getFieldByHandle('topics');

        expect(categorySourcesStored($field)['sources'])->toBe($chosen)
            ->and($field->getFormBuilderConfig()['sources'])->toBe($chosen)
            ->and(categorySourcesFieldIds($field))->toBe($expected);
    }
});

it('restores upgraded fields whose saved source list was being ignored', function(array $stored, string $expected): void {
    $form = formie()->form()->categoriesField('topics')->create();
    $sources = ['fruit' => $this->fruit['source'], 'colours' => $this->colours['source']];
    $resolve = static fn(mixed $value): mixed => is_array($value) ? array_map(static fn($key) => $sources[$key], $value) : ($sources[$value] ?? $value);

    categorySourcesStore($form->getFieldByHandle('topics'), static function(array $settings) use ($stored, $resolve): array {
        unset($settings['allowMultipleSources'], $settings['source'], $settings['sources']);

        return array_merge($settings, array_map($resolve, $stored));
    });

    $wanted = $expected === 'all' ? categorySourcesAllIds() : [...$this->fruit['ids'], ...$this->colours['ids']];
    sort($wanted);

    foreach ([1, 2] as $save) {
        $form = categorySourcesReload($form);

        expect(categorySourcesFieldIds($form->getFieldByHandle('topics')))->toBe($wanted);

        // Saving must keep the same choices.
        $form = categorySourcesBuilderSave($form, 'topics');

        expect(categorySourcesFieldIds($form->getFieldByHandle('topics')))->toBe($wanted);
    }
})->with([
    'several sources, no mode saved' => [['sources' => ['fruit', 'colours']], 'several'],
    'several sources, single-source default written by a later save' => [['allowMultipleSources' => false, 'sources' => ['fruit', 'colours'], 'source' => null], 'several'],
    'all sources, no mode saved, stale source' => [['sources' => '*', 'source' => 'fruit'], 'all'],
    'all sources, single-source default written by a later save' => [['allowMultipleSources' => false, 'sources' => '*', 'source' => null], 'all'],
]);

it('keeps a single-source field offering only its source, before and after a builder save', function(): void {
    $form = categorySourcesReload(formie()->form()->categoriesField('topics')->create());
    $source = $this->fruit['source'];

    categorySourcesStore($form->getFieldByHandle('topics'), static fn(array $settings): array => array_merge($settings, [
        'allowMultipleSources' => false,
        'sources' => '*',
        'source' => $source,
    ]));

    $form = categorySourcesReload($form);
    $field = $form->getFieldByHandle('topics');
    $expected = $this->fruit['ids'];
    sort($expected);

    expect($field->allowMultipleSources)->toBeFalse()
        ->and($field->source)->toBe($source)
        ->and(categorySourcesFieldIds($field))->toBe($expected)
        ->and($field->getFormBuilderConfig()['sources'])->toBe([$source]);

    foreach ([1, 2] as $save) {
        $form = categorySourcesBuilderSave($form, 'topics');
        $field = $form->getFieldByHandle('topics');

        expect(categorySourcesFieldIds($field))->toBe($expected)
            ->and(categorySourcesStored($field)['sources'])->toBe([$source])
            ->and($field->getFormBuilderConfig()['sources'])->toBe([$source]);
    }
});

it('leaves a source on its own meaning that one source', function(): void {
    $field = new Categories(['handle' => 'topics', 'source' => $this->colours['source']]);
    $expected = $this->colours['ids'];
    sort($expected);

    expect($field->allowMultipleSources)->toBeFalse()
        ->and($field->getInputSources())->toBe([$this->colours['source']])
        ->and(categorySourcesFieldIds($field))->toBe($expected);
});

it('treats a list holding the all source the same as all sources', function(): void {
    $field = new Categories(['handle' => 'topics', 'sources' => ['*']]);

    expect(categorySourcesFieldIds($field))->toBe(categorySourcesAllIds());
});

it('renders exactly the configured categories in every display type', function(string $displayType, array $settings): void {
    $sources = [$this->fruit['source'], $this->colours['source']];
    $form = categorySourcesReload(formie()->form()->categoriesField('topics', array_merge([
        'displayType' => $displayType,
        'sources' => $sources,
    ], $settings))->create());
    $expected = [...$this->fruit['ids'], ...$this->colours['ids']];

    $html = (string)Formie::$plugin->getRendering()->renderField($form, 'topics');
    preg_match_all('/<(?:option|input)\b[^>]*\bvalue="(\d+)"/', $html, $matches);
    $rendered = array_map('intval', $matches[1]);
    sort($rendered);
    sort($expected);

    expect($rendered)->toBe($expected);

    foreach ($this->shapes['ids'] as $id) {
        expect($rendered)->not->toContain($id);
    }
})->with([
    'checkboxes' => ['checkboxes', []],
    'radio' => ['radio', []],
    'dropdown' => ['dropdown', []],
    'multi-select dropdown' => ['dropdown', ['multi' => true]],
]);

it('accepts a category from a configured source and rejects one from outside them', function(): void {
    $form = categorySourcesReload(formie()->form()->categoriesField('topics', [
        'displayType' => 'checkboxes',
        'sources' => [$this->fruit['source'], $this->colours['source']],
    ])->create());
    $inside = [$this->fruit['ids'][0], $this->colours['ids'][0]];

    $accepted = formie()->submission($form)->with(['topics' => array_map('strval', $inside)])->save();
    $rejected = formie()->submission($form)
        ->with(['topics' => [(string)$this->shapes['ids'][0]]])
        ->allowValidationFailure()
        ->save();

    $reloaded = verbb\formie\elements\Submission::find()->id($accepted->id)->status(null)->one();
    $stored = array_map('intval', $reloaded->getFieldValue('topics')->ids());
    sort($stored);
    sort($inside);

    expect($stored)->toBe($inside)
        ->and($rejected)->toHaveFieldError('topics')
        ->and($rejected->getFieldValue('topics')->ids())->toBe([]);
});

it('still rejects a category outside a single-source field', function(): void {
    $form = categorySourcesReload(formie()->form()->categoriesField('topics', ['displayType' => 'radio', 'source' => $this->fruit['source']])->create());

    $accepted = formie()->submission($form)->with(['topics' => [(string)$this->fruit['ids'][0]]])->save();
    $rejected = formie()->submission($form)
        ->with(['topics' => [(string)$this->colours['ids'][0]]])
        ->allowValidationFailure()
        ->save();

    expect($accepted->getFieldValue('topics')->ids())->toBe([$this->fruit['ids'][0]])
        ->and($rejected)->toHaveFieldError('topics');
});

it('keeps GraphQL and client-rendered definitions on the configured categories', function(string $choice): void {
    [$settings, $wanted] = match ($choice) {
        'all' => [['sources' => '*'], categorySourcesAllIds()],
        'several' => [['sources' => [$this->fruit['source'], $this->colours['source']]], [...$this->fruit['ids'], ...$this->colours['ids']]],
        'one' => [['source' => $this->colours['source']], $this->colours['ids']],
    };
    $form = categorySourcesReload(formie()->form()->categoriesField('topics', $settings)->create());
    sort($wanted);

    $input = $form->getFieldByHandle('topics')->getClientRenderedDefinition()['input'];
    $clientIds = array_map(static fn(array $option): int => (int)$option['value'], $input['options']);
    sort($clientIds);

    expect($clientIds)->toBe($wanted)
        ->and(categorySourcesGraphqlIds($form, 'topics'))->toBe($wanted);
})->with([
    'all sources' => ['all'],
    'several sources' => ['several'],
    'one source' => ['one'],
]);

it('applies the root category and structure prefix with one source or several', function(string $choice): void {
    $root = Category::find()->id($this->fruit['ids'][0])->one();
    $config = array_merge($choice === 'one' ? ['source' => $this->fruit['source']] : ['sources' => [$this->fruit['source'], $this->colours['source']]], [
        'rootCategory' => [['id' => $root->id]],
        'showStructure' => true,
    ]);
    $form = categorySourcesReload(formie()->form()->categoriesField('topics', $config)->create());
    $options = $form->getFieldByHandle('topics')->getFieldOptions();

    // Only the child of the root is left, flush with the left edge rather than indented from the root.
    expect(array_map(static fn(array $option): int => (int)$option['value'], $options))->toBe([$this->fruit['ids'][1]])
        ->and($options[0]['level'])->toBe(0)
        ->and($options[0]['label'])->toBe('Apricot');
})->with([
    'one source' => ['one'],
    'several sources' => ['several'],
]);

it('shows the hierarchy when structure is on', function(): void {
    $form = categorySourcesReload(formie()->form()->categoriesField('topics', [
        'sources' => [$this->fruit['source'], $this->colours['source']],
        'showStructure' => true,
    ])->create());
    $labels = array_column($form->getFieldByHandle('topics')->getFieldOptions(), 'label');

    expect($labels)->toContain('- Apricot', '- Rust', 'Apple', 'Red');
});

it('offers sources and all in the builder and warns only when there are no groups', function(): void {
    $field = new Categories();
    $schema = $field->defineFormBuilderGeneralSchema();
    $controls = [];

    foreach ($schema as $control) {
        if (isset($control['name'])) {
            $controls[$control['name']] = $control;
        }
    }

    $values = array_column($controls['sources']['options'], 'value');

    expect($controls)->not->toHaveKey('source')
        ->and($controls['sources']['$field'])->toBe('checkboxSelect')
        ->and($controls['sources']['showAllOption'])->toBeTrue()
        ->and($controls['sources']['warning'])->toBeFalse()
        ->and($values)->toContain($this->fruit['source'], $this->colours['source'], $this->shapes['source'])
        ->and($values)->not->toContain('*')
        ->and($values)->not->toContain('')
        ->and($field->getFieldTypeConfigData()['warning'])->toBeFalse();
});

it('keeps saved single-source option lists on that source', function(): void {
    $params = ['source' => $this->fruit['source'], 'labelSource' => 'title', 'orderBy' => 'title ASC'];
    $config = ElementOptionSourceHelper::buildFieldConfig('categories', $params);
    $resolved = ElementOptionSourceHelper::resolveOptions('categories', $params);
    $ids = array_map(static fn(array $item): int => (int)$item['value'], $resolved->items);
    $expected = $this->fruit['ids'];
    sort($ids);
    sort($expected);

    $builder = ElementOptionSourceHelper::getBuilderConfig('categories');

    expect($config['source'])->toBe($this->fruit['source'])
        ->and($config)->not->toHaveKey('sources')
        ->and($resolved->error)->toBeNull()
        ->and($ids)->toBe($expected)
        ->and($builder['sourceMode'])->toBe('single')
        ->and(array_column($builder['sourceOptions'], 'value'))->not->toContain('*')
        ->and(array_column($builder['sourceOptions'], 'value'))->not->toContain('')
        ->and(ElementOptionSourceHelper::resolveOptions('categories', [])->error)->not->toBeNull();
});

it('does not change how Entries and Tags choose sources', function(): void {
    $tagGroup = Craft::$app->getTags()->getTagGroupByHandle('formieTestTags');
    $tagSource = 'taggroup:' . $tagGroup->uid;
    $form = categorySourcesReload(formie()->form()
        ->tagsField('labels', ['source' => $tagSource])
        ->entriesField('posts', ['sources' => '*'])
        ->create());
    $tags = $form->getFieldByHandle('labels');
    $entries = $form->getFieldByHandle('posts');

    expect($tags)->toBeInstanceOf(Tags::class)
        ->and($tags->allowMultipleSources)->toBeFalse()
        ->and($tags->getInputSources())->toBe([$tagSource])
        ->and($tags->getFormBuilderConfig()['source'])->toBe($tagSource)
        ->and(array_map('intval', $tags->getElementsQuery()->ids()))->toEqualCanonicalizing(array_map('intval', Tag::find()->groupId($tagGroup->id)->ids()))
        ->and($entries)->toBeInstanceOf(Entries::class)
        ->and($entries->allowMultipleSources)->toBeTrue()
        ->and($entries->getInputSources())->toBe('*')
        ->and($entries->getElementsQuery()->exists())->toBe(Entry::find()->exists());
});
