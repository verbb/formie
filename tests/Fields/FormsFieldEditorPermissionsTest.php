<?php

declare(strict_types=1);

use craft\elements\Entry;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\fields\Forms as FormsField;
use verbb\formie\services\Permissions;

beforeEach(function(): void {
    $this->formsFieldFixtures = [];
});

afterEach(function(): void {
    foreach ($this->formsFieldFixtures as $fixture) {
        Craft::$app->getElements()->deleteElement($fixture['editor'], true);
        Craft::$app->getEntries()->deleteSection($fixture['section']);
        Craft::$app->getEntries()->deleteEntryType($fixture['type']);
        Craft::$app->getFields()->deleteField($fixture['field']);
    }
});

/**
 * Creates an entry related to two forms, and an editor who can edit the entry and access Formie but not view forms.
 */
function formsFieldEditorFixture(): array
{
    $key = StringHelper::randomString(8);
    $forms = [
        formie()->form()->singleLineTextField('name')->create(),
        formie()->form()->singleLineTextField('name')->create(),
    ];

    $field = new FormsField(['name' => 'Forms ' . $key, 'handle' => 'forms' . $key]);
    expect(Craft::$app->getFields()->saveField($field))->toBeTrue();

    $layout = new FieldLayout(['type' => Entry::class]);
    $tab = new FieldLayoutTab(['name' => 'Content']);
    $tab->setLayout($layout);
    $tab->setElements([new EntryTitleField(), new CustomField($field)]);
    $layout->setTabs([$tab]);

    $type = new EntryType(['name' => 'Type ' . $key, 'handle' => 'type' . $key, 'hasTitleField' => true]);
    $type->setFieldLayout($layout);
    expect(Craft::$app->getEntries()->saveEntryType($type))->toBeTrue();

    $section = new Section(['name' => 'Section ' . $key, 'handle' => 'section' . $key, 'type' => Section::TYPE_CHANNEL]);
    $section->setEntryTypes([$type]);
    $section->setSiteSettings([new Section_SiteSettings(['siteId' => Craft::$app->getSites()->getPrimarySite()->id, 'enabledByDefault' => true, 'hasUrls' => false])]);
    expect(Craft::$app->getEntries()->saveSection($section))->toBeTrue();

    $entry = new Entry(['sectionId' => $section->id, 'typeId' => $type->id, 'title' => 'Entry ' . $key]);
    $entry->setFieldValue($field->handle, array_map(fn($form) => $form->id, $forms));
    expect(Craft::$app->getElements()->saveElement($entry))->toBeTrue();

    $editor = new User(['username' => 'formsEditor' . $key, 'email' => 'forms-editor-' . $key . '@example.test']);
    expect(Craft::$app->getElements()->saveElement($editor))->toBeTrue();
    Craft::$app->set('userPermissions', new \craft\services\UserPermissions());
    expect(Craft::$app->getUserPermissions()->saveUserPermissions($editor->id, [
        'accessCp',
        'accessPlugin-formie',
        Permissions::PERM_ACCESS_FORMS,
        'viewEntries:' . $section->uid,
        'saveEntries:' . $section->uid,
        'viewPeerEntries:' . $section->uid,
        'savePeerEntries:' . $section->uid,
        ...array_map(fn($site) => 'editSite:' . $site->uid, Craft::$app->getSites()->getAllSites()),
    ]))->toBeTrue();

    return compact('forms', 'field', 'type', 'section', 'entry', 'editor');
}

/**
 * Runs the callback in a control panel request signed in as the fixture's editor.
 */
function formsFieldAsEditor(array $fixture, callable $callback): mixed
{
    return WebRequestTestHelper::withWebRequestContext(function($request) use ($fixture, $callback): mixed {
        $request->setIsCpRequest(true);
        Craft::$app->getUser()->setIdentity(User::find()->id($fixture['editor']->id)->status(null)->one());

        return $callback();
    }, ['requestUri' => '/admin/entries']);
}

function formsFieldRelatedIds(array $fixture): array
{
    return array_map('intval', (new \craft\db\Query())
        ->select(['targetId'])
        ->from(['{{%relations}}'])
        ->where(['fieldId' => $fixture['field']->id, 'sourceId' => $fixture['entry']->id])
        ->orderBy(['sortOrder' => SORT_ASC])
        ->column());
}

it('shows related forms to editors who can’t view forms', function(): void {
    $this->formsFieldFixtures[] = $fixture = formsFieldEditorFixture();
    $formIds = array_map(fn($form) => (int)$form->id, $fixture['forms']);

    $relatedIds = formsFieldAsEditor($fixture, function() use ($fixture): array {
        $entry = Entry::find()->id($fixture['entry']->id)->status(null)->one();

        return array_map('intval', $entry->getFieldValue($fixture['field']->handle)->ids());
    });

    expect($relatedIds)->toBe($formIds);
});

it('keeps related forms when editors who can’t view forms save the entry', function(): void {
    $this->formsFieldFixtures[] = $fixture = formsFieldEditorFixture();
    $formIds = array_map(fn($form) => (int)$form->id, $fixture['forms']);

    formsFieldAsEditor($fixture, function() use ($fixture): void {
        $entry = Entry::find()->id($fixture['entry']->id)->status(null)->one();
        $entry->title = 'Edited by an editor';

        // The entry editor posts back the related forms it rendered, which was none before the fix.
        $renderedIds = $entry->getFieldValue($fixture['field']->handle)->ids();
        $entry->setFieldValue($fixture['field']->handle, $renderedIds);

        expect(Craft::$app->getElements()->saveElement($entry))->toBeTrue(json_encode($entry->getErrors()));
    });

    expect(formsFieldRelatedIds($fixture))->toBe($formIds);
});
