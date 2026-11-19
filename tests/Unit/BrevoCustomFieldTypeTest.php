<?php

declare(strict_types=1);

use verbb\formie\integrations\emailmarketing\Brevo;
use verbb\formie\models\IntegrationField;

it('maps Brevo multiple-choice attributes to array integration fields', function (): void {
    $integration = new Brevo(['name' => 'Brevo', 'handle' => 'brevo']);
    $method = new ReflectionMethod($integration, '_getCustomFields');
    $method->setAccessible(true);

    $fields = $method->invoke($integration, [
        ['name' => 'INTERESTS', 'category' => 'normal', 'type' => 'multiple-choice', 'multiCategoryOptions' => ['Option A', 'Option B']],
        ['name' => 'FIRSTNAME', 'category' => 'normal', 'type' => 'text'],
        ['name' => 'SCORE', 'category' => 'normal', 'type' => 'float'],
    ]);

    $types = [];

    foreach ($fields as $field) {
        expect($field)->toBeInstanceOf(IntegrationField::class);
        $types[$field->handle] = $field->getType();
    }

    expect($types)->toBe([
        'INTERESTS' => IntegrationField::TYPE_ARRAY,
        'FIRSTNAME' => IntegrationField::TYPE_STRING,
        'SCORE' => IntegrationField::TYPE_FLOAT,
    ]);
});
