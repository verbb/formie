<?php

use verbb\formie\integrations\crm\HubSpot;
use verbb\formie\models\IntegrationCollection;

it('limits HubSpot form metadata to the selected form during delivery', function(): void {
    $integration = new HubSpot(['formId' => 'portal__selected']);
    $flag = new ReflectionProperty(HubSpot::class, '_useSelectedFormSettings');
    $flag->setValue($integration, true);
    $method = new ReflectionMethod(HubSpot::class, 'prepareCachedFormSettings');
    $settings = [
        'forms' => [
            ['id' => 'portal__first', 'fields' => range(1, 50)],
            new IntegrationCollection(['id' => 'portal__selected', 'fields' => ['email']]),
            ['id' => 'portal__last', 'fields' => range(1, 50)],
        ],
        'contact' => ['email'],
    ];

    $filtered = $method->invoke($integration, $settings);

    expect($filtered['forms'])->toHaveCount(1)
        ->and($filtered['forms'][0])->toBe($settings['forms'][1])
        ->and($filtered['contact'])->toBe(['email']);
});
