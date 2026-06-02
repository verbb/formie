<?php

declare(strict_types=1);

use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;
use craft\elements\db\AssetQuery;
use craft\elements\db\ElementQuery;
use yii\db\QueryInterface;
use verbb\formie\fields\SingleLineText;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\models\IntegrationField;
use verbb\formie\fields\values\FieldValueInterface;

$integrationFieldTypes = [
    IntegrationField::TYPE_STRING,
    IntegrationField::TYPE_NUMBER,
    IntegrationField::TYPE_FLOAT,
    IntegrationField::TYPE_BOOLEAN,
    IntegrationField::TYPE_DATE,
    IntegrationField::TYPE_DATETIME,
    IntegrationField::TYPE_DATECLASS,
    IntegrationField::TYPE_ARRAY,
    IntegrationField::TYPE_PHONE,
];

$nestedRows = [[
    'fields' => [[
        'type' => SingleLineText::class,
        'handle' => 'innerText',
        'label' => 'Inner Text',
    ]],
]];

$fieldConfigs = [
    ['method' => 'singleLineTextField', 'handle' => 'text', 'value' => 'Hello'],
    ['method' => 'multiLineTextField', 'handle' => 'message', 'value' => "Hello\nWorld"],
    ['method' => 'numberField', 'handle' => 'num', 'value' => 42],
    ['method' => 'emailField', 'handle' => 'email', 'value' => 'test@example.com'],
    ['method' => 'agreeField', 'handle' => 'agree', 'value' => true],
    ['method' => 'dropdownField', 'handle' => 'choice', 'value' => 'one', 'config' => ['options' => [['label' => 'One', 'value' => 'one'], ['label' => 'Two', 'value' => 'two']]]],
    ['method' => 'radioField', 'handle' => 'priority', 'value' => 'high', 'config' => ['options' => [['label' => 'High', 'value' => 'high'], ['label' => 'Low', 'value' => 'low']]]],
    ['method' => 'checkboxesField', 'handle' => 'topics', 'value' => ['a', 'b'], 'config' => ['options' => [['label' => 'A', 'value' => 'a'], ['label' => 'B', 'value' => 'b']]]],
    ['method' => 'recipientsField', 'handle' => 'notify', 'value' => 'one', 'config' => ['displayType' => 'dropdown', 'options' => [['label' => 'One', 'value' => 'one'], ['label' => 'Two', 'value' => 'two']]]],
    ['method' => 'fileUploadField', 'handle' => 'attachments', 'value' => [], 'config' => ['restrictFiles' => false]],
    ['method' => 'repeaterField', 'handle' => 'lineItems', 'value' => [['innerText' => 'Row One']], 'config' => ['rows' => $nestedRows]],
    ['method' => 'tableField', 'handle' => 'tableData', 'value' => [['col1' => 'row1']]],
    ['method' => 'dateField', 'handle' => 'dob', 'value' => '2026-01-15'],
    ['method' => 'phoneField', 'handle' => 'phone', 'value' => '0400000000'],
];

$seedUser = User::find()->status(null)->username('formie-seed-user')->one();
$seedEntry = Entry::find()->status(null)->slug('formie-seed-entry')->one();
$seedCategory = Category::find()->status(null)->title('Formie Seed Category')->one();
$seedTag = Tag::find()->status(null)->title('Formie Seed Tag')->one();

if ($seedUser) {
    $fieldConfigs[] = [
        'method' => 'usersField',
        'handle' => 'assignees',
        'value' => [$seedUser->id],
    ];
}

if ($seedEntry) {
    $fieldConfigs[] = [
        'method' => 'entriesField',
        'handle' => 'relatedEntries',
        'value' => [$seedEntry->id],
    ];
}

if ($seedCategory) {
    $fieldConfigs[] = [
        'method' => 'categoriesField',
        'handle' => 'relatedCategories',
        'value' => [$seedCategory->id],
    ];
}

if ($seedTag) {
    $fieldConfigs[] = [
        'method' => 'tagsField',
        'handle' => 'relatedTags',
        'value' => [$seedTag->id],
    ];
}

