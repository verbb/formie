<?php

declare(strict_types=1);

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\events\RegisterFieldOptionsEvent;
use verbb\formie\positions\AboveInput;
use verbb\formie\positions\BelowInput;
use verbb\formie\positions\Hidden as HiddenPosition;
use verbb\formie\positions\LeftInput;
use verbb\formie\positions\RightInput;
use verbb\formie\services\Fields;
use yii\base\Event;

it('keeps registered positions as JSON option lists in form appearance settings', function (string $eventName, string $method, string $setting, array $positions, string $registration): void {
    $expectedPositions = match ($registration) {
        'filtered' => $eventName === Fields::EVENT_REGISTER_LABEL_POSITIONS
            ? [AboveInput::class, HiddenPosition::class]
            : [BelowInput::class],
        'named' => [BelowInput::class, AboveInput::class],
        'empty' => [],
        default => $positions,
    };
    $handler = static function (RegisterFieldOptionsEvent $event) use ($eventName, $registration): void {
        if ($registration === 'filtered') {
            // Reproduce a module's array_diff() hook, which retains the original keys.
            $event->options = array_diff($event->options, $eventName === Fields::EVENT_REGISTER_LABEL_POSITIONS
                ? [BelowInput::class, LeftInput::class, RightInput::class]
                : [AboveInput::class]);
        } elseif ($registration === 'named') {
            $event->options = ['last' => BelowInput::class, 'first' => AboveInput::class];
        } elseif ($registration === 'empty') {
            $event->options = [];
        }
    };
    Event::on(Fields::class, $eventName, $handler);

    try {
        $expectedOptions = array_map(static fn(string $position): array => [
            'label' => $position::displayName(),
            'value' => $position,
        ], $expectedPositions);
        $options = Formie::$plugin->getFields()->$method();
        $schema = (new Form())->defineFormBuilderAppearanceSchema();
        $settingNode = array_values(array_filter($schema, static fn(array $node): bool => ($node['name'] ?? null) === $setting));

        expect($options)->toBe($expectedOptions)
            ->and(json_decode(json_encode($options)))->toBeArray()
            ->and($settingNode)->toHaveCount(1)
            ->and($settingNode[0]['options'])->toBe($expectedOptions)
            ->and(json_decode(json_encode($settingNode[0]))->options)->toBeArray();
    } finally {
        Event::off(Fields::class, $eventName, $handler);
    }
})->with([
    'labels' => [Fields::EVENT_REGISTER_LABEL_POSITIONS, 'getLabelPositionsOptions', 'settings.defaultLabelPosition', [AboveInput::class, BelowInput::class, LeftInput::class, RightInput::class, HiddenPosition::class]],
    'instructions' => [Fields::EVENT_REGISTER_INSTRUCTIONS_POSITIONS, 'getInstructionsPositionsOptions', 'settings.defaultInstructionsPosition', [AboveInput::class, BelowInput::class]],
    'errors' => [Fields::EVENT_REGISTER_ERROR_MESSAGE_POSITIONS, 'getErrorMessagePositionsOptions', 'settings.defaultErrorMessagePosition', [AboveInput::class, BelowInput::class]],
])->with(['unchanged', 'filtered', 'named', 'empty']);
