<?php

declare(strict_types=1);

use craft\elements\Entry as EntryElement;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\PlainText;
use craft\fields\Table as CraftTable;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\IntegrationStatus;
use verbb\formie\helpers\References;
use verbb\formie\integrations\elements\Entry;
use verbb\formie\models\IntegrationField;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceDiagnostic;
use verbb\formie\references\ReferenceUsage;

function wholeTableDestination(array $columns): array
{
    $key = StringHelper::randomString(8);
    $table = new CraftTable(['name' => 'Rows ' . $key, 'handle' => 'rows' . $key, 'columns' => $columns]);
    Craft::$app->getFields()->saveField($table);
    $text = new PlainText(['name' => 'Text ' . $key, 'handle' => 'text' . $key]);
    Craft::$app->getFields()->saveField($text);

    $layout = new FieldLayout(['type' => EntryElement::class]);
    $tab = new FieldLayoutTab(['name' => 'Content']);
    $tab->setLayout($layout);
    $tab->setElements([new EntryTitleField(), new CustomField($table), new CustomField($text)]);
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

    return [$section, $type, $table, $text, $integration];
}

function wholeTableRuntime(Form $form, Entry $integration, Section $section, EntryType $type, array $fieldMapping): Entry
{
    $form->settings->integrations = [$integration->handle => [
        'enabled' => true,
        'execution' => 'synchronous',
        'entryTypeSection' => $section->uid . ':' . $type->uid,
        'defaultAuthorId' => User::find()->admin(true)->status(null)->one()->id,
        'attributeMapping' => ['title' => References::field($form->getFieldByHandle('headline')->reference)],
        'fieldMapping' => $fieldMapping,
    ]];

    Craft::$app->getElements()->saveElement($form);
    Formie::$plugin->getForms()->invalidateFormCaches();

    $form = Form::find()->id($form->id)->status(null)->one();
    $bindings = array_filter(Formie::$plugin->getIntegrations()->getEnabledFormIntegrations($form), fn($binding) => $binding->integration->handle === $integration->handle);

    return reset($bindings)->createRuntime();
}

function wholeTableColumns(array $types): array
{
    $columns = [];

    foreach (array_values($types) as $index => $type) {
        $columns['col' . ($index + 1)] = ['heading' => ucfirst($type), 'handle' => $type . 'Cell', 'type' => $type];
    }

    return $columns;
}

beforeEach(function(): void {
    $this->wholeTableFixtures = [];
});

afterEach(function(): void {
    foreach ($this->wholeTableFixtures as [$section, $type, $table, $text, $integration]) {
        Formie::$plugin->getIntegrations()->deleteIntegration($integration);
        Craft::$app->getEntries()->deleteSection($section);
        Craft::$app->getEntries()->deleteEntryType($type);
        Craft::$app->getFields()->deleteField($table);
        Craft::$app->getFields()->deleteField($text);
    }
});

it('saves every row of a whole Table mapped to a Craft Table as it was submitted', function(string $timezone): void {
    $previousTimezone = Craft::$app->getTimeZone();
    Craft::$app->setTimeZone($timezone);

    try {
        $columns = wholeTableColumns(['singleline', 'date', 'time']);
        $this->wholeTableFixtures[] = [$section, $type, $table, $text, $integration] = wholeTableDestination($columns);
        $form = formie()->form()->singleLineTextField('headline')->tableField('slots', ['columns' => $columns])->create();
        $title = 'Whole table ' . uniqid();
        $runtime = wholeTableRuntime($form, $integration, $section, $type, [$table->handle => References::field($form->getFieldByHandle('slots')->reference)]);
        $submission = formie()->submission($form)->with(['headline' => $title, 'slots' => [
            ['col1' => 'First row', 'col2' => '2026-10-20', 'col3' => '12:34'],
            ['col1' => 'Second row', 'col2' => '', 'col3' => ''],
        ]])->save();

        // Delivery runs from the stored submission, not the values of the request that created it.
        Craft::$app->getElements()->invalidateAllCaches();
        $submission = Submission::find()->id($submission->id)->status(null)->one();

        expect(Formie::$plugin->getIntegrationRunner()->runIntegration($runtime, $submission, 'whole-table-' . uniqid(), 'synchronous', ['triggerEvent' => 'submit'])->status)->toBe(IntegrationStatus::Succeeded);

        Craft::$app->getElements()->invalidateAllCaches();
        $rows = EntryElement::find()->sectionId($section->id)->status(null)->title($title)->one()->getFieldValue($table->handle);

        expect($rows)->toHaveCount(2)
            ->and($rows[0]['col1'])->toBe('First row')
            ->and($rows[0]['col2']->setTimezone(new DateTimeZone($timezone))->format('Y-m-d'))->toBe('2026-10-20')
            ->and($rows[0]['col3']->setTimezone(new DateTimeZone($timezone))->format('H:i'))->toBe('12:34')
            ->and($rows[1]['col1'])->toBe('Second row')
            ->and($rows[1]['col2'])->toBeNull()
            ->and($rows[1]['col3'])->toBeNull();
    } finally {
        Craft::$app->setTimeZone($previousTimezone);
    }
})->with(['UTC', 'Australia/Melbourne', 'America/Los_Angeles']);

