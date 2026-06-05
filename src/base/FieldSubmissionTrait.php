<?php
namespace verbb\formie\base;

use verbb\formie\elements\Submission;
use verbb\formie\helpers\CpSubmissionFieldConditions;
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
        $fieldAttributes = [
            'data-type' => get_class($this),
            'data-formie-field-handle' => (string)$this->valueKey(),
            'data-formie-field-type' => static::kebabClassName(),
        ];

        $cpFieldMeta = $this->_getCpSubmissionFieldMeta($element);
        $fieldAttributes = array_merge($fieldAttributes, $cpFieldMeta['attributes']);

        $fieldConfig = [
            'label' => $this->hasLabel() ? Craft::t('site', $this->label) : null,
            'attribute' => $this->handle,
            'required' => $this->required,
            'instructions' => Craft::t('site', $this->instructions),
            'id' => $this->handle,
            'errors' => $errors,
            'fieldAttributes' => $fieldAttributes,
        ];

        if ($cpFieldMeta['fieldClass'] !== []) {
            $fieldConfig['fieldClass'] = $cpFieldMeta['fieldClass'];
        }

        $field = Cp::fieldHtml($input, $fieldConfig);

        return Template::raw($field);
    }


    // Protected Methods
    // =========================================================================

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Html::textarea($this->handle, $value);
    }

    protected function _getCpSubmissionFieldMeta(?ElementInterface $element): array
    {
        $meta = [
            'attributes' => [],
            'fieldClass' => [],
        ];

        if (!Craft::$app->getRequest()->getIsCpRequest() || !($element instanceof Submission)) {
            return $meta;
        }

        $form = $element->getForm();

        if (!$form || !$form->cpSubmissionFollowsFieldConditions()) {
            return $meta;
        }

        $meta['attributes'] = [
            'data-formie-field' => true,
        ];

        if ($this->enableConditions && $this->getConditionsJson()) {
            $meta['attributes']['data-formie-conditions'] = $this->getConditionsJson();
        }

        if (!$this->isConditionallyHidden($element)) {
            return $meta;
        }

        $mode = $form->getCpSubmissionFieldConditions();

        if ($mode === CpSubmissionFieldConditions::MUTED) {
            $meta['attributes']['data-formie-cp-muted'] = true;
            $meta['fieldClass'] = ['fui-cp-muted-conditional-field'];
        } else {
            $meta['attributes']['data-formie-conditionally-hidden'] = true;
            $meta['fieldClass'] = ['formie-conditionally-hidden'];
        }

        return $meta;
    }
}
