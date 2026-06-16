<?php

declare(strict_types=1);

use verbb\formie\events\RegisterVariablesEvent;
use verbb\formie\Formie;
use verbb\formie\helpers\References;
use verbb\formie\helpers\Variables;
use verbb\formie\variables\VariableSource;
use yii\base\Event;

beforeEach(function (): void {
    Variables::clearRegisteredVariableSourcesCache();
    Formie::$plugin->getSettings()->compatibilityMode = true;
});

afterEach(function (): void {
    Variables::clearRegisteredVariableSourcesCache();
    Event::off(Variables::class, Variables::EVENT_REGISTER_VARIABLES);
    Formie::$plugin->getSettings()->compatibilityMode = true;
});

it('registers custom variable sources for the picker and resolves their values', function (): void {
    Event::on(Variables::class, Variables::EVENT_REGISTER_VARIABLES, function(RegisterVariablesEvent $event): void {
        $event->sources[] = VariableSource::create('acme_lead_source', 'Lead source')
            ->resolve(fn() => 'newsletter');
    });

    $groups = Variables::getCategoryConfig()['staticGroups'][Variables::GROUP_CUSTOM] ?? [];

    expect($groups)->not->toBeEmpty()
        ->and($groups[0]['value'] ?? null)->toBe('{custom:acme_lead_source}');

    $form = formie()
        ->form(['title' => 'Custom Variables'])
        ->singleLineTextField('name')
        ->create();

    $submission = formie()->submission($form)->with([
        'name' => 'Taylor',
    ])->save();

    $parsed = References::parseContent('Source: {custom:acme_lead_source}', $submission);

    expect($parsed)->toBe('Source: newsletter');
});

it('applies transforms to custom variable source values', function (): void {
    Event::on(Variables::class, Variables::EVENT_REGISTER_VARIABLES, function(RegisterVariablesEvent $event): void {
        $event->sources[] = VariableSource::create('acme_score', 'Score')
            ->types([Variables::TYPE_NUMBER])
            ->resolve(fn() => 42.6);
    });

    $form = formie()
        ->form(['title' => 'Custom Variable Transforms'])
        ->create();

    $submission = formie()->submission($form)->save();

    $parsed = References::parseContent('{custom:acme_score;transform=round}', $submission);

    expect($parsed)->toBe('43');
});

it('ignores duplicate custom variable sources', function (): void {
    Event::on(Variables::class, Variables::EVENT_REGISTER_VARIABLES, function(RegisterVariablesEvent $event): void {
        $event->sources[] = VariableSource::create('acme_token', 'First')
            ->resolve(fn() => 'first');
        $event->sources[] = VariableSource::create('acme_token', 'Duplicate')
            ->resolve(fn() => 'second');
    });

    $sources = Variables::getRegisteredVariableSources();

    expect($sources)->toHaveCount(1)
        ->and($sources[0]->getLabel())->toBe('First');
});

it('exposes custom variables under the general picker alias', function (): void {
    Event::on(Variables::class, Variables::EVENT_REGISTER_VARIABLES, function(RegisterVariablesEvent $event): void {
        $event->sources[] = VariableSource::create('acme_campaign', 'Campaign code')
            ->resolve(fn() => 'spring-sale');
    });

    $aliases = Variables::getCategoryConfig()['groupAliases'][Variables::STATIC_GENERAL] ?? [];

    expect($aliases)->toContain(Variables::GROUP_CUSTOM);
});

it('supports legacy beta registration and token resolution while compatibility mode is enabled', function (): void {
    Event::on(Variables::class, Variables::EVENT_REGISTER_VARIABLES, function(RegisterVariablesEvent $event): void {
        $event->register('acme', 'campaign', 'Campaign code')
            ->resolve(fn() => 'spring-sale');
    });

    $sources = Variables::getRegisteredVariableSources();

    expect($sources)->toHaveCount(1)
        ->and($sources[0]->getHandle())->toBe('acme_campaign')
        ->and($sources[0]->getToken())->toBe('{custom:acme_campaign}');

    $form = formie()->form(['title' => 'Legacy Custom Variables'])->create();
    $submission = formie()->submission($form)->save();

    $parsed = References::parseContent('Code: {acme:campaign}', $submission);

    expect($parsed)->toBe('Code: spring-sale');
});

it('does not resolve legacy custom variable tokens when compatibility mode is disabled', function (): void {
    Formie::$plugin->getSettings()->compatibilityMode = false;

    Event::on(Variables::class, Variables::EVENT_REGISTER_VARIABLES, function(RegisterVariablesEvent $event): void {
        $event->sources[] = VariableSource::create('acme_campaign', 'Campaign code')
            ->resolve(fn() => 'spring-sale');
    });

    $form = formie()->form(['title' => 'Legacy Custom Variables Disabled'])->create();
    $submission = formie()->submission($form)->save();

    $parsed = References::parseContent('Code: {acme:campaign}', $submission);

    expect($parsed)->toBe('Code: ');
});
