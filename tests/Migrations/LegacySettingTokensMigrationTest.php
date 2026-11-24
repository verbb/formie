<?php

declare(strict_types=1);

use craft\db\Query;
use craft\errors\InvalidFsException;
use craft\helpers\Json;
use Tests\Support\UploadTestHelper;
use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\elements\Submission;
use verbb\formie\fields\Name;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\ImportExportHelper;
use verbb\formie\helpers\References;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\payments\Mollie;
use verbb\formie\migrations\m261115_020000_legacy_setting_tokens;
use verbb\formie\models\StencilData;
use verbb\formie\references\ReferenceMigration;

function legacySettingsForm(array $extra = []): \verbb\formie\elements\Form
{
    $builder = formie()->form()
        ->singleLineTextField('email')
        ->singleLineTextField('company')
        ->numberField('number')
        ->nameField('nameMulti', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])
        ->groupField('group', ['rows' => [['fields' => [['type' => SingleLineText::class, 'handle' => 'name1', 'label' => 'Name 1']]]]]);

    foreach ($extra as $call) {
        $builder = $call($builder);
    }

    return $builder->create();
}

function legacySettingsSubmission(\verbb\formie\elements\Form $form): Submission
{
    return formie()->submission($form)->with([
        'email' => 'joshua@example.test',
        'company' => 'S Group',
        'number' => 11,
        'nameMulti' => ['firstName' => 'Josh', 'lastName' => 'Crawford'],
        'group' => ['name1' => 'Nested'],
    ])->save();
}

function storeLegacySetting(string $table, int $id, array $values): void
{
    $settings = Json::decode((string)(new Query())->select('settings')->from($table)->where(['id' => $id])->scalar()) ?: [];

    // Before upgrading, placements only held Required, and these settings were stored on the definition.
    if ($table === Table::FORMIE_FIELDS) {
        foreach ((new Query())->select(['id', 'settings'])->from(Table::FORMIE_FORM_FIELDS)->where(['fieldId' => $id])->all() as $row) {
            $instance = Json::decode((string)$row['settings']) ?: [];
            $settings += array_intersect_key($instance, $values);
            Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORM_FIELDS, ['settings' => Json::encode(array_diff_key($instance, $values))], ['id' => $row['id']])->execute();
        }
    }

    Craft::$app->getDb()->createCommand()->update($table, ['settings' => Json::encode(array_replace_recursive($settings, $values))], ['id' => $id])->execute();
    Craft::$app->getElements()->invalidateAllCaches();
}

function legacyStoredSettings(string $table, int $id): array
{
    return Json::decode((string)(new Query())->select('settings')->from($table)->where(['id' => $id])->scalar());
}

function freshLegacyForm(int $id): \verbb\formie\elements\Form
{
    Craft::$app->getElements()->invalidateAllCaches();
    Formie::$plugin->getFields()->resetFieldRegistryCache();

    return \verbb\formie\elements\Form::find()->id($id)->status(null)->one();
}

function reloadLegacySubmission(Submission $submission): Submission
{
    Craft::$app->getElements()->invalidateAllCaches();

    return Submission::find()->id($submission->id)->status(null)->one();
}

it('resolves stored submission title formats from Formie 3 to the same values', function() {
    $form = legacySettingsForm();
    $submission = legacySettingsSubmission($form);
    $email = '{field:' . $form->getFieldByHandle('email')->reference . '}';
    $name = '{field:' . $form->getFieldByHandle('nameMulti')->reference;

    // Stored on forms 2670, 11466, 17648 and 11362 of the Formie 3 site.
    $stored = '{email} {nameMulti.firstName} {field.nameMulti.lastName} {field:email} {field.email} {field:nameMulti.__toString} {group[name1]} {timestamp} {formName} {unknown}';
    storeLegacySetting(Table::FORMIE_FORMS, $form->id, ['submissionTitleFormat' => $stored]);

    $before = References::parseContent($stored, $submission);
    expect($before)->not->toContain('joshua@example.test joshua@example.test joshua@example.test');

    foreach ([1, 2] as $run) {
        expect((new m261115_020000_legacy_setting_tokens())->safeUp())->toBeTrue();

        expect(legacyStoredSettings(Table::FORMIE_FORMS, $form->id)['submissionTitleFormat'])
            ->toBe($email . ' ' . $name . ':firstName} ' . $name . ':lastName} ' . $email . ' ' . $email . ' ' . $name . '} {field:' . $form->getFieldByHandle('group')->getFieldByHandle('name1')->reference . '} {timestamp} {formName} {unknown}');
    }

    $migrated = legacyStoredSettings(Table::FORMIE_FORMS, $form->id)['submissionTitleFormat'];

    expect(References::parseContent(str_replace([' {timestamp} {formName} {unknown}'], '', $migrated), reloadLegacySubmission($submission)))
        ->toBe('joshua@example.test Josh Crawford joshua@example.test joshua@example.test Josh Crawford Nested');
});

