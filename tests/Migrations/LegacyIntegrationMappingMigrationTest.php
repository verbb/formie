<?php

use craft\db\Query;
use craft\helpers\Json;
use verbb\formie\Formie;
use verbb\formie\fields\Address;
use verbb\formie\fields\Name;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\Table;
use verbb\formie\integrations\emailmarketing\CampaignMonitor;
use verbb\formie\migrations\m260926_000000_reference_slots;
use verbb\formie\migrations\m260928_010000_reference_contract;
use verbb\formie\migrations\m261103_000002_legacy_integration_mappings;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationConfig;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\Stencil;
use verbb\formie\models\StencilData;
use verbb\formie\references\ReferenceMigration;
use verbb\formie\references\ReferenceSlot;
use verbb\formie\references\ReferenceSlotKind;
use verbb\formie\services\Stencils as StencilsService;

function legacyMappingForm(): \verbb\formie\elements\Form
{
    return formie()->form()
        ->nameField('person', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])
        ->addressField('place', ['rows' => (new Address())->getSubFields()])
        ->groupField('company', ['rows' => [['fields' => [
            ['type' => SingleLineText::class, 'handle' => 'jobRole', 'label' => 'Job Role'],
            ['type' => SingleLineText::class, 'handle' => 'summary', 'label' => 'Summary'],
            ['type' => Name::class, 'handle' => 'contact', 'label' => 'Contact', 'settings' => ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()]],
        ]]]])
        ->repeaterField('items', ['rows' => [['fields' => [['type' => SingleLineText::class, 'handle' => 'item', 'label' => 'Item']]]]])
        ->create();
}

function legacyMappingValues(\verbb\formie\elements\Submission $submission, array $mapping): array
{
    $destinations = array_map(fn($handle) => new IntegrationField(['handle' => $handle, 'type' => IntegrationField::TYPE_STRING]), array_keys($mapping));
    $integration = new CampaignMonitor(['listId' => 'fixture']);
    $integration->cache['config'] = (new IntegrationConfig(['lists' => [new IntegrationCollection(['id' => 'fixture', 'fields' => $destinations])]], $integration->getIntegrationConfigKey(), time()))->toStorage();

    return $integration->getFieldMappingValues($submission, $mapping);
}

it('canonicalizes legacy sub-field paths into the values the mapping picker offers', function() {
    $form = legacyMappingForm();
    $person = $form->getFieldByHandle('person')->reference;
    $place = $form->getFieldByHandle('place')->reference;
    $company = $form->getFieldByHandle('company');
    $jobRole = $company->getFieldByHandle('jobRole')->reference;
    $summary = $company->getFieldByHandle('summary')->reference;
    $contact = $company->getFieldByHandle('contact')->reference;

    $expected = [
        '{field:person.firstName}' => '{field:' . $person . ':firstName}',
        '{field:person.lastName;transform=upper|Unknown}' => '{field:' . $person . ':lastName;transform=upper|Unknown}',
        '{field:place.address1}' => '{field:' . $place . ':address1}',
        '{field:place.city}' => '{field:' . $place . ':city}',
        '{field:company.jobRole}' => '{field:' . $jobRole . '}',
        '{field:company.summary}' => '{field:' . $summary . '}',
        '{field:company.contact.firstName}' => '{field:' . $contact . ':firstName}',
    ];

    foreach ($expected as $legacy => $canonical) {
        expect(ReferenceMigration::canonicalFieldTokens($form, $legacy))->toBe($canonical)
            ->and(ReferenceMigration::canonicalFieldTokens($form, $canonical))->toBe($canonical);
    }

    foreach (['{field:person.missing}', '{field:missing.firstName}', '{field:company.missing}', '{field:company.jobRole.missing}', '{field:items.item}', '{field:person}'] as $token) {
        expect(ReferenceMigration::canonicalFieldTokens($form, $token))->toBe($token);
    }
});

it('leaves sub-field paths alone when a single name field has no such selector', function() {
    $form = formie()->form()->nameField('person', ['useMultipleFields' => false])->create();

    expect(ReferenceMigration::canonicalFieldTokens($form, '{field:person.firstName}'))->not->toContain(':firstName');
});

it('maps Formie 3 submission sources to their current reference targets', function(string $legacy, string $canonical) {
    expect(ReferenceSlot::fromStored($legacy)->toArray())->toBe(['kind' => 'reference', 'value' => $canonical])
        ->and(ReferenceSlot::fromStored(['kind' => 'reference', 'value' => $legacy])->toArray())->toBe(['kind' => 'reference', 'value' => $canonical])
        ->and(ReferenceSlot::fromStored(['type' => 'field', 'value' => $legacy])->toArray())->toBe(['kind' => 'reference', 'value' => $canonical])
        ->and(ReferenceSlot::fromStored(['kind' => 'literal', 'value' => $legacy])->kind)->toBe(ReferenceSlotKind::Literal)
        ->and(ReferenceSlot::fromStored(['kind' => 'literal', 'value' => $legacy])->value)->toBe($legacy)
        ->and(ReferenceSlot::fromStored(['kind' => 'text', 'value' => $legacy])->value)->toBe($legacy)
        ->and(ReferenceSlot::fromStored(['kind' => 'reference', 'value' => ['nested']])->value)->toBe(['nested']);
})->with([
    ['{submission:formName}', '{form:name}'],
    ['{submission:siteName}', '{site:name}'],
    ['{submission:siteHandle}', '{site:handle}'],
    ['{submission:dateCreated}', '{submission:date}'],
]);