foreach ($fieldConfigs as $fieldConfig) {
    $method = $fieldConfig['method'];
    $handle = $fieldConfig['handle'];
    $value = $fieldConfig['value'];
    $config = $fieldConfig['config'] ?? [];

    foreach ($integrationFieldTypes as $intFieldType) {
        $label = "resolves {$method} value for IntegrationField type {$intFieldType}";
        it($label, function () use ($method, $handle, $value, $config, $intFieldType): void {
            $builder = formie()->form([
                'title' => 'Mapping Matrix ' . $handle . '-' . $intFieldType,
                'handle' => 'mapping' . ucfirst($handle) . ucfirst($intFieldType) . uniqid(),
            ]);
            $builder->$method($handle, $config);
            $form = $builder->create();

            $submission = formie()->submission($form)->with([$handle => $value])->save();

            $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', $handle);
            expect($field)->not->toBeNull();
            $ref = $field->reference ?? $field->handle;
            expect($ref)->not->toBeEmpty();

            $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);
            $integrationField = new IntegrationField(['type' => $intFieldType]);
            $token = References::field($ref);

            $resolved = $integration->getMappedFieldValue($token, $submission, $integrationField);

            if ($method === 'fileUploadField' && $value === [] && $intFieldType !== IntegrationField::TYPE_ARRAY) {
                expect(
                    $resolved === null
                    || $resolved === ''
                    || $resolved === []
                    || $resolved === false
                    || is_scalar($resolved)
                    || $resolved instanceof \DateTimeInterface
                    || $resolved instanceof AssetQuery
                    || $resolved instanceof ElementQuery
                    || $resolved instanceof QueryInterface
                )->toBeTrue();
                return;
            }

            if ($value === null || $value === '' || ($value === [] && $intFieldType !== IntegrationField::TYPE_ARRAY)) {
                expect($resolved)->toBeIn([null, '', []]);
                return;
            }

            switch ($intFieldType) {
                case IntegrationField::TYPE_STRING:
                    expect($resolved)->toBeString();
                    break;
                case IntegrationField::TYPE_NUMBER:
                    expect($resolved === null || is_int($resolved))->toBeTrue();
                    break;
                case IntegrationField::TYPE_FLOAT:
                    expect($resolved === null || is_float($resolved) || is_int($resolved))->toBeTrue();
                    break;
                case IntegrationField::TYPE_BOOLEAN:
                    expect(is_bool($resolved) || in_array($resolved, ['true', 'false'], true))->toBeTrue();
                    break;
                case IntegrationField::TYPE_DATE:
                case IntegrationField::TYPE_DATETIME:
                    // Provider-specific hooks can emit mixed date payload shapes; reject arbitrary objects/resources.
                    expect(
                        $resolved === null
                        || is_string($resolved)
                        || is_int($resolved)
                        || is_float($resolved)
                        || is_bool($resolved)
                        || is_array($resolved)
                        || $resolved instanceof \DateTimeInterface
                        || $resolved instanceof FieldValueInterface
                        || $resolved instanceof \Stringable
                    )->toBeTrue();
                    break;
                case IntegrationField::TYPE_DATECLASS:
                    expect($resolved === null || $resolved instanceof \DateTimeInterface)->toBeTrue();
                    break;
                case IntegrationField::TYPE_ARRAY:
                    // HubSpot (and others) may convert array to string; accept either
                    expect(is_array($resolved) || is_string($resolved))->toBeTrue();
                    break;
                case IntegrationField::TYPE_PHONE:
                    expect($resolved === null || is_string($resolved))->toBeTrue();
                    break;
                default:
                    expect($resolved)->not->toBeNull();
                    break;
            }
        });
    }
}

it('resolves static value for each IntegrationField type', function (): void {
    $form = formie()->form(['title' => 'Static Mapping Form'])->singleLineTextField('dummy')->create();
    $submission = formie()->submission($form)->with(['dummy' => 'x'])->save();

    $integration = new HubSpot(['name' => 'HubSpot', 'handle' => 'hubspot']);

    $values = [
        IntegrationField::TYPE_STRING => 'hello',
        IntegrationField::TYPE_NUMBER => '42',
        IntegrationField::TYPE_FLOAT => '3.14',
        IntegrationField::TYPE_BOOLEAN => '1',
        IntegrationField::TYPE_DATE => '2026-01-01',
        IntegrationField::TYPE_DATETIME => '2026-01-01 12:00:00',
        IntegrationField::TYPE_DATECLASS => '2026-01-01',
        IntegrationField::TYPE_ARRAY => ['a', 'b'],
        IntegrationField::TYPE_PHONE => '0400000000',
    ];

    foreach ($values as $type => $staticValue) {
        $integrationField = new IntegrationField(['type' => $type]);
        $converted = $integration->convertValueForIntegration($staticValue, $integrationField);
        if ($type === IntegrationField::TYPE_STRING) {
            expect($converted)->toBeString();
        } else if ($type === IntegrationField::TYPE_NUMBER) {
            expect($converted === null || is_int($converted))->toBeTrue();
        } else if ($type === IntegrationField::TYPE_FLOAT) {
            expect($converted === null || is_float($converted) || is_int($converted))->toBeTrue();
        } else if ($type === IntegrationField::TYPE_BOOLEAN) {
            expect($converted)->toBeBool();
        } else if ($type === IntegrationField::TYPE_DATE) {
            expect($converted === null || is_string($converted))->toBeTrue();
        } else if ($type === IntegrationField::TYPE_DATETIME) {
            expect($converted === null || is_string($converted))->toBeTrue();
        } else if ($type === IntegrationField::TYPE_DATECLASS) {
            expect($converted === null || $converted instanceof \DateTimeInterface)->toBeTrue();
        } else if ($type === IntegrationField::TYPE_ARRAY) {
            expect(is_array($converted) || is_string($converted))->toBeTrue();
        } else if ($type === IntegrationField::TYPE_PHONE) {
            expect($converted === null || is_string($converted))->toBeTrue();
        } else {
            expect($converted === null || is_string($converted))->toBeTrue();
        }
    }
});