it('uploads to the same folder when a Formie 3 subpath uses a field handle or submission attribute', function() {
    UploadTestHelper::ensureUploadVolume();
    $form = legacySettingsForm([fn($builder) => $builder->fileUploadField('file', ['restrictFiles' => false, 'uploadLocationSubpath' => 'seed', 'filenameFormat' => 'seed'])]);
    $submission = legacySettingsSubmission($form);
    $field = $form->getFieldByHandle('file');

    // Stored on forms 11230 and 863 of the Formie 3 site.
    storeLegacySetting(Table::FORMIE_FIELDS, $field->definitionId, ['uploadLocationSubpath' => 'legacy-{company}/{id}', 'filenameFormat' => '{field.company}-{submissionId}']);

    // The handle can't be read, so the subpath is reported as invalid instead of placing the upload.
    expect(fn() => freshLegacyForm($form->id)->getFieldByHandle('file')->getUploadFolderForSubmission(reloadLegacySubmission($submission)))->toThrow(InvalidFsException::class);

    foreach ([1, 2] as $run) {
        expect((new m261115_020000_legacy_setting_tokens())->safeUp())->toBeTrue();
    }

    Craft::$app->getElements()->invalidateAllCaches();
    $saved = legacyStoredSettings(Table::FORMIE_FIELDS, $field->definitionId);
    $reference = $form->getFieldByHandle('company')->reference;

    expect($saved['uploadLocationSubpath'])->toBe('legacy-{field:' . $reference . '}/{submission:id}')
        ->and($saved['filenameFormat'])->toBe('{field:' . $reference . '}-{submissionId}');

    $folder = freshLegacyForm($form->id)->getFieldByHandle('file')->getUploadFolderForSubmission(reloadLegacySubmission($submission));

    expect($folder->path)->toBe('legacy-S-Group/' . $submission->id . '/');
});

it('takes payment amounts and billing details from the fields Formie 3 stored by handle', function() {
    $integration = new Mollie(['name' => 'Legacy tokens', 'handle' => 'legacyTokens' . bin2hex(random_bytes(3))]);
    expect(Formie::$plugin->getIntegrations()->saveIntegration($integration, false))->toBeTrue();

    $form = legacySettingsForm([fn($builder) => $builder->paymentField('payment', [
        'paymentIntegration' => $integration->handle,
        'paymentIntegrationType' => get_class($integration),
        'providerSettings' => [$integration->handle => ['amountType' => 'dynamic', 'currencyType' => 'fixed', 'currencyFixed' => 'AUD']],
    ])]);
    $submission = legacySettingsSubmission($form);
    $field = $form->getFieldByHandle('payment');

    // Stored on forms 11211, 17384 and the group billing forms of the Formie 3 site.
    storeLegacySetting(Table::FORMIE_FIELDS, $field->definitionId, ['providerSettings' => [$integration->handle => [
        'amountVariable' => '{number}',
        'paymentDescription' => 'From {field.email} {nameMulti.firstName}',
        'paymentReceiptEmail' => '{field.email}',
        'metadata' => [['label' => 'Company', 'value' => '{company}']],
        'billingDetails' => ['billingName' => '{field:nameMulti.__toString}', 'billingEmail' => '{email}', 'billingAddress' => '{group[name1]}'],
    ]]]);

    $payment = static function() use ($form, $submission) {
        Craft::$app->getElements()->invalidateAllCaches();
        $reloaded = freshLegacyForm($form->id)->getFieldByHandle('payment');

        return [$reloaded->getPaymentIntegration(), reloadLegacySubmission($submission)];
    };

    [$provider, $saved] = $payment();
    expect(fn() => $provider->getPaymentAmount($saved))->toThrow(\verbb\formie\references\ReferenceException::class);

    foreach ([1, 2] as $run) {
        expect((new m261115_020000_legacy_setting_tokens())->safeUp())->toBeTrue();
    }

    $stored = legacyStoredSettings(Table::FORMIE_FIELDS, $field->definitionId)['providerSettings'][$integration->handle];
    $reference = static fn(string $handle) => '{field:' . $form->getFieldByHandle($handle)->reference . '}';

    expect($stored['amountVariable'])->toBe($reference('number'))
        ->and($stored['paymentReceiptEmail'])->toBe($reference('email'))
        ->and($stored['metadata'][0]['value'])->toBe($reference('company'))
        ->and($stored['billingDetails']['billingName'])->toBe($reference('nameMulti'))
        ->and($stored['billingDetails']['billingEmail'])->toBe($reference('email'))
        ->and($stored['billingDetails']['billingAddress'])->toBe('{field:' . $form->getFieldByHandle('group')->getFieldByHandle('name1')->reference . '}');

    [$provider, $saved] = $payment();
    expect((float)$provider->getPaymentAmount($saved))->toBe(11.0)
        ->and(References::parseContent($stored['paymentDescription'], $saved))->toBe('From joshua@example.test Josh');
});

