<?php

use Tests\Support\WebRequestTestHelper;
use verbb\formie\base\Field;
use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Form;
use verbb\formie\Formie;
use verbb\formie\theme\context\RenderContext;

use craft\base\Element;
use craft\base\ElementInterface;
use craft\services\Deprecator;

use yii\base\Behavior;
use yii\base\Event;

beforeEach(function (): void {
    $this->tagDeprecations = [];
    $deprecator = $this->getMockBuilder(Deprecator::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['log'])
        ->getMock();
    $deprecator->method('log')->willReturnCallback(function (string $key): void {
        if (str_ends_with($key, '::EVENT_MODIFY_HTML_TAG')) {
            $this->tagDeprecations[] = $key;
        }
    });
    $this->tagDeprecator = $deprecator;
});

it('renders forms without deprecated tag warnings for debug observers', function (bool $compatibilityMode): void {
    Formie::$plugin->getSettings()->compatibilityMode = $compatibilityMode;
    $form = formie()->form()->singleLineTextField('message')->create();
    $observed = [];
    // Match Yii's EventPanel registration without leaving its anonymous handler attached.
    $observer = static function (Event $event) use (&$observed): void {
        $observed[] = $event->name;
    };
    Event::on('*', '*', $observer);

    try {
        $html = WebRequestTestHelper::withWebRequestContext(function () use ($form): string {
            Craft::$app->set('deprecator', $this->tagDeprecator);
            return (string)Formie::$plugin->getRendering()->renderForm($form);
        });
    } finally {
        Event::off('*', '*', $observer);
    }

    expect($html)->toContain('data-formie', 'message')
        ->and($observed)->toContain(Field::EVENT_MODIFY_SLOT_TAG, Field::EVENT_MODIFY_HTML_TAG)
        ->and($this->tagDeprecations)->toBe([]);
})->with([true, false]);

it('preserves tag callbacks and only warns for named legacy listeners', function (string $owner, string $registration): void {
    $form = formie()->form()->singleLineTextField('message')->create();
    $component = $owner === 'form' ? $form : $form->getFieldByHandle('message');
    $key = $owner === 'form' ? 'form' : 'fieldInput';
    $calls = 0;
    $handler = static function (Event $event) use (&$calls): void {
        $calls++;
        $event->tag->attributes['data-test-listener'] = 'retained';
    };
    $legacyName = $component::EVENT_MODIFY_HTML_TAG;
    $eventName = match ($registration) {
        'modern' => $component::EVENT_MODIFY_SLOT_TAG,
        'instance observer', 'class observer' => 'modify*',
        default => $legacyName,
    };
    $class = match ($registration) {
        'base class' => $owner === 'form' ? Element::class : Field::class,
        'interface' => $owner === 'form' ? ElementInterface::class : FieldInterface::class,
        'wildcard class' => 'verbb\\formie\\*',
        default => get_class($component),
    };
    $instance = in_array($registration, ['instance', 'modern', 'instance observer'], true);
    $debugObserver = static function (): void {};
    Event::on('*', '*', $debugObserver);

    if ($registration === 'behavior') {
        $component->attachBehavior('legacyTag', new class ($handler) extends Behavior {
            public function __construct(private mixed $handler)
            {
                parent::__construct();
            }

            public function events(): array
            {
                return ['modifyHtmlTag' => $this->handler];
            }
        });
    } elseif ($instance) {
        $component->on($eventName, $handler);
    } else {
        Event::on($class, $eventName, $handler);
    }

    try {
        $tag = WebRequestTestHelper::withWebRequestContext(function () use ($component, $key, $form) {
            Craft::$app->set('deprecator', $this->tagDeprecator);
            return $component->renderSlotTag($key, RenderContext::from(['form' => $form]));
        });
    } finally {
        Event::off('*', '*', $debugObserver);

        if ($registration === 'behavior') {
            $component->detachBehavior('legacyTag');
        } elseif ($instance) {
            $component->off($eventName, $handler);
        } else {
            Event::off($class, $eventName, $handler);
        }
    }

    $observer = in_array($registration, ['instance observer', 'class observer'], true);
    $deprecated = !$observer && $registration !== 'modern';

    expect($tag?->attributesForRender()['data-test-listener'] ?? null)->toBe('retained')
        ->and($calls)->toBe($observer ? 2 : 1)
        ->and($this->tagDeprecations)->toBe($deprecated ? [get_class($component) . '::EVENT_MODIFY_HTML_TAG'] : []);
})->with(['field', 'form'])->with(['instance', 'class', 'base class', 'interface', 'wildcard class', 'behavior', 'modern', 'instance observer', 'class observer']);
