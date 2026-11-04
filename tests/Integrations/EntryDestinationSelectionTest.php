<?php

declare(strict_types=1);

use craft\elements\Entry as EntryElement;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\PlainText;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\helpers\References;
use verbb\formie\integrations\elements\Entry;

function entryDestinationFixture(): array
{
    $key = StringHelper::randomString(8);
    $body = new PlainText(['name' => 'Body ' . $key, 'handle' => 'body' . $key]);
    Craft::$app->getFields()->saveField($body);

    $layout = new FieldLayout(['type' => EntryElement::class]);
    $tab = new FieldLayoutTab(['name' => 'Content']);
    $tab->setLayout($layout);
    $tab->setElements([new EntryTitleField(), new CustomField($body)]);
    $layout->setTabs([$tab]);

    $type = new EntryType(['name' => 'Type ' . $key, 'handle' => 'type' . $key, 'hasTitleField' => true]);
    $type->setFieldLayout($layout);
    Craft::$app->getEntries()->saveEntryType($type);

    $section = new Section(['name' => 'Section ' . $key, 'handle' => 'section' . $key, 'type' => Section::TYPE_CHANNEL]);
    $section->setEntryTypes([$type]);
    $section->setSiteSettings([new Section_SiteSettings(['siteId' => Craft::$app->getSites()->getPrimarySite()->id, 'enabledByDefault' => true, 'hasUrls' => false])]);
    Craft::$app->getEntries()->saveSection($section);

    $integration = new Entry(['name' => 'Entry ' . $key, 'handle' => 'entry' . $key, 'enabled' => true]);
    Formie::$plugin->getIntegrations()->saveIntegration($integration);

    return [$section, $type, $body, $integration];
}

function entryDestinationRuntime(Entry $integration, string $selection, Form $form, string $bodyHandle, array $extra = []): Entry
{
    $form->settings->integrations = [$integration->handle => [
        'enabled' => true,
        'execution' => 'synchronous',
        'entryTypeSection' => $selection,
        'defaultAuthorId' => User::find()->admin(true)->status(null)->one()->id,
        'attributeMapping' => ['title' => References::field($form->getFieldByHandle('headline')->reference)],
        'fieldMapping' => [$bodyHandle => References::field($form->getFieldByHandle('story')->reference)],
    ] + $extra];

    Craft::$app->getElements()->saveElement($form);
    Formie::$plugin->getForms()->invalidateFormCaches();

    $form = Form::find()->id($form->id)->status(null)->one();
    $bindings = array_filter(Formie::$plugin->getIntegrations()->getEnabledFormIntegrations($form), fn($binding) => $binding->integration->handle === $integration->handle);

    return reset($bindings)->createRuntime();
}

function deliverEntryDestination(Entry $runtime, Form $form, string $title, string $story): IntegrationStatus
{
    $submission = formie()->submission($form)->with(['headline' => $title, 'story' => $story])->save();

    return Formie::$plugin->getIntegrationRunner()->runIntegration($runtime, $submission, 'entry-destination-' . uniqid(), 'synchronous', ['triggerEvent' => 'submit'])->status;
}

function storedDestinationEntry(Section $section, string $title): ?EntryElement
{
    Craft::$app->getElements()->invalidateAllCaches();

    return EntryElement::find()->sectionId($section->id)->status(null)->title($title)->one();
}

beforeEach(function(): void {
    [$this->section, $this->type, $this->body, $this->integration] = entryDestinationFixture();
    $this->form = formie()->form()->singleLineTextField('headline')->singleLineTextField('story')->create();
});

afterEach(function(): void {
    Formie::$plugin->getIntegrations()->deleteIntegration($this->integration);
    Craft::$app->getEntries()->deleteSection($this->section);
    Craft::$app->getEntries()->deleteEntryType($this->type);
    Craft::$app->getFields()->deleteField($this->body);
});

