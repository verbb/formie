<?php
namespace verbb\formie\compatibility\fields;

use verbb\formie\base\Field;
use verbb\formie\Formie;
use verbb\formie\helpers\Html;
use verbb\formie\models\HtmlTag;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

use Craft;

class FieldCompatibility
{
    // Public Methods
    // =========================================================================

    public static function resolveLegacySchema(object $field, string $legacyMethod, string $newMethod): array
    {
        if (!Formie::$plugin->getCompatibility()->isCompatibilityModeEnabled()) {
            return [];
        }

        if (!method_exists($field, $legacyMethod)) {
            return [];
        }

        if ((new \ReflectionMethod($field, $legacyMethod))->getDeclaringClass()->getName() === Field::class) {
            return [];
        }

        Craft::$app->getDeprecator()->log(get_class($field) . '::' . $legacyMethod, "Field `{$legacyMethod}()` has been deprecated. Use `{$newMethod}()` instead.");

        return $field->{$legacyMethod}();
    }

    public static function renderLegacyHtmlTag(object $field, string $key, RenderContext $context): ?SlotTag
    {
        if (!Formie::$plugin->getCompatibility()->isCompatibilityModeEnabled()) {
            return null;
        }

        if (!method_exists($field, 'defineHtmlTag')) {
            return null;
        }

        if ((new \ReflectionMethod($field, 'defineHtmlTag'))->getDeclaringClass()->getName() === Field::class) {
            return null;
        }

        Craft::$app->getDeprecator()->log(get_class($field) . '::defineHtmlTag', 'Field `defineHtmlTag()` has been deprecated. Use `defineFieldSlotTag()` instead.');

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
}
