<?php

declare(strict_types=1);

use craft\base\Element;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use Twig\Extension\SandboxExtension;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use verbb\formie\Formie;
use verbb\formie\fields\values\SingleOptionFieldValue;
use verbb\formie\services\Templates;

it('blocks callable methods on element collections', function (): void {
    $items = new ElementCollection(['safe']);

    expect(fn() => Formie::$plugin->getTemplates()->renderSandboxedString(
        '{{ items.mapToDictionary("strlen")|length }}',
        ['items' => $items],
    ))->toThrow(SecurityNotAllowedMethodError::class);
})->group('security');

it('blocks eachSpread string callables on element collections', function (): void {
    $items = new ElementCollection([['safe']]);

    expect(fn() => Formie::$plugin->getTemplates()->renderSandboxedString(
        '{{ items.eachSpread("sprintf")|length }}',
        ['items' => $items],
    ))->toThrow(SecurityNotAllowedMethodError::class);
})->group('security');

it('blocks the reported submission query to collection callable chain', function (): void {
    $form = formie()->form(['title' => 'Twig Collection Sandbox Security'])->create();
    $submission = formie()->submission($form)->save();

    expect(fn() => Formie::$plugin->getTemplates()->renderSandboxedObjectTemplate(
        "{{ object.getLocalized().emulateExecution().collect().concat([['%s', 'safe']]).eachSpread('sprintf').take(0).concat(['']).get(0) }}",
        $submission,
    ))->toThrow(SecurityNotAllowedMethodError::class);
})->group('security');

it('blocks higher-order collection proxies', function (): void {
    $items = new ElementCollection(['safe']);

    expect(fn() => Formie::$plugin->getTemplates()->renderSandboxedString(
        '{{ items.map.strlen }}',
        ['items' => $items],
    ))->toThrow(SecurityNotAllowedPropertyError::class);
})->group('security');

it('preserves safe element collection access', function (): void {
    $items = new ElementCollection(['safe']);
    $rendered = Formie::$plugin->getTemplates()->renderSandboxedString(
        '{{ items.count() }}:{{ items[0] }}:{{ items.all()[0] }}',
        ['items' => $items],
    );

    expect($rendered)->toBe('1:safe:safe');
})->group('security');

it('preserves printable Formie option values', function (): void {
    $value = new SingleOptionFieldValue('Published', 'published', true);

    $rendered = Formie::$plugin->getTemplates()->renderSandboxedString(
        '{{ value }}',
        ['value' => $value],
    );

    expect($rendered)->toBe('published');
})->group('security');

it('adds extension properties without replacing Base element permissions', function (): void {
    $templates = new Templates([
        'pluginClass' => Formie::class,
        'additionalAllowedProperties' => [Element::class => ['customLabel']],
    ]);
    $policy = $templates->getSandboxedTwig()->getExtension(SandboxExtension::class)->getSecurityPolicy();
    $entry = new Entry();

    $policy->checkPropertyAllowed($entry, 'id');
    $policy->checkPropertyAllowed($entry, 'customLabel');

    expect(true)->toBeTrue();
})->group('security');

it('renders object shorthand while leaving environment aliases literal', function (): void {
    $rendered = Formie::$plugin->getTemplates()->renderSandboxedObjectTemplate(
        'Submission-${FORMIE_TEST}-{number}',
        ['number' => 42],
        autoescape: false,
    );

    expect($rendered)->toBe('Submission-${FORMIE_TEST}-42');
})->group('security');

it('rejects unapproved functions in form-authored object templates', function (): void {
    expect(fn() => Formie::$plugin->getTemplates()->renderSandboxedObjectTemplate(
        '{{ getenv("PATH") }}',
        [],
    ))->toThrow(Twig\Error\Error::class);
})->group('security');
