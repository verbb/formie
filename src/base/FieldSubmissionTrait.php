<?php
namespace verbb\formie\base;

use verbb\formie\helpers\Html;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\helpers\Template;

use Twig\Markup;

trait FieldSubmissionTrait
{
    // Public Methods
    // =========================================================================

    public function getSubmissionHtml(mixed $value, ?ElementInterface $element): Markup
    {
        $input = $this->defineSubmissionHtml($value, $element, false);
        $errors = $element ? $element->getErrors($this->handle) : '';

        // CP edit fields need stable module/validation selectors even when the host
        // UI chrome stays Craft-native, so expose the same field identity markers
        // the shared browser modules expect on the outer field wrapper.
        $field = Cp::fieldHtml($input, [
            'label' => $this->hasLabel() ? Craft::t('site', $this->label) : null,
            'attribute' => $this->handle,
            'required' => $this->required,
            'instructions' => Craft::t('site', $this->instructions),
            'id' => $this->handle,
            'errors' => $errors,
            'fieldAttributes' => [
                'data-type' => get_class($this),
                'data-formie-field-handle' => (string)$this->valueKey(),
                'data-formie-field-type' => static::kebabClassName(),
            ],
        ]);

        return Template::raw($field);
    }


    // Protected Methods
    // =========================================================================

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Html::textarea($this->handle, $value);
    }
}