it('resolves stored success messages and integration messages from Formie 3', function() {
    $form = legacySettingsForm();
    $submission = legacySettingsSubmission($form);
    $variable = static fn(string $value) => ['type' => 'variableTag', 'attrs' => ['label' => 'Email', 'value' => $value]];
    $document = Json::encode([['type' => 'paragraph', 'content' => [$variable('{field.nameMulti.firstName}'), ['type' => 'text', 'text' => ' {email}']]]]);

    // Stored on forms 11230 and 15471 of the Formie 3 site.
    storeLegacySetting(Table::FORMIE_FORMS, $form->id, ['successMessage' => $document, 'integrations' => ['slack' => ['enabled' => true, 'message' => $document, 'webhook' => 'https://example.test/?n={number}']]]);

    foreach ([1, 2] as $run) {
        expect((new m261115_020000_legacy_setting_tokens())->safeUp())->toBeTrue();
    }

    $settings = legacyStoredSettings(Table::FORMIE_FORMS, $form->id);
    $name = $form->getFieldByHandle('nameMulti')->reference;
    $email = $form->getFieldByHandle('email')->reference;
    $expected = Json::encode([['type' => 'paragraph', 'content' => [$variable('{field:' . $name . ':firstName}'), ['type' => 'text', 'text' => ' {field:' . $email . '}']]]]);

    expect($settings['successMessage'])->toBe($expected)
        ->and($settings['integrations']['slack']['message'])->toBe($expected)
        ->and($settings['integrations']['slack']['webhook'])->toBe('https://example.test/?n={field:' . $form->getFieldByHandle('number')->reference . '}');
});

it('resolves a Webhook URL from Formie 3 that names a field', function() {
    $form = legacySettingsForm();

    // Formie 3 stored this as `webhook`, and Formie 4 moves it to `url` (form 3821 of the Formie 3 site).
    storeLegacySetting(Table::FORMIE_FORMS, $form->id, ['integrations' => ['webhook' => ['enabled' => '1', 'url' => 'https://example.test/jobs/{number}/apply']]]);

    foreach ([1, 2] as $run) {
        expect((new m261115_020000_legacy_setting_tokens())->safeUp())->toBeTrue();
    }

    $url = legacyStoredSettings(Table::FORMIE_FORMS, $form->id)['integrations']['webhook']['url'];

    expect($url)->toBe('https://example.test/jobs/{field:' . $form->getFieldByHandle('number')->reference . '}/apply')
        ->and(References::parseUrl($url, legacySettingsSubmission(freshLegacyForm($form->id))))->toBe('https://example.test/jobs/11/apply');
});

