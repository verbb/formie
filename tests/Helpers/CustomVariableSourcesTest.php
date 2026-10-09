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

beforeEach(fn() => \verbb\formie\Formie::$plugin->set('referenceCatalogue', ReferenceCatalogue::class));
afterEach(function () {
    Event::off(ReferenceCatalogue::class, ReferenceCatalogue::EVENT_REGISTER);
    \verbb\formie\Formie::$plugin->set('referenceCatalogue', ReferenceCatalogue::class);
});

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
        $event->sources[] = new ReferenceSource(new ReferenceDefinition('acme/fixed', 'Fixed', 'custom', FieldValueType::string(), transforms: ['acme/shout'], allowTransforms: false), fn() => 'fixed');
        $event->transforms[] = new ReferenceTransform('acme/shout', FieldValueType::string(), FieldValueType::string(), fn($value) => strtoupper($value));
    });
    $context = new ReferenceContext(permissions: ['server']);
    expect(References::resolveValue('{custom:acme/value;transform=acme%2Fshout}', $context)->requireValue())->toBe('HELLO')
        ->and(References::resolveValue('{custom:acme/invalid}', $context)->diagnostic)->toBe(ReferenceDiagnostic::InvalidType)
        ->and(References::resolveValue('{custom:acme/value;transform=missing}', $context)->diagnostic)->toBe(ReferenceDiagnostic::UnknownTransform)
        ->and(References::resolveValue('{custom:acme/fixed;transform=acme%2Fshout}', $context)->diagnostic)->toBe(ReferenceDiagnostic::UnknownTransform);
});

it('diagnoses abandoned beta tokens instead of silently returning empty strings', function() {
    expect(References::resolveValue('{acme:campaign}', new ReferenceContext())->diagnostic)->toBe(ReferenceDiagnostic::UnknownSource);
});

it('enforces source usage before resolution in real mapping and redirect consumers', function() {
    $calls = 0;
    Event::on(ReferenceCatalogue::class, ReferenceCatalogue::EVENT_REGISTER, function(RegisterReferencesEvent $event) use (&$calls) {
        $event->sources[] = new ReferenceSource(new ReferenceDefinition('acme/mapping', 'Mapping', 'custom', FieldValueType::string(), usages: [\verbb\formie\references\ReferenceUsage::Integration]), function() use (&$calls) {
            $calls++;
            return '<mapped>';
        });
    });
    $form = formie()->form()->create();
    $submission = formie()->submission($form)->save();
    $integration = new \verbb\formie\integrations\automations\WebRequest();
    $destination = new \verbb\formie\models\IntegrationField();
    expect($integration->getMappedFieldValue(['kind' => 'reference', 'value' => '{custom:acme/mapping}'], $submission, $destination))->toBe('<mapped>')
        ->and($integration->getMappedFieldValue(['kind' => 'text', 'value' => 'Value: {custom:acme/mapping}'], $submission, $destination))->toBe('Value: <mapped>');
    expect(fn() => References::resolveUrl('{custom:acme/mapping}', $submission))->toThrow(\verbb\formie\references\ReferenceException::class);
    expect(fn() => References::parseContent('{custom:acme/mapping}', $submission, ['outputContext' => \verbb\formie\references\ReferenceOutputContext::EmailHeader]))->toThrow(\verbb\formie\references\ReferenceException::class);
    expect($calls)->toBe(2);
    $metadata = (new ReferenceCatalogue())->pickerSources()[0];
    expect($metadata['usages'])->toBe(['integration']);
});

it('enforces block shape for exact and interpolated references while retaining rich bodies', function() {
    $calls = 0;
    Event::on(ReferenceCatalogue::class, ReferenceCatalogue::EVENT_REGISTER, function(RegisterReferencesEvent $event) use (&$calls) {
        $event->sources[] = new ReferenceSource(new ReferenceDefinition('acme/block', 'Block', 'custom', FieldValueType::string(), shape: \verbb\formie\references\ReferenceShape::Block), function(ReferenceContext $context) use (&$calls) {
            $calls++;
            expect($context->outputContext)->toBe(\verbb\formie\references\ReferenceOutputContext::Html);
            return '<block>';
        });
    });
    $context = new ReferenceContext(permissions: ['server']);
    foreach ([\verbb\formie\references\ReferenceOutputContext::EmailHeader, \verbb\formie\references\ReferenceOutputContext::UrlComponent] as $output) {
        expect(fn() => References::interpolateText('{custom:acme/block}', $context, $output))->toThrow(\verbb\formie\references\ReferenceException::class);
    }
    expect(References::resolveValue('{custom:acme/block}', $context)->diagnostic)->toBe(ReferenceDiagnostic::ForbiddenSource)
        ->and(References::interpolateText('{custom:acme/block}', $context, \verbb\formie\references\ReferenceOutputContext::Html))->toBe('&lt;block&gt;')
        ->and($calls)->toBe(1);
    $form = formie()->form()->singleLineTextField('value')->create();
    $submission = formie()->submission($form)->with(['value' => 'Body value'])->save();
    expect(fn() => References::resolveUrl('{allFields}', $submission))->toThrow(\verbb\formie\references\ReferenceException::class);
    expect(References::parseContent('{allFields}', $submission, ['outputContext' => \verbb\formie\references\ReferenceOutputContext::Html]))->toContain('Body value');
});

it('registers once while resolving each source with its current context', function () {
    $registrations = 0;
    $resolutions = 0;
    Event::on(ReferenceCatalogue::class, ReferenceCatalogue::EVENT_REGISTER, function (RegisterReferencesEvent $event) use (&$registrations, &$resolutions) {
        $registrations++;
        $event->sources[] = new ReferenceSource(new ReferenceDefinition('acme/each', 'Each', 'custom', FieldValueType::string()), function () use (&$resolutions) { return (string)++$resolutions; });
    });
    Variables::getCategoryConfig();
    $context = new ReferenceContext(permissions: ['server']);
    expect(References::resolveValue('{custom:acme/each}', $context)->requireValue())->toBe('1')
        ->and(References::resolveValue('{custom:acme/each}', $context)->requireValue())->toBe('2')
        ->and($registrations)->toBe(1);
});
