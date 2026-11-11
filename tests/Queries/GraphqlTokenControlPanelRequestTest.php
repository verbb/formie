<?php

declare(strict_types=1);

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\GqlSchema;
use craft\models\GqlToken;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use Tests\Support\WebRequestTestHelper;
use verbb\formie\elements\Form;
use verbb\formie\fields\Forms as FormsField;

beforeEach(function(): void {
    $this->tokenFixtures = [];
});

afterEach(function(): void {
    $gql = Craft::$app->getGql();
    $entries = Craft::$app->getEntries();

    foreach ($this->tokenFixtures as $fixture) {
        $gql->deleteTokenById($fixture['token']->id);
        $gql->deleteSchema($fixture['schema']);
        $entries->deleteSection($fixture['section']);
        $entries->deleteEntryType($fixture['type']);
        Craft::$app->getFields()->deleteField($fixture['field']);
    }

    $gql->flushCaches();
});

/**
 * Creates an entry relating the given forms through a Forms field, and a token whose schema can read the entry's
 * section and the given form scopes.
 */
function graphqlFormsTokenFixture(array $relatedForms, array $formScopes): array
{
    $key = StringHelper::randomString(8);

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
    $entry->setFieldValue($field->handle, array_map(fn(Form $form) => $form->id, $relatedForms));
    expect(Craft::$app->getElements()->saveElement($entry))->toBeTrue();

    $gql = Craft::$app->getGql();
    $schema = new GqlSchema(['name' => 'Token Schema ' . $key, 'scope' => [
        ...$formScopes,
        'sections.' . $section->uid . ':read',
        ...array_map(fn($site) => 'sites.' . $site->uid . ':read', Craft::$app->getSites()->getAllSites()),
    ]]);
    expect($gql->saveSchema($schema))->toBeTrue();

    $token = new GqlToken(['name' => 'Token ' . $key, 'accessToken' => StringHelper::randomString(32), 'enabled' => true, 'schemaId' => $schema->id]);
    expect($gql->saveToken($token))->toBeTrue();
    $gql->flushCaches();

    return compact('entry', 'field', 'type', 'section', 'schema', 'token');
}

/**
 * Runs a query through Craft's GraphQL API action as an anonymous token request.
 */
function graphqlTokenRequest(array $fixture, string $query, bool $isCpRequest = false): array
{
    return WebRequestTestHelper::withWebRequestContext(function($request) use ($isCpRequest) {
        $request->setIsCpRequest($isCpRequest);
        Craft::$app->getUser()->setIdentity(null);

        return Craft::$app->runAction('graphql/api')->data;
    }, [
        'method' => 'POST',
        'requestUri' => $isCpRequest ? '/admin/actions/graphql/api' : '/api',
        'headers' => ['Authorization' => 'Bearer ' . $fixture['token']->accessToken],
        'bodyParams' => ['query' => $query],
    ]);
}

function graphqlRelatedFormIds(array $result, array $fixture): array
{
    return array_map('intval', array_column($result['data']['entries'][0][$fixture['field']->handle], 'id'));
}

it('returns token-readable forms when the API request is a control panel request', function(bool $isCpRequest): void {
    $allowed = formie()->form(['title' => 'Token Readable Form'])->singleLineTextField('message')->create();
    formie()->form(['title' => 'Token Unreadable Form'])->singleLineTextField('message')->create();
    $this->tokenFixtures[] = $fixture = graphqlFormsTokenFixture([$allowed], ['formieForms.' . $allowed->uid . ':read']);

    $result = graphqlTokenRequest($fixture, sprintf('{
        formieForms { id }
        formieFormCount
        entries(id: %d) { ... on %s_Entry { %s { id } } }
    }', $fixture['entry']->id, $fixture['type']->handle, $fixture['field']->handle), $isCpRequest);

    expect($result['errors'] ?? [])->toBe([]);
    expect(array_map('intval', array_column($result['data']['formieForms'], 'id')))->toBe([$allowed->id]);
    expect($result['data']['formieFormCount'])->toBe(1);
    expect(graphqlRelatedFormIds($result, $fixture))->toBe([$allowed->id]);
})->with([
    'control panel request' => [true],
    'site request' => [false],
]);

it('limits eager-loaded Forms field values to the forms a token can read', function(string $arguments): void {
    $allowed = formie()->form(['title' => 'Related Readable Form'])->singleLineTextField('message')->create();
    $denied = formie()->form(['title' => 'Related Unreadable Form'])->singleLineTextField('message')->create();
    $this->tokenFixtures[] = $fixture = graphqlFormsTokenFixture([$denied, $allowed], ['formieForms.' . $allowed->uid . ':read']);

    $result = graphqlTokenRequest($fixture, sprintf(
        '{ entries(id: %d) { ... on %s_Entry { %s%s { id } } } }',
        $fixture['entry']->id,
        $fixture['type']->handle,
        $fixture['field']->handle,
        str_replace(['{allowed}', '{denied}'], [$allowed->id, $denied->id], $arguments),
    ));

    expect($result['errors'] ?? [])->toBe([]);
    expect(graphqlRelatedFormIds($result, $fixture))->toBe($arguments === '(id: {denied})' ? [] : [$allowed->id]);
})->with([
    'no arguments' => [''],
    'requested readable form' => ['(id: {allowed})'],
    'requested unreadable form' => ['(id: {denied})'],
]);

it('returns every related form to tokens that can read all forms', function(): void {
    $first = formie()->form(['title' => 'First Related Form'])->singleLineTextField('message')->create();
    $second = formie()->form(['title' => 'Second Related Form'])->singleLineTextField('message')->create();
    $this->tokenFixtures[] = $fixture = graphqlFormsTokenFixture([$first, $second], ['formieForms.all:read']);

    $result = graphqlTokenRequest($fixture, sprintf(
        '{ entries(id: %d) { ... on %s_Entry { %s { id } } } }',
        $fixture['entry']->id,
        $fixture['type']->handle,
        $fixture['field']->handle,
    ));

    expect($result['errors'] ?? [])->toBe([]);
    expect(graphqlRelatedFormIds($result, $fixture))->toBe([$first->id, $second->id]);
});