it('leaves settings Formie 3 never parsed, and hidden defaults on other fields, as they were', function() {
    $form = legacySettingsForm([fn($builder) => $builder->hiddenField('hidden', ['valueSource' => 'custom', 'defaultValue' => 'seed'])->singleLineTextField('text', ['defaultValue' => '{email}', 'placeholder' => '{email}', 'instructions' => 'Use {email} here'])]);
    $hidden = $form->getFieldByHandle('hidden');
    $text = $form->getFieldByHandle('text');

    storeLegacySetting(Table::FORMIE_FIELDS, $hidden->definitionId, ['defaultValue' => '{email} {userEmail} {nope}']);
    storeLegacySetting(Table::FORMIE_FORMS, $form->id, ['errorMessage' => 'Check {email}']);

    $before = legacyStoredSettings(Table::FORMIE_FIELDS, $text->definitionId);
    $formBefore = legacyStoredSettings(Table::FORMIE_FORMS, $form->id);

    expect((new m261115_020000_legacy_setting_tokens())->safeUp())->toBeTrue();

    expect(legacyStoredSettings(Table::FORMIE_FIELDS, $text->definitionId))->toBe($before)
        ->and(legacyStoredSettings(Table::FORMIE_FORMS, $form->id))->toBe($formBefore)
        ->and(legacyStoredSettings(Table::FORMIE_FIELDS, $hidden->definitionId)['defaultValue'])->toBe('{field:' . $form->getFieldByHandle('email')->reference . '} {userEmail} {nope}');
});

it('converts stored tokens when importing an export from an older version', function() {
    UploadTestHelper::ensureUploadVolume();
    $source = legacySettingsForm([fn($builder) => $builder->fileUploadField('file', ['restrictFiles' => false, 'uploadLocationSubpath' => 'seed', 'filenameFormat' => 'seed'])]);
    $export = ImportExportHelper::generateFormExport($source);
    $export['handle'] = 'legacyImport' . bin2hex(random_bytes(3));
    $export['title'] = 'Legacy import';

    // These tokens came from the real exports of forms 2670 and 4994 on the Formie 3 site.
    $export['settings'] = Json::encode(['submissionTitleFormat' => '{email} {nameMulti.firstName}'] + Json::decodeIfJson($export['settings']));
    array_walk_recursive($export['pages'], function(mixed &$value, string|int $key) {
        if ($key === 'uploadLocationSubpath') {
            $value = '{company}/{id}';
        }
    });

    $form = ImportExportHelper::createFormFromImport($export);
    expect(Craft::$app->getElements()->saveElement($form, false))->toBeTrue();

    $saved = freshLegacyForm($form->id);
    $reference = static fn(string $handle) => '{field:' . $saved->getFieldByHandle($handle)->reference;

    expect($saved->settings->submissionTitleFormat)->toBe($reference('email') . '} ' . $reference('nameMulti') . ':firstName}')
        ->and($saved->getFieldByHandle('file')->uploadLocationSubpath)->toBe($reference('company') . '}/{submission:id}');
});

it('converts stored tokens when a stencil is applied to a new form', function() {
    $source = legacySettingsForm();
    $data = new StencilData();
    $data->pages = StencilData::getSerializedLayout($source->getFormLayout());
    $data->settings->submissionTitleFormat = '{email} {field.nameMulti.firstName}';

    $form = new \verbb\formie\elements\Form();
    $data->populateToForm($form, true);
    $reference = static fn(string $handle) => '{field:' . $form->getFieldByHandle($handle)->reference;

    expect($form->settings->submissionTitleFormat)->toBe($reference('email') . '} ' . $reference('nameMulti') . ':firstName}');
});

it('stores a path both forms can read when a synced field is shared between forms', function() {
    UploadTestHelper::ensureUploadVolume();
    $first = legacySettingsForm([fn($builder) => $builder->fileUploadField('file', ['restrictFiles' => false, 'uploadLocationSubpath' => 'seed'])]);
    $second = legacySettingsForm([fn($builder) => $builder->fileUploadField('other', ['restrictFiles' => false, 'uploadLocationSubpath' => 'seed'])]);
    $synced = $first->getFieldByHandle('file');
    storeLegacySetting(Table::FORMIE_FIELDS, $synced->definitionId, ['uploadLocationSubpath' => '{company}/{nameMulti.firstName}']);
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORM_FIELDS, ['fieldId' => $synced->definitionId], ['id' => $second->getFieldByHandle('other')->id])->execute();

    foreach ([1, 2] as $run) {
        expect((new m261115_020000_legacy_setting_tokens())->safeUp())->toBeTrue();
    }

    $stored = legacyStoredSettings(Table::FORMIE_FIELDS, $synced->definitionId)['uploadLocationSubpath'];

    expect($stored)->toBe('{field:company}/{field:nameMulti:firstName}');

    foreach ([$first, $second] as $form) {
        $submission = legacySettingsSubmission(freshLegacyForm($form->id));

        expect(References::parseContent($stored, $submission))->toBe('S Group/Josh');
    }
});

