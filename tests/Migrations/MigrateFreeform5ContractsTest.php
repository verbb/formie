<?php

declare(strict_types=1);

use craft\db\Query;
use Solspace\Freeform\Freeform as FreeformPlugin;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\migrations\plugins\MigrateFreeform5;
use Tests\Support\Fixtures\Freeform5FixtureFactory;

function ensureFreeformPluginReady(): bool
{
    return class_exists(FreeformPlugin::class)
        && Craft::$app->getPlugins()->isPluginEnabled('freeform')
        && Craft::$app->getDb()->tableExists('{{%freeform_forms}}');
}

function loadMigrationFixture(): array
{
    $fixture = json_decode(file_get_contents(dirname(__DIR__, 2) . '/.cache/verbb-tests/migration-fixture.json'), true, flags: JSON_THROW_ON_ERROR);
    $fixture['freeformForm'] = FreeformPlugin::getInstance()->forms->getFormById($fixture['formId']);
    return $fixture;
}

it('no-ops safely when target freeform form id does not exist', function (): void {
    if (!ensureFreeformPluginReady()) {
        throw new RuntimeException('Migration suite requires the installed Freeform fixture plugin.');
    }

    $initialCount = (int)Form::find()->status(null)->count();

    $migration = new MigrateFreeform5();
    $migration->formId = 9999999;

    $result = $migration->run();
    $finalCount = (int)Form::find()->status(null)->count();

    expect($result->ok)->toBeTrue()
        ->and($result->lines)->toBeArray()
        ->and($finalCount)->toBe($initialCount);
})->group('migrate-plugins');

it('normalizes migrated field handles for reserved words and invalid characters', function (): void {
    $migration = new MigrateFreeform5();

    $reservedHandlesProperty = new ReflectionProperty(MigrateFreeform5::class, '_reservedHandles');
    $reservedHandlesProperty->setAccessible(true);
    $reservedHandlesProperty->setValue($migration, ['title', 'id', 'uid']);

    $method = new ReflectionMethod(MigrateFreeform5::class, '_getFieldHandle');
    $method->setAccessible(true);

    $reservedHandle = $method->invoke($migration, 'title', false);
    $dashedHandle = $method->invoke($migration, 'my-field-handle', false);
    $longHandle = $method->invoke($migration, str_repeat('a', 80), false);

    expect($reservedHandle)->toBe('field_title')
        ->and($dashedHandle)->toBe('my_field_handle')
        ->and(strlen($longHandle))->toBeLessThanOrEqual(64);
})->group('migrate-plugins');

it('normalizes notification handles by replacing dashes', function (): void {
    $migration = new MigrateFreeform5();

    $method = new ReflectionMethod(MigrateFreeform5::class, 'getNotificationHandle');
    $method->setAccessible(true);

    $normalized = $method->invoke($migration, 'notify-admin');
    $unchanged = $method->invoke($migration, 'notify_admin');

    expect($normalized)->toBe('notify_admin')
        ->and($unchanged)->toBe('notify_admin');
})->group('migrate-plugins');

