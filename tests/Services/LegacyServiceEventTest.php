<?php

use craft\services\Deprecator;
use verbb\formie\events\RegisterPredefinedOptionsEvent;
use verbb\formie\events\SubmissionStatusEvent;
use verbb\formie\services\OptionSources;
use verbb\formie\services\SubmissionStatuses;
use yii\base\Event;

dataset('legacy service events', [
    [OptionSources::class, 'verbb\\formie\\services\\PredefinedOptions', OptionSources::EVENT_REGISTER_PREDEFINED_OPTIONS],
    [SubmissionStatuses::class, 'verbb\\formie\\services\\Statuses', SubmissionStatuses::EVENT_BEFORE_SAVE_STATUS],
    [SubmissionStatuses::class, 'verbb\\formie\\services\\Statuses', SubmissionStatuses::EVENT_AFTER_SAVE_STATUS],
    [SubmissionStatuses::class, 'verbb\\formie\\services\\Statuses', SubmissionStatuses::EVENT_BEFORE_DELETE_STATUS],
    [SubmissionStatuses::class, 'verbb\\formie\\services\\Statuses', SubmissionStatuses::EVENT_BEFORE_APPLY_STATUS_DELETE],
    [SubmissionStatuses::class, 'verbb\\formie\\services\\Statuses', SubmissionStatuses::EVENT_AFTER_DELETE_STATUS],
]);

it('dispatches service events with debug observers and deprecation exceptions enabled', function (string $class, string $legacyClass, string $name): void {
    $service = new $class();
    $deprecator = Craft::$app->getDeprecator();
    $originalThrowExceptions = $deprecator->throwExceptions;
    $observed = 0;
    $modernCalls = 0;
    $observer = static function (Event $event) use ($service, $name, &$observed): void {
        if ($event->sender === $service && $event->name === $name) {
            $observed++;
        }
    };
    $modernHandler = static function () use (&$modernCalls): void {
        $modernCalls++;
    };
    // Match the debug toolbar's catch-all registration without booting the toolbar.
    Event::on('*', '*', $observer);
    Event::on($class, $name, $modernHandler);

    try {
        $deprecator->throwExceptions = true;

        if ($service instanceof OptionSources) {
            expect($service->getPredefinedProviderOptions())->not->toBeEmpty();
        } else {
            $service->trigger($name, new SubmissionStatusEvent());
        }

        expect($modernCalls)->toBe(1)
            ->and($observed)->toBe(1);
    } finally {
        $deprecator->throwExceptions = $originalThrowExceptions;
        Event::off('*', '*', $observer);
        Event::off($class, $name, $modernHandler);
    }
})->with('legacy service events');

it('retains named legacy service handlers and their deprecation warnings', function (string $class, string $legacyClass, string $name): void {
    $service = new $class();
    $event = $service instanceof OptionSources ? new RegisterPredefinedOptionsEvent() : new SubmissionStatusEvent();
    $originalDeprecator = Craft::$app->getDeprecator();
    $deprecator = $this->getMockBuilder(Deprecator::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['log'])
        ->getMock();
    $deprecator->expects($this->once())->method('log')->with($legacyClass . '::' . $name, $this->isType('string'));
    $calls = 0;
    $handler = static function (Event $received) use ($event, $service, &$calls): void {
        expect($received)->toBe($event)
            ->and($received->sender)->toBe($service);
        $received->handled = true;
        $calls++;
    };
    $observer = static function (): void {};
    Event::on('*', '*', $observer);
    Event::on($legacyClass, $name, $handler);

    try {
        Craft::$app->set('deprecator', $deprecator);
        $service->trigger($name, $event);

        expect($calls)->toBe(1)
            ->and($event->handled)->toBeTrue()
            ->and($event->name)->toBe($name)
            ->and($event->sender)->toBe($service);
    } finally {
        Craft::$app->set('deprecator', $originalDeprecator);
        Event::off('*', '*', $observer);
        Event::off($legacyClass, $name, $handler);
    }
})->with('legacy service events');
