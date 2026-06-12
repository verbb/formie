<?php

declare(strict_types=1);

use Tests\Support\MaliciousPayloads;
use verbb\formie\events\DefineHiddenDefaultTemplateContextEvent;
use verbb\formie\fields\Hidden;
use verbb\formie\helpers\HiddenDefaultTemplateResolver;

use yii\base\Event;

it('resolves template defaults from form context for initial values', function (): void {
    $form = formie()
        ->form(['title' => 'Event Registration', 'handle' => 'eventRegistration'])
        ->hiddenField('eventHandle', [
            'defaultOption' => Hidden::DEFAULT_OPTION_TEMPLATE,
            'defaultTemplate' => '{form.handle}',
        ])
        ->create();

    $field = $form->getFieldByHandle('eventHandle');

    expect($field?->getInitialValue($form))->toBe('eventRegistration');
})->group('fields');

it('ignores posted hidden values when using template defaults', function (): void {
    $payload = "{{ 7 * 7 }} {{ craft.app.cache.cachePath }} {system:email}";

    $form = formie()
        ->form(['title' => 'Template Hidden Security', 'handle' => 'templateHiddenSecurity'])
        ->hiddenField('trackingToken', [
            'defaultOption' => Hidden::DEFAULT_OPTION_TEMPLATE,
            'defaultTemplate' => '{form.handle}',
        ])
        ->create();

    $submission = formie()->submission($form)->with([
        'trackingToken' => $payload,
    ])->save();

    expect($submission->getFieldValue('trackingToken'))->toBe('templateHiddenSecurity')
        ->and($submission->getFieldValue('trackingToken'))->not->toBe($payload);
})->group('security');

it('keeps custom hidden defaults post-wins behaviour', function (): void {
    $payload = "{{ 7 * 7 }} {{ craft.app.cache.cachePath }} {system:email}";

    $form = formie()
        ->form(['title' => 'Hidden SSTI Security'])
        ->hiddenField('trackingToken', [
            'defaultOption' => 'custom',
            'defaultValue' => 'safe-default',
        ])
        ->create();

    $submission = formie()->submission($form)->with([
        'trackingToken' => $payload,
    ])->save();

    expect($submission->getFieldValue('trackingToken'))->toBe($payload);
})->group('security');

it('still resolves admin-authored custom hidden defaults when no value is submitted', function (): void {
    $form = formie()
        ->form(['title' => 'Hidden Default Reference Security'])
        ->hiddenField('trackingToken', [
            'defaultOption' => 'custom',
            'defaultValue' => 'fallback-{form:handle}',
        ])
        ->create();

    $submission = formie()->submission($form)->with([
        'trackingToken' => '',
    ])->save();

    expect($submission->getFieldValue('trackingToken'))->toBe('fallback-' . $form->handle);
})->group('security');

it('does not execute twig probes in template hidden initial values', function (): void {
    $form = formie()
        ->form(['title' => 'Hidden Template Initial Value Contract', 'handle' => 'hiddenTemplateInitial'])
        ->hiddenField('trackingToken', [
            'defaultOption' => Hidden::DEFAULT_OPTION_TEMPLATE,
            'defaultTemplate' => '{form.handle}',
        ])
        ->create();

    $field = $form->getFieldByHandle('trackingToken');
    $inputOptions = $field?->getInputTemplateVariables($form, MaliciousPayloads::twigProbe());

    expect($inputOptions)->toBeArray()
        ->and($inputOptions['value'] ?? null)->toBe('hiddenTemplateInitial');
})->group('security');

it('allows extending template context through the define context event', function (): void {
    $handler = static function(DefineHiddenDefaultTemplateContextEvent $event): void {
        $event->variables['eventCost'] = '42';
    };

    Event::on(HiddenDefaultTemplateResolver::class, HiddenDefaultTemplateResolver::EVENT_DEFINE_CONTEXT, $handler);

    try {
        $form = formie()
            ->form(['title' => 'Event Registration'])
            ->hiddenField('eventCost', [
                'defaultOption' => Hidden::DEFAULT_OPTION_TEMPLATE,
                'defaultTemplate' => '{eventCost}',
            ])
            ->create();

        $field = $form->getFieldByHandle('eventCost');

        expect($field?->getInitialValue($form))->toBe('42');
    } finally {
        Event::off(HiddenDefaultTemplateResolver::class, HiddenDefaultTemplateResolver::EVENT_DEFINE_CONTEXT, $handler);
    }
})->group('fields');