it('sends legacy submission sources from mappings that have not been migrated', function() {
    $form = formie()->form()->singleLineTextField('text')->create();
    $submission = formie()->submission($form)->with(['text' => 'Value'])->save();

    $values = legacyMappingValues($submission, [
        'FormName' => ['kind' => 'reference', 'value' => '{submission:formName}'],
        'SiteName' => '{submission:siteName}',
        'SiteHandle' => '{submission:siteHandle}',
        'Date' => '{submission:dateCreated}',
    ]);

    expect($values)->toBe([
        'FormName' => $form->title,
        'SiteName' => Craft::$app->getSites()->getCurrentSite()->name,
        'SiteHandle' => Craft::$app->getSites()->getCurrentSite()->handle,
        'Date' => $submission->dateCreated->format('Y-m-d H:i:s'),
    ]);
});

it('migrates persisted legacy mappings without changing what the integration receives', function(array $migrations) {
    $form = legacyMappingForm();
    $condition = ['field' => '{submission:formName}', 'condition' => '=', 'value' => 'Contact'];
    $settings = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar());
    $mapping = [
        'FormName' => '{submission:formName}',
        'First' => ['kind' => 'reference', 'value' => '{field:person.firstName}'],
        'Last' => '{field:person.lastName}',
        'Address' => ['kind' => 'reference', 'value' => '{field:place.address1}'],
        'City' => '{field:place.city}',
        'Country' => '{field:place.country}',
        'Role' => '{field:company.jobRole}',
        'Contact' => '{field:company.contact.firstName}',
    ];
    $settings['integrations'] = ['fixture' => [
        'enabled' => false,
        'listId' => 'unchanged',
        'fieldMapping' => $mapping,
        'nested' => ['contactFieldMapping' => ['Site' => ['kind' => 'reference', 'value' => '{submission:siteHandle}']]],
        'literalMapping' => ['Copy' => '{submission:formName}'],
        'conditions' => [$condition],
    ]];
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_FORMS, ['settings' => Json::encode($settings)], ['id' => $form->id])->execute();

    $submission = formie()->submission($form)->with([
        'person' => ['firstName' => 'Jane', 'lastName' => 'Doe'],
        'place' => ['address1' => '1 Main St', 'city' => 'Melbourne', 'state' => 'VIC', 'zip' => '3000', 'country' => 'AU'],
        'company' => ['jobRole' => 'Director', 'contact' => ['firstName' => 'Sam', 'lastName' => 'Lee']],
    ])->save();
    $before = legacyMappingValues($submission, $mapping);

    foreach ([1, 2] as $run) {
        foreach ($migrations as $migration) {
            expect((new $migration())->safeUp())->toBeTrue();
        }

        $stored = Json::decode((new Query())->select('settings')->from(Table::FORMIE_FORMS)->where(['id' => $form->id])->scalar())['integrations']['fixture'];
        $person = $form->getFieldByHandle('person')->reference;

        expect($stored['fieldMapping']['FormName'])->toBe(['kind' => 'reference', 'value' => '{form:name}'])
            ->and($stored['fieldMapping']['First'])->toBe(['kind' => 'reference', 'value' => '{field:' . $person . ':firstName}'])
            ->and($stored['fieldMapping']['Last'])->toBe(['kind' => 'reference', 'value' => '{field:' . $person . ':lastName}'])
            ->and($stored['nested']['contactFieldMapping']['Site'])->toBe(['kind' => 'reference', 'value' => '{site:handle}'])
            ->and($stored['literalMapping'])->toBe(['Copy' => '{submission:formName}'])
            ->and($stored['conditions'])->toBe([$condition])
            ->and($stored['listId'])->toBe('unchanged');
    }

    expect(legacyMappingValues($submission, $stored['fieldMapping']))->toBe($before)
        ->and($before)->toMatchArray(['FormName' => $form->title, 'First' => 'Jane', 'Address' => '1 Main St', 'Country' => 'AU', 'Role' => 'Director', 'Contact' => 'Sam']);
})->with([
    'original upgrade path' => [[m260926_000000_reference_slots::class, m260928_010000_reference_contract::class]],
    'repair for upgraded sites' => [[m261103_000002_legacy_integration_mappings::class]],
]);

it('migrates legacy mappings stored in stencils', function() {
    $form = formie()->form()->nameField('person', ['useMultipleFields' => true, 'rows' => (new Name())->getSubFields()])->create();
    $stencilData = new StencilData();
    $stencilData->populateFormData($form);
    $data = $stencilData->getSerializedData();
    $data['settings']['integrations'] = ['fixture' => ['enabled' => true, 'fieldMapping' => [
        'FormName' => '{submission:formName}',
        'First' => ['kind' => 'reference', 'value' => '{field:person.firstName}'],
    ]]];
    $stencil = new Stencil([
        'name' => 'Legacy mapping stencil',
        'handle' => 'legacyMappingStencil',
        'scope' => StencilsService::SCOPE_SITE,
        'data' => new StencilData($data),
    ]);
    expect(Formie::$plugin->getStencils()->saveStencil($stencil))->toBeTrue();
    Craft::$app->getDb()->createCommand()->update(Table::FORMIE_STENCILS, ['data' => Json::encode($data)], ['id' => $stencil->id])->execute();

    try {
        expect((new m261103_000002_legacy_integration_mappings())->safeUp())->toBeTrue();

        $stored = Json::decode((new Query())->select('data')->from(Table::FORMIE_STENCILS)->where(['id' => $stencil->id])->scalar());
        $mapping = $stored['settings']['integrations']['fixture']['fieldMapping'];

        expect($mapping['FormName'])->toBe(['kind' => 'reference', 'value' => '{form:name}'])
            ->and($mapping['First']['value'])->toEndWith(':firstName}')
            ->and($mapping['First']['value'])->not->toContain('person.');
    } finally {
        Formie::$plugin->getStencils()->deleteStencilById($stencil->id);
    }
});
