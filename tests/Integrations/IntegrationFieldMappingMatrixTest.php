<?php

declare(strict_types=1);

use verbb\formie\helpers\References;
use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\integrations\crm\Salesforce;
use verbb\formie\integrations\crm\Pardot;
use verbb\formie\models\IntegrationField;

// Each case specifies a useful destination contract. Arbitrary cross-type casts
// belong to smoke coverage, not a union broad enough to accept a lost value.
it('preserves mapped values in both direct and outgoing provider payloads', function (
    string $provider, string $method, mixed $input, string $type, mixed $expected, array $payload
): void {
    $form = formie()->form()->$method('answer')->create();
    $saved = formie()->submission($form)->with(['answer' => $input])->save();
    $submission = \verbb\formie\elements\Submission::find()->id($saved->id)->status(null)->one();
    $field = $form->getFieldByHandle('answer');
    $integration = new $provider(['name' => 'Mapping contract', 'handle' => 'mappingContract']);
    $destination = new IntegrationField(['handle' => 'result', 'type' => $type]);
    $reference = References::field($field->reference ?? $field->handle);
    expect($integration->getMappedFieldValue($reference, $submission, $destination))->toBe($expected)
        ->and($integration->getFieldMappingValues($submission, ['result' => $reference], [$destination]))
        ->toBe($payload);
})->with([HubSpot::class, Salesforce::class, Pardot::class])->with([
    'text' => ['singleLineTextField', 'Keep this value', IntegrationField::TYPE_STRING, 'Keep this value', ['result' => 'Keep this value']],
    'email' => ['emailField', 'person@example.test', IntegrationField::TYPE_STRING, 'person@example.test', ['result' => 'person@example.test']],
    'integer' => ['numberField', '42', IntegrationField::TYPE_NUMBER, 42, ['result' => 42]],
    'zero' => ['numberField', '0', IntegrationField::TYPE_NUMBER, 0, ['result' => 0]],
    'decimal' => ['numberField', '42.5', IntegrationField::TYPE_FLOAT, 42.5, ['result' => 42.5]],
    'empty text' => ['singleLineTextField', '', IntegrationField::TYPE_STRING, '', []],
]);

it('converts static integration values without erasing zero false or valid dates', function (string $type, mixed $input, mixed $expected): void {
    $converted = HubSpot::convertValueForIntegration($input, new IntegrationField(['type' => $type]));
    if ($expected instanceof DateTimeInterface) {
        expect($converted)->toBeInstanceOf(DateTimeInterface::class)
            ->and($converted->format('c'))->toBe($expected->format('c'));
    } else {
        expect($converted)->toBe($expected);
    }
})->with([
    'string' => [IntegrationField::TYPE_STRING, 'hello', 'hello'],
    'integer' => [IntegrationField::TYPE_NUMBER, '42', 42],
    'zero' => [IntegrationField::TYPE_NUMBER, '0', 0],
    'decimal' => [IntegrationField::TYPE_FLOAT, '3.14', 3.14],
    'true' => [IntegrationField::TYPE_BOOLEAN, '1', true],
    'false' => [IntegrationField::TYPE_BOOLEAN, '0', false],
    'date' => [IntegrationField::TYPE_DATE, '2026-01-01', '2026-01-01'],
    'datetime' => [IntegrationField::TYPE_DATETIME, '2026-01-01 12:00:00', '2026-01-01 12:00:00'],
    'date object' => [IntegrationField::TYPE_DATECLASS, '2026-01-01', new DateTimeImmutable('2026-01-01', new DateTimeZone('UTC'))],
    'array' => [IntegrationField::TYPE_ARRAY, ['a', 'b'], ['a', 'b']],
    'empty array' => [IntegrationField::TYPE_ARRAY, [], []],
    'null number' => [IntegrationField::TYPE_NUMBER, null, null],
    'invalid number' => [IntegrationField::TYPE_NUMBER, 'not a number', null],
]);