it('matches Table columns by ID or by handle', function(array $sourceColumns, array $destinationColumns, array $expected): void {
    $this->wholeTableFixtures[] = [$section, $type, $table, $text, $integration] = wholeTableDestination($destinationColumns);
    $form = formie()->form()->singleLineTextField('headline')->tableField('slots', ['columns' => $sourceColumns])->create();
    $title = 'Column match ' . uniqid();
    $runtime = wholeTableRuntime($form, $integration, $section, $type, [$table->handle => References::field($form->getFieldByHandle('slots')->reference)]);
    $submission = formie()->submission($form)->with(['headline' => $title, 'slots' => [array_fill_keys(array_keys($sourceColumns), 'Cell')]])->save();

    expect(Formie::$plugin->getIntegrationRunner()->runIntegration($runtime, $submission, 'column-match-' . uniqid(), 'synchronous', ['triggerEvent' => 'submit'])->status)->toBe(IntegrationStatus::Succeeded);

    Craft::$app->getElements()->invalidateAllCaches();
    $row = EntryElement::find()->sectionId($section->id)->status(null)->title($title)->one()->getFieldValue($table->handle)[0];

    expect(array_intersect_key($row, $expected))->toBe($expected);
})->with([
    'same ID, different handle' => [
        ['col1' => ['heading' => 'Item', 'handle' => 'formItem', 'type' => 'singleline']],
        ['col1' => ['heading' => 'Item', 'handle' => 'craftItem', 'type' => 'singleline']],
        ['col1' => 'Cell'],
    ],
    'same handle, different ID' => [
        ['col1' => ['heading' => 'Item', 'handle' => 'item', 'type' => 'singleline']],
        ['col7' => ['heading' => 'Item', 'handle' => 'item', 'type' => 'singleline']],
        ['col7' => 'Cell'],
    ],
]);

it('sends a whole Table as text to a text destination', function(): void {
    $form = formie()->form()->tableField('slots', ['columns' => wholeTableColumns(['singleline', 'singleline'])])->create();
    $submission = formie()->submission($form)->with(['slots' => [['col1' => 'A', 'col2' => 'B'], ['col1' => 'C', 'col2' => 'D']]])->save();
    $integration = new Entry(['name' => 'Entry', 'handle' => 'entry']);
    $mapping = ['destination' => References::field($form->getFieldByHandle('slots')->reference)];

    expect($integration->getFieldMappingValues($submission, $mapping, [new IntegrationField(['handle' => 'destination', 'type' => IntegrationField::TYPE_STRING])]))
        ->toBe(['destination' => 'A, B, C, D']);
});

it('keeps saving a single column mapped to a text field', function(): void {
    $columns = wholeTableColumns(['singleline', 'singleline']);
    $this->wholeTableFixtures[] = [$section, $type, $table, $text, $integration] = wholeTableDestination($columns);
    $form = formie()->form()->singleLineTextField('headline')->tableField('slots', ['columns' => $columns])->create();
    $title = 'Column ' . uniqid();
    $reference = $form->getFieldByHandle('slots')->reference;
    $runtime = wholeTableRuntime($form, $integration, $section, $type, [$text->handle => '{field:' . $reference . ':col2;scope=first}']);
    $submission = formie()->submission($form)->with(['headline' => $title, 'slots' => [['col1' => 'A', 'col2' => 'B']]])->save();

    expect(Formie::$plugin->getIntegrationRunner()->runIntegration($runtime, $submission, 'table-column-' . uniqid(), 'synchronous', ['triggerEvent' => 'submit'])->status)->toBe(IntegrationStatus::Succeeded);

    Craft::$app->getElements()->invalidateAllCaches();

    expect(EntryElement::find()->sectionId($section->id)->status(null)->title($title)->one()->getFieldValue($text->handle))->toBe('B');
});

it('only accepts a whole Table in an integration mapping', function(ReferenceUsage $usage): void {
    $form = formie()->form()->tableField('slots', ['columns' => wholeTableColumns(['singleline'])])->create();
    $submission = formie()->submission($form)->with(['slots' => [['col1' => 'A']]])->save();
    $token = References::field($form->getFieldByHandle('slots')->reference);

    expect(References::resolveValue($token, ReferenceContext::forSubmission($submission, usage: $usage))->diagnostic)->toBe(ReferenceDiagnostic::InvalidType);
})->with([ReferenceUsage::Text, ReferenceUsage::RichText, ReferenceUsage::EmailHeader, ReferenceUsage::Url, ReferenceUsage::Condition]);

it('keeps rejecting Table references that name nothing in an integration mapping', function(string $suffix, ReferenceDiagnostic $diagnostic): void {
    $form = formie()->form()->tableField('slots', ['columns' => wholeTableColumns(['singleline'])])->create();
    $submission = formie()->submission($form)->with(['slots' => [['col1' => 'A']]])->save();
    $token = '{field:' . $form->getFieldByHandle('slots')->reference . $suffix . '}';

    expect(References::resolveValue($token, ReferenceContext::forSubmission($submission, usage: ReferenceUsage::Integration))->diagnostic)->toBe($diagnostic);
})->with([
    'unknown column' => [':missing', ReferenceDiagnostic::InvalidSelector],
]);

it('fails delivery when a mapped Table column does not exist', function(): void {
    $columns = wholeTableColumns(['singleline']);
    $this->wholeTableFixtures[] = [$section, $type, $table, $text, $integration] = wholeTableDestination($columns);
    $form = formie()->form()->singleLineTextField('headline')->tableField('slots', ['columns' => $columns])->create();
    $title = 'Missing column ' . uniqid();
    $runtime = wholeTableRuntime($form, $integration, $section, $type, [$text->handle => '{field:' . $form->getFieldByHandle('slots')->reference . ':missing}']);
    $submission = formie()->submission($form)->with(['headline' => $title, 'slots' => [['col1' => 'A']]])->save();

    expect(Formie::$plugin->getIntegrationRunner()->runIntegration($runtime, $submission, 'table-missing-' . uniqid(), 'synchronous', ['triggerEvent' => 'submit'])->status)->toBe(IntegrationStatus::Failed)
        ->and(EntryElement::find()->sectionId($section->id)->status(null)->title($title)->exists())->toBeFalse();
});
