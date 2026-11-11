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
use verbb\formie\fields\Forms as FormsField;

beforeEach(function(): void {
    $this->tokenFixtures = [];
});

afterEach(function(): void {
    $gql = Craft::$app->getGql();
    $entries = Craft::$app->getEntries();

    foreach ($this->tokenFixtures as [$token, $schema, $section, $type, $field]) {
        $gql->deleteTokenById($token->id);
        $gql->deleteSchema($schema);
        $entries->deleteSection($section);
        $entries->deleteEntryType($type);
        Craft::$app->getFields()->deleteField($field);
    }

    $gql->flushCaches();
});

it('returns token-readable forms when the API request is a control panel request', function(bool $isCpRequest): void {
    $allowed = formie()->form(['title' => 'Token Readable Form'])->singleLineTextField('message')->create();
    $denied = formie()->form(['title' => 'Token Unreadable Form'])->singleLineTextField('message')->create();
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
    $entry->setFieldValue($field->handle, [$allowed->id]);
    expect(Craft::$app->getElements()->saveElement($entry))->toBeTrue();

    $gql = Craft::$app->getGql();
    $schema = new GqlSchema(['name' => 'Token Schema ' . $key, 'scope' => [
        'formieForms.' . $allowed->uid . ':read',
        'sections.' . $section->uid . ':read',
        ...array_map(fn($site) => 'sites.' . $site->uid . ':read', Craft::$app->getSites()->getAllSites()),
    ]]);
    expect($gql->saveSchema($schema))->toBeTrue();

    $token = new GqlToken(['name' => 'Token ' . $key, 'accessToken' => StringHelper::randomString(32), 'enabled' => true, 'schemaId' => $schema->id]);
    expect($gql->saveToken($token))->toBeTrue();
    $this->tokenFixtures[] = [$token, $schema, $section, $type, $field];
    $gql->flushCaches();

    $query = sprintf('{
        formieForms { id }
        formieFormCount
        entries(id: %d) { ... on %s_Entry { %s { id } } }
    }', $entry->id, $type->handle, $field->handle);

    $result = WebRequestTestHelper::withWebRequestContext(function($request, $response) use ($isCpRequest, $query) {
        $request->setIsCpRequest($isCpRequest);
        Craft::$app->getUser()->setIdentity(null);

        return Craft::$app->runAction('graphql/api')->data;
    }, [
        'method' => 'POST',
        'requestUri' => $isCpRequest ? '/admin/actions/graphql/api' : '/api',
        'headers' => ['Authorization' => 'Bearer ' . $token->accessToken],
        'bodyParams' => ['query' => $query],
    ]);

    expect($result['errors'] ?? [])->toBe([]);
    expect(array_map('intval', array_column($result['data']['formieForms'], 'id')))->toBe([$allowed->id]);
    expect($result['data']['formieFormCount'])->toBe(1);
    expect(array_map('intval', array_column($result['data']['entries'][0][$field->handle], 'id')))->toBe([$allowed->id]);
})->with([
    'control panel request' => [true],
    'site request' => [false],
]);