it('delivers mapped content whichever way a valid destination is identified', function(string $identity): void {
    $selection = $identity === 'id' ? $this->section->id . ':' . $this->type->id : $this->section->uid . ':' . $this->type->uid;
    $runtime = entryDestinationRuntime($this->integration, $selection, $this->form, $this->body->handle);
    $title = 'Destination ' . uniqid();

    expect(deliverEntryDestination($runtime, $this->form, $title, 'Mapped body content'))->toBe(IntegrationStatus::Succeeded);

    $entry = storedDestinationEntry($this->section, $title);

    expect($entry)->not->toBeNull()
        ->and($entry->typeId)->toBe($this->type->id)
        ->and($entry->getFieldValue($this->body->handle))->toBe('Mapped body content');
})->with(['id', 'uid']);

it('offers the same field mapping controls for a destination identified by ID', function(): void {
    $runtime = entryDestinationRuntime($this->integration, $this->section->id . ':' . $this->type->id, $this->form, $this->body->handle, ['updateElement' => true]);
    $mappingFields = function(string $name) use ($runtime): array {
        $items = array_values(array_filter($runtime->getFormSettingsSchema($this->form), fn($item) => ($item['name'] ?? null) === $name));

        return array_column($items[0]['integrationFields'] ?? [], 'handle');
    };

    expect($mappingFields('fieldMapping'))->toContain($this->body->handle)
        ->and($mappingFields('updateElementMapping'))->toContain('title');
});

it('updates the matched entry rather than creating another for a destination identified by ID', function(): void {
    $runtime = entryDestinationRuntime($this->integration, $this->section->id . ':' . $this->type->id, $this->form, $this->body->handle, [
        'updateElement' => true,
        'updateElementMapping' => ['title' => References::field($this->form->getFieldByHandle('headline')->reference)],
    ]);
    $title = 'Matched ' . uniqid();

    expect(deliverEntryDestination($runtime, $this->form, $title, 'First'))->toBe(IntegrationStatus::Succeeded);

    $original = storedDestinationEntry($this->section, $title);

    expect(deliverEntryDestination($runtime, $this->form, $title, 'Second'))->toBe(IntegrationStatus::Succeeded);

    Craft::$app->getElements()->invalidateAllCaches();
    $entries = EntryElement::find()->sectionId($this->section->id)->status(null)->title($title)->all();

    expect($entries)->toHaveCount(1)
        ->and($entries[0]->id)->toBe($original->id)
        ->and($entries[0]->getFieldValue($this->body->handle))->toBe('Second');
});

it('fails without creating an entry when the identified destination no longer exists', function(string $case): void {
    $orphanId = 2147480000;
    $selection = $case === 'section' ? $orphanId . ':' . $this->type->id : $this->section->id . ':' . $orphanId;
    $runtime = entryDestinationRuntime($this->integration, $selection, $this->form, $this->body->handle);
    $title = 'Missing ' . uniqid();

    expect(deliverEntryDestination($runtime, $this->form, $title, 'Mapped body content'))->not->toBe(IntegrationStatus::Succeeded)
        ->and(EntryElement::find()->status(null)->title($title)->exists())->toBeFalse();
})->with(['section', 'type']);

it('does not save mapped content under a section and entry type that do not belong together', function(string $identity): void {
    [$otherSection, $otherType, $otherBody, $otherIntegration] = entryDestinationFixture();

    try {
        $selection = $identity === 'id' ? $this->section->id . ':' . $otherType->id : $this->section->uid . ':' . $otherType->uid;
        $runtime = entryDestinationRuntime($this->integration, $selection, $this->form, $this->body->handle);
        $title = 'Mismatched ' . uniqid();

        expect(deliverEntryDestination($runtime, $this->form, $title, 'Mapped body content'))->not->toBe(IntegrationStatus::Succeeded)
            ->and(EntryElement::find()->status(null)->title($title)->exists())->toBeFalse();
    } finally {
        Formie::$plugin->getIntegrations()->deleteIntegration($otherIntegration);
        Craft::$app->getEntries()->deleteSection($otherSection);
        Craft::$app->getEntries()->deleteEntryType($otherType);
        Craft::$app->getFields()->deleteField($otherBody);
    }
})->with(['id', 'uid']);