it('maps every supported _mapField field class to a Formie field instance', function (): void {
    if (!ensureFreeformPluginReady()) {
        throw new RuntimeException('Migration suite requires the installed Freeform fixture plugin.');
    }

    $fixture = loadMigrationFixture();
    $freeformForm = $fixture['freeformForm'];

    expect($freeformForm)->not->toBeNull();

    $migration = new MigrateFreeform5();
    $reservedHandlesProperty = new ReflectionProperty(MigrateFreeform5::class, '_reservedHandles');
    $reservedHandlesProperty->setAccessible(true);
    $reservedHandlesProperty->setValue($migration, ['title', 'id', 'uid']);

    $method = new ReflectionMethod(MigrateFreeform5::class, '_mapField');
    $method->setAccessible(true);

    $sourceFieldClasses = [];
    $mappedFieldClassesByHandle = [];

    foreach ($freeformForm->getPages() as $page) {
        foreach ($page->getLayout()->getAllRows() as $row) {
            foreach ($row->getAllFields() as $field) {
                $sourceFieldClasses[] = get_class($field);
                $mapped = $method->invoke($migration, $field);
                $mappedFieldClassesByHandle[$field->getHandle()] = $mapped ? get_class($mapped) : null;
            }
        }
    }

    $sourceFieldClasses = array_values(array_unique($sourceFieldClasses));

    expect($sourceFieldClasses)->toContain(
        'Solspace\\Freeform\\Fields\\Implementations\\CheckboxField',
        'Solspace\\Freeform\\Fields\\Implementations\\Pro\\ConfirmationField',
        'Solspace\\Freeform\\Fields\\Implementations\\CheckboxesField',
        'Solspace\\Freeform\\Fields\\Implementations\\Pro\\DatetimeField',
        'Solspace\\Freeform\\Fields\\Implementations\\DropdownField',
        'Solspace\\Freeform\\Fields\\Implementations\\EmailField',
        'Solspace\\Freeform\\Fields\\Implementations\\FileUploadField',
        'Solspace\\Freeform\\Fields\\Implementations\\Pro\\GroupField',
        'Solspace\\Freeform\\Fields\\Implementations\\HiddenField',
        'Solspace\\Freeform\\Fields\\Implementations\\HtmlField',
        'Solspace\\Freeform\\Fields\\Implementations\\Pro\\InvisibleField',
        'Solspace\\Freeform\\Fields\\Implementations\\MultipleSelectField',
        'Solspace\\Freeform\\Fields\\Implementations\\NumberField',
        'Solspace\\Freeform\\Fields\\Implementations\\Pro\\PhoneField',
        'Solspace\\Freeform\\Fields\\Implementations\\RadiosField',
        'Solspace\\Freeform\\Fields\\Implementations\\Pro\\RichTextField',
        'Solspace\\Freeform\\Fields\\Implementations\\Pro\\TableField',
        'Solspace\\Freeform\\Fields\\Implementations\\TextareaField',
        'Solspace\\Freeform\\Fields\\Implementations\\TextField',
        'Solspace\\Freeform\\Fields\\Implementations\\Pro\\WebsiteField',
    )->and($mappedFieldClassesByHandle)->toMatchArray([
        'consent' => 'verbb\\formie\\fields\\Agree',
        'confirmEmail' => 'verbb\\formie\\fields\\Email',
        'interests' => 'verbb\\formie\\fields\\Checkboxes',
        'scheduledAt' => 'verbb\\formie\\fields\\Date',
        'topic' => 'verbb\\formie\\fields\\Dropdown',
        'contactEmail' => 'verbb\\formie\\fields\\Email',
        'resumeFiles' => 'verbb\\formie\\fields\\FileUpload',
        'profileGroup' => 'verbb\\formie\\fields\\Group',
        'source' => 'verbb\\formie\\fields\\Hidden',
        'bannerHtml' => 'verbb\\formie\\fields\\Html',
        'secretToken' => 'verbb\\formie\\fields\\Hidden',
        'departments' => 'verbb\\formie\\fields\\Dropdown',
        'age' => 'verbb\\formie\\fields\\Number',
        'contactPhone' => 'verbb\\formie\\fields\\Phone',
        'choice' => 'verbb\\formie\\fields\\Radio',
        'extraRich' => 'verbb\\formie\\fields\\Content',
        'detailsTable' => 'verbb\\formie\\fields\\Table',
        'details' => 'verbb\\formie\\fields\\MultiLineText',
        'fullName' => 'verbb\\formie\\fields\\SingleLineText',
        'website' => 'verbb\\formie\\fields\\SingleLineText',
    ]);
})->group('migrate-plugins');

