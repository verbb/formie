<?php
namespace verbb\formie\fields\subfields;

use verbb\formie\Formie;
use verbb\formie\base\ChildFieldInterface;
use verbb\formie\base\Integration;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;
use verbb\formie\fields\SingleLineText;
use verbb\formie\fields\subfields\traits\DateSubFieldValueTrait;
use verbb\formie\helpers\SchemaHelper;

use Craft;

class DateTime extends SingleLineText implements ChildFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Date - Time');
    }

    public static function getInputTemplatePath(): string
    {
        return 'fields/single-line-text';
    }

    public static function getReferenceBlockTemplatePath(): string
    {
        return 'fields/single-line-text';
    }

    
    // Traits
    // =========================================================================
    
    use DateSubFieldValueTrait;
    
    
    // Protected Methods
    // =========================================================================

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        $tag = parent::defineFieldSlotTag($key, $context);

        if ($key === 'fieldInput' && $this->getParentField()?->getDisplayType() === 'datePicker') {
            $attributes = array_filter(
                $this->getInputAttributes(),
                fn($value, $attribute) => $attribute !== 'type',
                ARRAY_FILTER_USE_BOTH,
            );

            $tag?->mergeCoreAttributes(['type' => 'text']);
            $tag?->mergeInstanceAttributes($attributes);
            $tag?->mergeInstanceAttributes([
                'data-formie-date-datepicker-input' => true,
            ]);
        }

        return $tag;
    }

    protected function defineClientInput(): array
    {
        return array_merge(parent::defineClientInput(), [
            'inputType' => 'time',
        ]);
    }
}
