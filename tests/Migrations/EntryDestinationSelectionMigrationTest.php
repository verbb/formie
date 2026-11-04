<?php

declare(strict_types=1);

use craft\db\Query;
use craft\elements\Entry as EntryElement;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\PlainText;
use craft\helpers\Json;
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
use verbb\formie\helpers\Table;
use verbb\formie\integrations\elements\Entry;
use verbb\formie\migrations\m261104_040000_entry_destination_uids;

function entryDestinationMigrationFixture(): array
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

function entryDestinationMigrationForm(string $handle, array $binding): Form
{
    $form = formie()->form()->singleLineTextField('headline')->singleLineTextField('story')->create();
    storeEntryDestinationBinding($form->id, $handle, $binding);

    return $form;
}

function storeEntryDestinationBinding(int $formId, string $handle, array $binding): void
{
    $settings = Json::decode(storedEntryDestinationSettings($formId));
    $settings['integrations'][$handle] = $binding;

    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $formId])->execute();
    Formie::$plugin->getForms()->invalidateFormCaches();
}

function storedEntryDestinationSettings(int $formId): string
{
    return (string)(new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $formId])->scalar();
}

function storedEntryDestination(int $formId, string $handle): array
{
    return Json::decode(storedEntryDestinationSettings($formId))['integrations'][$handle];
}

function entryMigrationBinding(string $selection, Form $form, string $bodyHandle): array
{
    return [
        'enabled' => true,
        'execution' => 'synchronous',
        'entryTypeSection' => $selection,
        'defaultAuthorId' => User::find()->admin(true)->status(null)->one()->id,
        'attributeMapping' => ['title' => References::field($form->getFieldByHandle('headline')->reference)],
        'fieldMapping' => [$bodyHandle => References::field($form->getFieldByHandle('story')->reference)],
    ];
}

beforeEach(function(): void {
    $this->entryDestinationFixtures = [];
});

afterEach(function(): void {
    foreach ($this->entryDestinationFixtures as [$section, $type, $body, $integration]) {
        Formie::$plugin->getIntegrations()->deleteIntegration($integration);
        Craft::$app->getEntries()->deleteSection($section);
        Craft::$app->getEntries()->deleteEntryType($type);
        Craft::$app->getFields()->deleteField($body);
    }
});

it('converts the selection left behind when the same-named Formie 2 migration was already applied', function(): void {
    $this->entryDestinationFixtures[] = [$section, $type, $body, $integration] = entryDestinationMigrationFixture();
    $form = entryDestinationMigrationForm($integration->handle, ['enabled' => true, 'entryTypeUid' => $type->uid]);

    $migrator = Formie::$plugin->getMigrator();
    $track = $migrator->track;
    $history = fn(string $name): array => (new Query())->from('{{%migrations}}')->where(['track' => $track, 'name' => $name])->all();
    $pairs = 'm240507_000000_entry_integration_ids';
    $uids = 'm241128_100000_entry_integrations';
    $repair = 'm261104_040000_entry_destination_uids';
    $recorded = array_merge($history($pairs), $history($repair));

    // Formie 2 records its own migration under the name Formie 3 later reused for the UID conversion.
    Craft::$app->getDb()->createCommand()->delete('{{%migrations}}', ['track' => $track, 'name' => [$pairs, $repair]])->execute();

    try {
        expect($history($uids))->toHaveCount(1);

        $pending = $migrator->getNewMigrations();

        expect($pending)->toContain($pairs)->toContain($repair)->not->toContain($uids);

        $migrator->migrateUp($pairs);

        expect(storedEntryDestination($form->id, $integration->handle)['entryTypeSection'])->toBe($section->id . ':' . $type->id);

        $migrator->migrateUp($repair);

        expect(storedEntryDestination($form->id, $integration->handle))->toBe([
            'enabled' => true,
            'entryTypeUid' => $type->uid,
            'entryTypeSection' => $section->uid . ':' . $type->uid,
        ]);
    } finally {
        Craft::$app->getDb()->createCommand()->delete('{{%migrations}}', ['track' => $track, 'name' => [$pairs, $repair]])->execute();

        foreach ($recorded as $row) {
            unset($row['id']);
            Craft::$app->getDb()->createCommand()->insert('{{%migrations}}', $row)->execute();
        }
    }
});

