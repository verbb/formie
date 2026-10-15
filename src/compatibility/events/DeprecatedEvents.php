<?php
namespace verbb\formie\compatibility\events;

use yii\base\Component;
use yii\base\Event;
use yii\helpers\StringHelper;

use ReflectionProperty;

class DeprecatedEvents
{
    // Static Methods
    // =========================================================================

    public static function hasNamedHandlers(Component $component, string $name): bool
    {
        $component->ensureBehaviors();

        // Yii's public checks include wildcard observers, such as the debug toolbar.
        // Inspect only registrations naming this event to establish deprecated API use.
        static $instanceEvents = new ReflectionProperty(Component::class, '_events');
        static $classEvents = new ReflectionProperty(Event::class, '_events');
        static $wildcardEvents = new ReflectionProperty(Event::class, '_eventWildcards');

        if (!empty($instanceEvents->getValue($component)[$name])) {
            return true;
        }

        $classes = [get_class($component), ...class_parents($component), ...class_implements($component)];
        $handlers = $classEvents->getValue()[$name] ?? [];

        foreach ($classes as $class) {
            if (!empty($handlers[$class])) {
                return true;
            }
        }

        // A wildcard class with an exact event name still explicitly uses the API.
        foreach ($wildcardEvents->getValue()[$name] ?? [] as $pattern => $handlers) {
            foreach ($classes as $class) {
                if ($handlers && StringHelper::matchWildcard($pattern, $class, ['escape' => false])) {
                    return true;
                }
            }
        }

        return false;
    }
}
