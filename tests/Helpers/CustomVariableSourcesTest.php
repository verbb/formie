<?php

declare(strict_types=1);

use verbb\formie\events\RegisterReferencesEvent;
use verbb\formie\fields\definitions\FieldValueType;
use verbb\formie\helpers\References;
use verbb\formie\helpers\Variables;
use verbb\formie\references\ReferenceCatalogue;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceDefinition;
use verbb\formie\references\ReferenceDiagnostic;
use verbb\formie\references\ReferenceSource;
use verbb\formie\references\ReferenceTransform;
use yii\base\Event;

afterEach(fn() => Event::off(ReferenceCatalogue::class, ReferenceCatalogue::EVENT_REGISTER));

it('registers typed namespaced sources without evaluating server values in the picker', function() {
    $calls = 0;
    Event::on(ReferenceCatalogue::class, ReferenceCatalogue::EVENT_REGISTER, function(RegisterReferencesEvent $event) use (&$calls) {
        $event->sources[] = new ReferenceSource(new ReferenceDefinition('acme/campaign', 'Campaign', 'custom', FieldValueType::string()), function() use (&$calls) { $calls++; return 'private-value'; });
    });
    $groups = Variables::getCategoryConfig()['staticGroups'][Variables::GROUP_CUSTOM];
    expect($groups[0]['value'])->toBe('{custom:acme/campaign}')
        ->and(json_encode($groups))->not->toContain('private-value')->and($calls)->toBe(0);
    expect(References::resolveValue('{custom:acme/campaign}', new ReferenceContext(permissions: ['server']))->requireValue())->toBe('private-value');
    expect(References::resolveValue('{custom:acme/campaign}', new ReferenceContext())->diagnostic)->toBe(ReferenceDiagnostic::ForbiddenSource);
});

it('requires namespaces and rejects duplicate registrations', function() {
    expect(fn() => new ReferenceSource(new ReferenceDefinition('unnamespaced', 'Invalid', 'custom', FieldValueType::string()), fn() => 'x'))->toThrow(InvalidArgumentException::class);
    Event::on(ReferenceCatalogue::class, ReferenceCatalogue::EVENT_REGISTER, function(RegisterReferencesEvent $event) {
        $source = new ReferenceSource(new ReferenceDefinition('acme/value', 'Value', 'custom', FieldValueType::string()), fn() => 'x');
        $event->sources = [$source, $source];
    });
    expect(fn() => new ReferenceCatalogue())->toThrow(InvalidArgumentException::class);
});

it('validates extension input output and availability rather than coercing errors', function() {
    Event::on(ReferenceCatalogue::class, ReferenceCatalogue::EVENT_REGISTER, function(RegisterReferencesEvent $event) {
        $event->sources[] = new ReferenceSource(new ReferenceDefinition('acme/value', 'Value', 'custom', FieldValueType::string(), transforms: ['acme/shout']), fn() => 'hello');
        $event->sources[] = new ReferenceSource(new ReferenceDefinition('acme/invalid', 'Invalid', 'custom', FieldValueType::boolean()), fn() => 'false');
        $event->transforms[] = new ReferenceTransform('acme/shout', FieldValueType::string(), FieldValueType::string(), fn($value) => strtoupper($value));
    });
    $context = new ReferenceContext(permissions: ['server']);
    expect(References::resolveValue('{custom:acme/value;transform=acme%2Fshout}', $context)->requireValue())->toBe('HELLO')
        ->and(References::resolveValue('{custom:acme/invalid}', $context)->diagnostic)->toBe(ReferenceDiagnostic::InvalidType)
        ->and(References::resolveValue('{custom:acme/value;transform=missing}', $context)->diagnostic)->toBe(ReferenceDiagnostic::UnknownTransform);
});

it('diagnoses abandoned beta tokens instead of silently returning empty strings', function() {
    expect(References::resolveValue('{acme:campaign}', new ReferenceContext())->diagnostic)->toBe(ReferenceDiagnostic::UnknownSource);
});
