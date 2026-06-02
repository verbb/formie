<?php

declare(strict_types=1);

use verbb\formie\services\Emails;

it('removes script content and control characters from email header strings', function (): void {
    $emails = new Emails();
    $method = new ReflectionMethod(Emails::class, '_getFilteredString');
    $method->setAccessible(true);

    $filtered = $method->invoke(
        $emails,
        "  <script>alert('xss')</script><b>Sender</b>\r\n\t"
    );

    expect($filtered)
        ->toContain('Sender')
        ->not->toContain('<script')
        ->not->toContain("\r")
        ->not->toContain("\n");
})->group('security');
