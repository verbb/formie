<?php

declare(strict_types=1);

use verbb\formie\integrations\crm\Salesforce;

it('escapes submitted values before interpolating them into salesforce soql literals', function (): void {
    $integration = new Salesforce([
        'name' => 'Security Salesforce',
        'handle' => 'securitySalesforce',
    ]);
    $method = new ReflectionMethod(Salesforce::class, '_escapeSoqlString');
    $method->setAccessible(true);

    expect($method->invoke($integration, "x' OR Email != 'x"))
        ->toBe("x\\' OR Email != \\'x")
        ->and($method->invoke($integration, "x\\'"))
        ->toBe("x\\\\\\'");
})->group('security');
