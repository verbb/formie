<?php

declare(strict_types=1);

use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\Pipedrive;
use verbb\formie\models\IntegrationField;

it('Pipedrive EVENT_MODIFY_FIELD_MAPPING_VALUE casts set option arrays to integers', function (): void {
    $form = formie()->form(['title' => 'Pipedrive Set Casting'])
        ->checkboxesField('topics', ['options' => [['label' => 'One', 'value' => '1'], ['label' => 'Two', 'value' => '2']]])
        ->create();

    $submission = formie()->submission($form)->with(['topics' => ['1', '2']])->save();

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'topics');
    $ref = $field->reference ?? 'topics';

    $integration = new Pipedrive(['name' => 'Pipedrive', 'handle' => 'pipedrive']);
    $integrationField = new IntegrationField([
        'type' => IntegrationField::TYPE_ARRAY,
        'sourceType' => 'set',
    ]);

    $value = $integration->getMappedFieldValue(References::field($ref), $submission, $integrationField);

    expect($value)->toBeArray()
        ->and($value)->toHaveCount(2)
        ->and(is_int($value[0]))->toBeTrue()
        ->and(is_int($value[1]))->toBeTrue();
});

it('Pipedrive EVENT_MODIFY_FIELD_MAPPING_VALUE leaves non-set arrays unchanged', function (): void {
    $form = formie()->form(['title' => 'Pipedrive Non-Set'])
        ->checkboxesField('topics', ['options' => [['label' => 'One', 'value' => '1'], ['label' => 'Two', 'value' => '2']]])
        ->create();

    $submission = formie()->submission($form)->with(['topics' => ['1', '2']])->save();

    $field = ArrayHelper::firstWhere($submission->getFields(), 'handle', 'topics');
    $ref = $field->reference ?? 'topics';

    $integration = new Pipedrive(['name' => 'Pipedrive', 'handle' => 'pipedrive']);
    $integrationField = new IntegrationField([
        'type' => IntegrationField::TYPE_ARRAY,
        'sourceType' => 'varchar',
    ]);

    $value = $integration->getMappedFieldValue(References::field($ref), $submission, $integrationField);

    expect($value)->toBeArray()
        ->and($value)->toHaveCount(2)
        ->and($value[0])->toBeArray()
        ->and($value[0])->toHaveKey('value')
        ->and($value[1])->toBeArray()
        ->and($value[1])->toHaveKey('value');
});

it('Pipedrive _normalizeSetFieldValue handles arrays and comma-separated strings', function (): void {
    $method = new ReflectionMethod(Pipedrive::class, '_normalizeSetFieldValue');
    $method->setAccessible(true);

    expect($method->invoke(new Pipedrive(), [1, '2', 3]))->toBe([1, 2, 3])
        ->and($method->invoke(new Pipedrive(), '51, 52'))->toBe([51, 52])
        ->and($method->invoke(new Pipedrive(), ''))->toBe([])
        ->and($method->invoke(new Pipedrive(), null))->toBe([]);
});

it('Pipedrive _getMultiOptionFieldHandles returns set fields and label_ids', function (): void {
    $method = new ReflectionMethod(Pipedrive::class, '_getMultiOptionFieldHandles');
    $method->setAccessible(true);

    $integration = new Pipedrive(['name' => 'Pipedrive', 'handle' => 'pipedrive']);

    $handles = $method->invoke($integration, [
        new IntegrationField(['handle' => 'name', 'sourceType' => 'varchar']),
        new IntegrationField(['handle' => 'abc123', 'sourceType' => 'set']),
        new IntegrationField(['handle' => 'label_ids', 'sourceType' => 'set']),
        new IntegrationField(['handle' => 'stage_id', 'sourceType' => 'enum']),
    ]);

    expect($handles)->toBe(['abc123', 'label_ids']);
});

it('Pipedrive _mergeMultiOptionFields merges existing and mapped set field values', function (): void {
    $integration = new class(['name' => 'Pipedrive', 'handle' => 'pipedrive']) extends Pipedrive {
        public array $requests = [];

        public function request(string $method, string $uri, array $options = []): mixed
        {
            $this->requests[] = compact('method', 'uri', 'options');

            return [
                'data' => [
                    'label_ids' => [1, 2],
                    'abc123def456' => '10, 11',
                ],
            ];
        }
    };

    $method = new ReflectionMethod(Pipedrive::class, '_mergeMultiOptionFields');
    $method->setAccessible(true);

    $payload = [
        'name' => 'Jane Doe',
        'label_ids' => [3],
        'abc123def456' => [12],
    ];

    $merged = $method->invoke(
        $integration,
        $payload,
        'person',
        8432,
        ['label_ids', 'abc123def456'],
    );

    expect($merged['name'])->toBe('Jane Doe')
        ->and($merged['label_ids'])->toBe([1, 2, 3])
        ->and($merged['abc123def456'])->toBe([10, 11, 12])
        ->and($integration->requests)->toHaveCount(1)
        ->and($integration->requests[0]['method'])->toBe('GET')
        ->and($integration->requests[0]['uri'])->toBe('persons/8432');
});

it('Pipedrive _mergeMultiOptionFields deduplicates overlapping option ids', function (): void {
    $integration = new class(['name' => 'Pipedrive', 'handle' => 'pipedrive']) extends Pipedrive {
        public function request(string $method, string $uri, array $options = []): mixed
        {
            return [
                'data' => [
                    'label_ids' => [1, 2],
                ],
            ];
        }
    };

    $method = new ReflectionMethod(Pipedrive::class, '_mergeMultiOptionFields');
    $method->setAccessible(true);

    $merged = $method->invoke(
        $integration,
        ['label_ids' => [2, 3]],
        'person',
        8432,
        ['label_ids'],
    );

    expect($merged['label_ids'])->toBe([1, 2, 3]);
});

it('Pipedrive _mergeMultiOptionFields skips unmapped multi-option fields', function (): void {
    $integration = new class(['name' => 'Pipedrive', 'handle' => 'pipedrive']) extends Pipedrive {
        public function request(string $method, string $uri, array $options = []): mixed
        {
            return [
                'data' => [
                    'label_ids' => [1, 2],
                ],
            ];
        }
    };

    $method = new ReflectionMethod(Pipedrive::class, '_mergeMultiOptionFields');
    $method->setAccessible(true);

    $merged = $method->invoke(
        $integration,
        ['name' => 'Jane Doe'],
        'person',
        8432,
        ['label_ids'],
    );

    expect($merged)->toBe(['name' => 'Jane Doe']);
});
