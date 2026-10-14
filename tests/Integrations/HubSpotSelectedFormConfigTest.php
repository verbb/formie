<?php

use verbb\formie\integrations\crm\HubSpot;

it('limits HubSpot form metadata to the selected form during delivery', function(): void {
    $integration = new HubSpot(['formId' => 'portal__selected']);
    $flag = new ReflectionProperty(HubSpot::class, '_useSelectedFormSettings');
    $flag->setValue($integration, true);
    $method = new ReflectionMethod(HubSpot::class, 'prepareCachedFormSettings');
    $settings = [
        'forms' => [
            ['id' => 'portal__first', 'fields' => range(1, 50)],
            ['id' => 'portal__selected', 'fields' => ['email']],
            ['id' => 'portal__last', 'fields' => range(1, 50)],
        ],
        'contact' => ['email'],
    ];

    expect($method->invoke($integration, $settings))->toBe([
        'forms' => [
            ['id' => 'portal__selected', 'fields' => ['email']],
        ],
        'contact' => ['email'],
    ]);
});