it('migrates a large freeform v5 fixture with exact layout settings notifications and submissions', function (): void {
    if (!ensureFreeformPluginReady()) {
        throw new RuntimeException('Migration suite requires the installed Freeform fixture plugin.');
    }

    $fixture = loadMigrationFixture();
    $fixtureFieldHandles = [];

    foreach (($fixture['freeformForm']?->getPages() ?? []) as $page) {
        foreach ($page->getLayout()->getAllRows() as $row) {
            foreach ($row->getAllFields() as $field) {
                $fixtureFieldHandles[] = $field->getHandle();
            }
        }
    }

    expect($fixture['formId'])->toBeGreaterThan(0);
    expect($fixtureFieldHandles)->toContain('confirmEmail', 'resumeFiles', 'secretToken', 'departments', 'contactPhone', 'detailsTable', 'details', 'website', 'extraRich');

    $migration = new MigrateFreeform5();
    $migration->formId = (int)$fixture['formId'];

    $result = $migration->run();
    $levels = array_values(array_unique(array_map(static fn($line) => $line->level, $result->lines)));
    $messages = array_map(static fn($line) => $line->message, $result->lines);

    expect($result->lines)->not->toBeEmpty()
        ->and($result->stats)->toBeArray()
        ->and($result->stats)->toHaveKeys(['formsAttempted'])
        ->and((int)$result->stats['formsAttempted'])->toBe(1)
        ->and($levels)->toContain('info')
        ->and(implode(' ', $messages))->not->toContain('Cannot assign array to property verbb\\formie\\models\\FormSettings::$submitActionMessage')
        ->and(implode(' ', $messages))->not->toContain('Cannot assign string to property verbb\\formie\\models\\Notification::$content')
        ->and(implode(' ', $messages))->toContain('All entries completed.');


    foreach ([
        'fullName',
        'contactEmail',
        'alternateName',
        'consent',
        'age',
        'source',
        'topic',
        'interests',
        'choice',
        'profileGroup',
        'scheduledAt',
        'confirmEmail',
        'resumeFiles',
        'secretToken',
        'departments',
        'contactPhone',
        'detailsTable',
        'details',
        'website',
    ] as $submissionHandle) {
        expect(implode(' ', $messages))->not->toContain("Failed to migrate “{$submissionHandle}”");
    }

    expect($result->stats['submissionsMigrated'] ?? null)->toBe(2);
    $migratedForm = Form::find()->title((string)$fixture['formTitle'])->status(null)->orderBy('elements.id DESC')->one();
    expect($migratedForm)->not->toBeNull();

    $migratedSubmissions = Submission::find()
        ->formId((int)$migratedForm?->id)
        ->orderBy('id ASC')
        ->status(null)
        ->all();

    expect($migratedSubmissions)->toHaveCount(2);
    expect(array_map(fn($page) => $page->label, $migratedForm->getPages()))->toBe(['Primary Page', 'Secondary Page'])
        ->and($migratedForm->settings->submitMethod)->toBe('ajax')
        ->and($migratedForm->settings->submitAction)->toBe('url')
        ->and($migratedForm->settings->submitActionUrl)->toBe('https://example.test/thanks');
    $pages = $migratedForm->getPages();
    expect(array_map(fn($field) => $field->handle, $pages[0]->getRows()[0]->getFields()))->toBe(['fullName', 'contactEmail', 'alternateName'])
        ->and($migratedForm->getFieldByHandle('fullName')->required)->toBeTrue()
        ->and($migratedForm->getFieldByHandle('fullName')->placeholder)->toBe('Full Name');
    foreach ([
        ['fullName' => 'Alice Example', 'contactEmail' => 'alice@example.test', 'age' => '34', 'source' => 'direct', 'details' => 'Long notes A', 'secretToken' => 'secret-a'],
        ['fullName' => 'Bob Example', 'contactEmail' => 'bob@example.test', 'age' => '44', 'source' => 'referral', 'details' => 'Long notes B', 'secretToken' => 'secret-b'],
    ] as $index => $expectedValues) {
        foreach ($expectedValues as $handle => $expected) {
            expect($migratedSubmissions[$index]->getFieldValueAsString($handle))->toBe($expected);
        }
    }
    expect($migratedSubmissions[0]->getFieldValue('profileGroup.groupNote'))->toBe('Nested note')
        ->and($migratedSubmissions[1]->getFieldValue('profileGroup.groupNote'))->toBe('Second nested note');
    $notifications = $migratedForm->getNotifications();
    expect($notifications)->toHaveCount(1)
        ->and($notifications[0]->to)->toBe('ops@example.test')
        ->and($notifications[0]->from)->toBe('no-reply@example.test')
        ->and($notifications[0]->fromName)->toBe('Fixture Sender')
        ->and($notifications[0]->replyTo)->toBe('reply@example.test')
        ->and($notifications[0]->subject)->toBe('Fixture notification');
    $body = \verbb\formie\helpers\References::parseContent((string)$notifications[0]->content, $migratedSubmissions[0]);
    expect($body)->toContain('Body for', 'alice@example.test');

})->group('migrate-plugins');
