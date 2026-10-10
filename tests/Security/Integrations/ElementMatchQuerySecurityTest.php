<?php

declare(strict_types=1);

use craft\elements\User as UserElement;
use craft\db\QueryParam;
use verbb\formie\elements\Submission;
use verbb\formie\integrations\elements\User as UserIntegration;
use verbb\formie\models\IntegrationField;

class ElementMatchQuerySecurityProbe extends UserIntegration
{
    public array $matchValues = [];

    public function matchUser(array $values): UserElement
    {
        $this->matchValues = $values;

        return $this->defineElementForPayload(UserElement::class, 'users', new Submission());
    }

    public function prepareValues(array $values, array $fieldSettings): ?array
    {
        return $this->prepareElementMatchValues($values, $fieldSettings);
    }

    public function getFieldMappingValues(Submission $submission, ?array $fieldMapping, mixed $fieldSettings = null)
    {
        return $this->matchValues;
    }
}

function elementMatchQuerySecurityProbe(array $mapping = ['email' => 'mapped']): ElementMatchQuerySecurityProbe
{
    return new ElementMatchQuerySecurityProbe([
        'name' => 'Element match security probe',
        'handle' => 'elementMatchSecurityProbe',
        'updateElement' => true,
        'updateElementMapping' => $mapping,
    ]);
}

it('treats submitted element match strings as literal values', function(string $value): void {
    $integration = elementMatchQuerySecurityProbe();

    expect($integration->matchUser(['email' => $value])->id)->toBeNull();
})->with([
    'wildcard' => '*@example.test',
    'not-empty operator' => ':notempty:',
    'comma list' => 'admin@example.test,formie-seed-user@example.test',
    'logical list operator' => 'or admin@example.test',
    'comparison operator' => '> 0',
]);

it('retains ordinary exact element matching', function(): void {
    $target = UserElement::find()->status(null)->email('formie-seed-user@example.test')->one();
    $integration = elementMatchQuerySecurityProbe();

    expect($target)->not->toBeNull()
        ->and($integration->matchUser(['email' => $target->email])->id)->toBe($target->id);
});

it('prevents submitted array operators while retaining ordinary list matching', function(): void {
    $target = UserElement::find()->status(null)->email('formie-seed-user@example.test')->one();
    $integration = elementMatchQuerySecurityProbe();

    expect($target)->not->toBeNull()
        ->and($integration->matchUser(['email' => ['not', $target->email]])->id)->toBe($target->id);
});

it('does not broaden a match when any configured criterion is empty', function(mixed $empty): void {
    $target = UserElement::find()->status(null)->email('formie-seed-user@example.test')->one();
    $integration = elementMatchQuerySecurityProbe([
        'email' => 'mapped',
        'username' => 'mapped',
    ]);

    expect($target)->not->toBeNull()
        ->and($integration->matchUser(['email' => $target->email, 'username' => $empty])->id)->toBeNull();
})->with([
    'empty string' => [''],
    'whitespace-only string' => ['   '],
    'null' => [null],
    'empty array' => [[]],
]);

it('does not match when a configured criterion is removed', function(): void {
    $target = UserElement::find()->status(null)->email('formie-seed-user@example.test')->one();
    $integration = elementMatchQuerySecurityProbe([
        'email' => 'mapped',
        'username' => 'mapped',
    ]);

    expect($target)->not->toBeNull()
        ->and($integration->matchUser(['email' => $target->email])->id)->toBeNull();
});

it('does not match when a configured destination is no longer available', function(): void {
    $target = UserElement::find()->status(null)->email('formie-seed-user@example.test')->one();
    $integration = elementMatchQuerySecurityProbe([
        'email' => 'mapped',
        'staleHandle' => 'mapped',
    ]);

    expect($target)->not->toBeNull()
        ->and($integration->matchUser(['email' => $target->email])->id)->toBeNull();
});

it('preserves false and zero match values', function(): void {
    $integration = elementMatchQuerySecurityProbe([
        'enabled' => 'mapped',
        'count' => 'mapped',
        'code' => 'mapped',
    ]);
    $settings = [
        new IntegrationField(['handle' => 'enabled', 'type' => IntegrationField::TYPE_BOOLEAN]),
        new IntegrationField(['handle' => 'count', 'type' => IntegrationField::TYPE_NUMBER]),
        new IntegrationField(['handle' => 'code', 'type' => IntegrationField::TYPE_STRING]),
    ];

    expect($integration->prepareValues(['enabled' => false, 'count' => 0, 'code' => '0'], $settings))->toBe([
        'enabled' => false,
        'count' => 0,
        'code' => '0',
    ]);
});

it('keeps multi-option values literal and rejects their reserved query tokens', function(): void {
    $integration = elementMatchQuerySecurityProbe(['options' => 'mapped']);
    $settings = [new IntegrationField(['handle' => 'options', 'type' => IntegrationField::TYPE_ARRAY])];

    expect($integration->prepareValues(['options' => ['not', '*', 'one,two']], $settings))->toBe([
        'options' => [QueryParam::OR, 'not', '*', 'one,two'],
    ])->and($integration->prepareValues(['options' => [':notempty:']], $settings))->toBeNull();
});