it('keeps readable paths when a definition is shared between forms', function() {
    $first = legacySettingsForm();
    $fields = $first->getFields();

    expect(ReferenceMigration::legacyTextTokens($fields, '{email} {nameMulti.firstName} {group[name1]} {field:nameMulti.__toString}', false))
        ->toBe('{field:email} {field:nameMulti:firstName} {field:group.name1} {field:nameMulti}');
});

it('does not rewrite current tokens or text that names nothing', function(string $value) {
    $form = legacySettingsForm();
    $reference = $form->getFieldByHandle('email')->reference;
    $value = str_replace('REF', $reference, $value);

    expect(ReferenceMigration::legacyTextTokens($form->getFields(), $value))->toBe($value);
})->with([
    ['{field:REF}'],
    ['{field:REF;transform=upper}'],
    ['{field:REF|fallback}'],
    ['{form:name} {submission:id} {site:name} {user:email}'],
    ['{timestamp} {formName} {submissionId} {siteName} {userEmail} {dateInt}'],
    ['{{ form.handle }} {% if x %}'],
    ['{(Anfrage)} { spaced } {"a":1} {missing} {missing.sub}'],
    ['{email|upper}'],
]);

it('converts simple Twig filters in Formie 3 upload paths', function(string $subpath, callable $expected, callable $folder) {
    UploadTestHelper::ensureUploadVolume();
    $form = legacySettingsForm([fn($builder) => $builder->fileUploadField('file', ['restrictFiles' => false, 'uploadLocationSubpath' => 'seed', 'filenameFormat' => 'seed'])]);
    $form->title = 'Cartoon Next Project';
    Craft::$app->getElements()->saveElement($form);
    $submission = legacySettingsSubmission($form);
    $field = $form->getFieldByHandle('file');
    storeLegacySetting(Table::FORMIE_FIELDS, $field->definitionId, ['uploadLocationSubpath' => $subpath]);

    foreach ([1, 2] as $run) {
        expect((new m261115_020000_legacy_setting_tokens())->safeUp())->toBeTrue();
    }

    Craft::$app->getElements()->invalidateAllCaches();
    $stored = legacyStoredSettings(Table::FORMIE_FIELDS, $field->definitionId)['uploadLocationSubpath'];
    $submission = reloadLegacySubmission($submission);

    expect($stored)->toBe($expected($form))
        ->and(freshLegacyForm($form->id)->getFieldByHandle('file')->getUploadFolderForSubmission($submission)->path)->toBe($folder($submission));
})->with([
    'date filter on the unset post date' => [
        '{postDate|date("Ymd")}/{id}',
        fn() => '{submission:date;transform=format;preset=custom;pattern=Ymd}/{submission:id}',
        fn(Submission $submission) => $submission->dateCreated->format('Ymd') . '/' . $submission->id . '/',
    ],
    'row owner in a repeater' => [
        '{postDate|date("Ymd")}/{owner.id}',
        fn() => '{submission:date;transform=format;preset=custom;pattern=Ymd}/{submission:id}',
        fn(Submission $submission) => $submission->dateCreated->format('Ymd') . '/' . $submission->id . '/',
    ],
    'camel filter on the form name' => [
        'forms/{formName|camel}/{id}',
        fn() => 'forms/{form:name;transform=camel}/{submission:id}',
        fn(Submission $submission) => 'forms/cartoonNextProject/' . $submission->id . '/',
    ],
    'filter on a field handle' => [
        '{company|kebab}',
        fn(\verbb\formie\elements\Form $form) => '{field:' . $form->getFieldByHandle('company')->reference . ';transform=kebab}',
        fn() => 's-group/',
    ],
]);

it('leaves Twig in upload paths it cannot convert for the upload to fall back from', function() {
    UploadTestHelper::ensureUploadVolume();
    $form = legacySettingsForm([fn($builder) => $builder->fileUploadField('file', ['restrictFiles' => false, 'uploadLocationSubpath' => 'seed'])]);
    $field = $form->getFieldByHandle('file');
    storeLegacySetting(Table::FORMIE_FIELDS, $field->definitionId, ['uploadLocationSubpath' => '{postDate|slice(0, 4)}/{unknownThing|camel}']);

    expect((new m261115_020000_legacy_setting_tokens())->safeUp())->toBeTrue();

    expect(legacyStoredSettings(Table::FORMIE_FIELDS, $field->definitionId)['uploadLocationSubpath'])->toBe('{postDate|slice(0, 4)}/{unknownThing|camel}');
});
