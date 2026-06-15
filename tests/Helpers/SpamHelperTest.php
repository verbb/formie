<?php

declare(strict_types=1);

use verbb\formie\helpers\SpamHelper;

it('matches plain spam keywords as whole words', function (): void {
    $match = SpamHelper::checkContent('this message contains blocked-keyword text', null, ['blocked-keyword']);

    expect($match)->toBeArray()
        ->and($match['type'])->toBe('text')
        ->and($match['value'])->toBe('blocked-keyword');
});

it('matches logical spam keyword rules', function (): void {
    $match = SpamHelper::checkContent('bulk offer only', null, ['[match: spam AND bulk]']);

    expect($match)->toBeFalse();

    $match = SpamHelper::checkContent('spam bulk offer', null, ['[match: spam AND bulk]']);

    expect($match)->toBeArray()
        ->and($match['value'])->toBe('[match: spam AND bulk]');
});

it('matches ip rules including cidr notation', function (): void {
    $match = SpamHelper::checkContent('safe-content', '10.0.0.15', ['[ip: 10.0.0.0/24]']);

    expect($match)->toBeArray()
        ->and($match['type'])->toBe('ip')
        ->and($match['value'])->toBe('[ip: 10.0.0.0/24]');
});

it('normalizes blocked domain lists for global email rules', function (): void {
    $domains = SpamHelper::parseDomainList(" Mailinator.COM \n tempmail.net\n");

    expect($domains)->toBe(['mailinator.com', 'tempmail.net']);
});

it('builds spam reasons for keyword and email matches', function (): void {
    expect(SpamHelper::spamReasonFromMatch([
        'type' => 'text',
        'value' => 'blocked-keyword',
    ]))->toContain('blocked-keyword');

    expect(SpamHelper::spamReasonFromEmailMatch([
        'type' => 'blockedEmailDomain',
        'value' => 'mailinator.com',
    ]))->toContain('mailinator.com');
});
