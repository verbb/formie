<?php
namespace verbb\formie\compatibility\fields;

use verbb\formie\Formie;
use verbb\formie\base\Field;
use verbb\formie\helpers\Html;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\Notification;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

use Craft;

use ReflectionMethod;
use WeakMap;

class FieldCompatibility
{
    // Static Methods
    // =========================================================================

    public static function isCalling(object|string $field, string $method): bool
    {
        $key = is_object($field) ? spl_object_id($field) : $field;
        return isset(self::$_calls[$key][$method]);
    }

    public static function withLegacyCall(object|string $field, string $method, callable $callback): mixed
    {
        $key = is_object($field) ? spl_object_id($field) : $field;
        $previous = self::$_calls[$key][$method] ?? null;
        self::$_calls[$key][$method] = true;

        try {
            return $callback();
        } finally {
            if ($previous === null) {
                unset(self::$_calls[$key][$method]);

                if (self::$_calls[$key] === []) {
                    unset(self::$_calls[$key]);
                }
            } else {
                self::$_calls[$key][$method] = $previous;
            }
        }
    }

    public static function prefersLegacyMethod(object|string $field, string $legacyMethod, string $canonicalMethod): bool
    {
        if (self::isCalling($field, $legacyMethod) || !method_exists($field, $legacyMethod)) {
            return false;
        }

        $legacyOwner = (new ReflectionMethod($field, $legacyMethod))->getDeclaringClass()->getName();
        $canonicalMethods = [$canonicalMethod];
        $definition = preg_replace('/^getValue/', 'defineValue', $canonicalMethod);

        if ($definition !== $canonicalMethod && method_exists($field, $definition)) {
            $canonicalMethods[] = $definition;
        }

        foreach ($canonicalMethods as $method) {
            $canonicalOwner = (new ReflectionMethod($field, $method))->getDeclaringClass()->getName();

            // A newer canonical declaration (including its protected hook) wins.
            if ($legacyOwner === Field::class || $legacyOwner === $canonicalOwner || !is_a($legacyOwner, $canonicalOwner, true)) {
                return false;
            }
        }

        return true;
    }

    public static function withReferenceNotification(Field $field, ?Notification $notification, callable $callback): mixed
    {
        self::$_notifications ??= new WeakMap();
        $previous = self::$_notifications[$field] ?? null;
        self::$_notifications[$field] = $notification;

        try {
            return $callback();
        } finally {
            if ($previous === null) {
                unset(self::$_notifications[$field]);
            } else {
                self::$_notifications[$field] = $previous;
            }
        }
    }

    public static function referenceNotification(Field $field): ?Notification
    {
        return self::$_notifications[$field] ?? null;
    }

    public static function resolveSchema(Field $field, string $canonicalMethod): array
    {
        $legacyMethod = str_replace('defineFormBuilder', 'define', $canonicalMethod);

        if (Formie::$plugin->getCompatibility()->isCompatibilityModeEnabled() && self::prefersLegacyMethod($field, $legacyMethod, $canonicalMethod)) {
            return self::resolveLegacySchema($field, $legacyMethod, $canonicalMethod);
        }

        return $field->$canonicalMethod();
    }

    public static function resolveLegacySchema(object $field, string $legacyMethod, string $newMethod): array
    {
        if (!Formie::$plugin->getCompatibility()->isCompatibilityModeEnabled() || !self::prefersLegacyMethod($field, $legacyMethod, $newMethod)) {
            return [];
        }

        Craft::$app->getDeprecator()->log(get_class($field) . '::' . $legacyMethod, "Field `{$legacyMethod}()` has been deprecated. Use `{$newMethod}()` instead.");

        return self::withLegacyCall($field, $legacyMethod, fn() => $field->$legacyMethod());
    }

    public static function renderLegacyHtmlTag(object $field, string $key, RenderContext $context): ?SlotTag
    {
        if (!Formie::$plugin->getCompatibility()->isCompatibilityModeEnabled()) {
            return null;
        }

        if (!method_exists($field, 'defineHtmlTag')) {
            return null;
        }

        if ((new ReflectionMethod($field, 'defineHtmlTag'))->getDeclaringClass()->getName() === Field::class) {
            return null;
        }

        Craft::$app->getDeprecator()->log(get_class($field) . '::defineHtmlTag', 'Field `defineHtmlTag()` has been deprecated. Use `defineSlotTag()` instead.');

        /** @var HtmlTag|null $legacyTag */
        $legacyTag = $field->defineHtmlTag($key, $context->toArray());

        if (!$legacyTag) {
            return null;
        }

        $attributes = Html::mergeAttributes($legacyTag->attributes, $legacyTag->extraAttributes);

        if ($legacyTag->extraClasses) {
            $attributes = Html::mergeAttributes($attributes, [
                'class' => $legacyTag->extraClasses,
            ]);
        }

        $slotTag = SlotTag::make($legacyTag->tag)->core($attributes);
        $slotTag->prependContent = $legacyTag->prependContent;
        $slotTag->appendContent = $legacyTag->appendContent;

        return $slotTag;
    }


    // Properties
    // =========================================================================

    private static array $_calls = [];
    private static ?WeakMap $_notifications = null;
}