it('restores the mapping controls and mapped content for a repaired selection', function(): void {
    $this->entryDestinationFixtures[] = [$section, $type, $body, $integration] = entryDestinationMigrationFixture();
    $form = formie()->form()->singleLineTextField('headline')->singleLineTextField('story')->create();
    storeEntryDestinationBinding($form->id, $integration->handle, entryMigrationBinding($section->id . ':' . $type->id, $form, $body->handle));

    expect((new m261104_040000_entry_destination_uids())->safeUp())->toBeTrue();
    expect(storedEntryDestination($form->id, $integration->handle))->toBe(entryMigrationBinding($section->uid . ':' . $type->uid, $form, $body->handle));

    Craft::$app->getElements()->invalidateAllCaches();
    $form = Form::find()->id($form->id)->status(null)->one();
    $binding = array_values(array_filter(Formie::$plugin->getIntegrations()->getEnabledFormIntegrations($form), fn($binding) => $binding->integration->handle === $integration->handle))[0];
    $runtime = $binding->createRuntime();

    $schema = array_values(array_filter($runtime->getFormSettingsSchema($form), fn($item) => ($item['name'] ?? null) === 'fieldMapping'));

    expect($schema)->toHaveCount(1)
        ->and(array_column($schema[0]['integrationFields'] ?? [], 'handle'))->toContain($body->handle);

    $title = 'Repaired ' . uniqid();
    $submission = formie()->submission($form)->with(['headline' => $title, 'story' => 'Mapped body content'])->save();

    expect(Formie::$plugin->getIntegrationRunner()->runIntegration($runtime, $submission, 'repaired-' . uniqid(), 'synchronous', ['triggerEvent' => 'submit'])->status)->toBe(IntegrationStatus::Succeeded);

    Craft::$app->getElements()->invalidateAllCaches();
    $entry = EntryElement::find()->sectionId($section->id)->status(null)->title($title)->one();

    expect($entry)->not->toBeNull()
        ->and($entry->getFieldValue($body->handle))->toBe('Mapped body content');
});

it('leaves every other stored selection exactly as it was and is safe to run again', function(): void {
    $this->entryDestinationFixtures[] = [$section, $type, $body, $integration] = entryDestinationMigrationFixture();
    $this->entryDestinationFixtures[] = [$otherSection, $otherType, $otherBody, $otherIntegration] = entryDestinationMigrationFixture();
    $orphanId = 2147480000;

    $unchanged = [
        'uids' => $section->uid . ':' . $type->uid,
        'missing section' => $orphanId . ':' . $type->id,
        'missing type' => $section->id . ':' . $orphanId,
        'type of another section' => $section->id . ':' . $otherType->id,
        'not a pair' => (string)$type->id,
        'empty' => '',
    ];
    $forms = [];

    foreach ($unchanged as $name => $selection) {
        $forms[$name] = entryDestinationMigrationForm($integration->handle, ['enabled' => false, 'entryTypeSection' => $selection, 'fieldMapping' => ['anything' => ['kind' => 'literal', 'value' => 'Keep']]]);
    }

    // The same setting name on another kind of integration is not a Craft destination.
    $webhook = entryDestinationMigrationForm('someOtherIntegration', ['entryTypeSection' => $section->id . ':' . $type->id]);

    $before = array_map(fn($form) => storedEntryDestinationSettings($form->id), $forms);
    $webhookBefore = storedEntryDestinationSettings($webhook->id);

    // A trashed form can be restored, so its selection needs the same repair as a live one.
    $trashed = entryDestinationMigrationForm($integration->handle, ['enabled' => true, 'entryTypeSection' => $section->id . ':' . $type->id]);
    $second = entryDestinationMigrationForm($otherIntegration->handle, ['enabled' => true, 'entryTypeSection' => $otherSection->id . ':' . $otherType->id]);
    Craft::$app->getElements()->deleteElementById($trashed->id, Form::class);

    expect((new m261104_040000_entry_destination_uids())->safeUp())->toBeTrue();

    foreach ($forms as $name => $form) {
        expect(storedEntryDestinationSettings($form->id))->toBe($before[$name]);
    }

    expect(storedEntryDestinationSettings($webhook->id))->toBe($webhookBefore)
        ->and(storedEntryDestination($trashed->id, $integration->handle)['entryTypeSection'])->toBe($section->uid . ':' . $type->uid)
        ->and(storedEntryDestination($second->id, $otherIntegration->handle)['entryTypeSection'])->toBe($otherSection->uid . ':' . $otherType->uid);

    $repaired = storedEntryDestinationSettings($trashed->id);

    expect((new m261104_040000_entry_destination_uids())->safeUp())->toBeTrue()
        ->and(storedEntryDestinationSettings($trashed->id))->toBe($repaired);
});
