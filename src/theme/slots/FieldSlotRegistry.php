<?php
namespace verbb\formie\theme\slots;

use verbb\formie\base\FieldInterface;
use verbb\formie\Formie;
use verbb\formie\helpers\Html;
use verbb\formie\models\Settings;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

use yii\base\Component;

class FieldSlotRegistry extends Component
{
    // Public Methods
    // =========================================================================

    public function resolve(string $key, RenderContext $context): ?SlotTag
    {
        return match ($key) {
            'field' => $this->_field($context),
            'fieldLayout' => $this->_fieldLayout($context),
            'fieldLabel' => $this->_fieldLabel($context),
            'fieldRequired' => $this->_fieldRequired($context),
            'fieldOptional' => $this->_fieldOptional($context),
            'fieldInstructions' => $this->_fieldInstructions($context),
            'fieldContent' => $this->_fieldContent($context),
            'fieldControl' => $this->_fieldControl($context),
            'fieldErrors' => $this->_fieldErrors($context),
            'fieldError' => $this->_fieldError($context),
            'subFieldRows' => $this->_subFieldRows($context),
            'subFieldRow' => $this->_subFieldRow($context),
            'nestedFieldRows' => $this->_nestedFieldRows($context),
            'nestedFieldRow' => $this->_nestedFieldRow($context),
            default => null,
        };
    }


    // Private Methods
    // =========================================================================

    private function _field(RenderContext $context): SlotTag
    {
        $field = $context->field;
        $validation = $field?->getValidationRulesJson();
        $isConditionallyHidden = $context->fieldIsConditionallyHidden();

        return SlotTag::make('div')
            ->core([
                'data-formie-field' => true,
                'data-formie-field-handle' => $field?->valueKey(),
                'data-formie-field-uid' => $field?->uid,
                'data-formie-field-type' => $field?->kebabClassName(),
                'data-formie-input-id' => $context->inputId(),
                'data-formie-field-has-error' => $context->hasErrors() ? true : false,
                'data-formie-conditions' => $field?->getConditionsJson(),
                'data-formie-conditionally-hidden' => $isConditionallyHidden ? true : false,
                'data-formie-validation' => $validation,
            ])
            ->theme([
                'class' => [
                    'formie-field',
                    $context->hasErrors() ? 'formie-field-has-error' : false,
                    $isConditionallyHidden ? 'formie-conditionally-hidden' : false,
                ],
            ])
            ->instanceAttributes(Html::mergeAttributes(
                $field?->getContainerAttributes() ?? [],
                ['class' => [$field?->cssClasses]]
            ));
    }

    private function _fieldLayout(RenderContext $context): SlotTag
    {
        $field = $context->field;
        $labelPosition = $this->_resolvePositionValue($field->labelPosition ?? $context->get('labelPosition'));
        $instructionsPosition = $this->_resolvePositionValue($field->instructionsPosition ?? $context->get('instructionsPosition'));
        $errorMessagePosition = $this->_resolveErrorMessagePosition($context);

        return SlotTag::make('div')
            ->core([
                'data-formie-field-layout' => true,
                'data-formie-label-position' => $labelPosition,
                'data-formie-instructions-position' => $instructionsPosition,
                'data-formie-error-position' => $errorMessagePosition,
            ])
            ->theme([
                'class' => [
                    'formie-field-layout',
                    $labelPosition ? "formie-field-layout-label-{$labelPosition}" : false,
                    $instructionsPosition ? "formie-field-layout-instructions-{$instructionsPosition}" : false,
                    $errorMessagePosition ? "formie-field-layout-errors-{$errorMessagePosition}" : false,
                ],
            ]);
    }

    private function _fieldLabel(RenderContext $context): ?SlotTag
    {
        $field = $context->field;
        $labelPosition = $this->_resolvePositionValue($field->labelPosition ?? $context->get('labelPosition'));

        if (!$field || !$field->hasLabel()) {
            return null;
        }

        $usesQuestionLabel = method_exists($field, 'usesQuestionLabel') && $field->usesQuestionLabel();

        if ($usesQuestionLabel && $labelPosition === 'hidden') {
            $labelPosition = 'above';
        }

        return SlotTag::make('label')
            ->core([
                'data-formie-label' => true,
                'data-formie-field-label' => true,
                'data-formie-label-position' => $labelPosition,
                'data-formie-sr-only' => !$usesQuestionLabel && $labelPosition === 'hidden' ? true : false,
                'for' => $context->inputId(),
            ])
            ->theme([
                'class' => [
                    'formie-label',
                    'formie-field-label',
                    !$usesQuestionLabel && $labelPosition === 'hidden' ? 'formie-sr-only' : false,
                ],
            ]);
    }

    private function _fieldRequired(RenderContext $context): SlotTag
    {
        return SlotTag::make('span')
            ->core([
                'data-formie-field-required' => true,
                'aria-hidden' => 'true',
            ])
            ->theme([
                'class' => [
                    'formie-field-required',
                ],
            ]);
    }

    private function _fieldOptional(RenderContext $context): SlotTag
    {
        return SlotTag::make('span')
            ->core([
                'data-formie-field-optional' => true,
            ])
            ->theme([
                'class' => [
                    'formie-field-note',
                    'formie-field-optional',
                ],
            ]);
    }

    private function _fieldInstructions(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'id' => $context->instructionsId(),
                'data-formie-instructions' => true,
            ])
            ->theme([
                'class' => [
                    'formie-instructions',
                ],
            ]);
    }

    private function _fieldContent(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-field-content' => true,
            ])
            ->theme([
                'class' => [
                    'formie-field-content',
                ],
            ]);
    }

    private function _fieldControl(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-field-control' => true,
            ])
            ->theme([
                'class' => [
                    'formie-field-control',
                ],
            ]);
    }

    private function _fieldErrors(RenderContext $context): SlotTag
    {
        $errorAriaLive = Formie::$plugin->getSettings()->errorAriaLive;
        $core = [
            'id' => $context->errorsId(),
            'data-formie-field-errors' => true,
        ];

        if ($errorAriaLive !== Settings::ERROR_ARIA_LIVE_OFF) {
            $core['aria-live'] = $errorAriaLive;
            $core['aria-atomic'] = true;
        }

        return SlotTag::make('div')
            ->core($core)
            ->theme([
                'class' => [
                    'formie-field-errors',
                ],
            ]);
    }

    private function _fieldError(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-field-error' => true,
                'role' => 'alert',
            ])
            ->theme([
                'class' => [
                    'formie-field-error',
                ],
            ]);
    }

    private function _subFieldRows(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-subfield-rows' => true,
            ])
            ->theme([
                'class' => [
                    'formie-subfield-rows',
                ],
            ]);
    }

    private function _subFieldRow(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-subfield-row' => true,
            ])
            ->theme([
                'class' => [
                    'formie-subfield-row',
                ],
            ]);
    }

    private function _nestedFieldRows(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-nested-field-rows' => true,
            ])
            ->theme([
                'class' => [
                    'formie-nested-field-rows',
                ],
            ]);
    }

    private function _nestedFieldRow(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-nested-field-row' => true,
            ])
            ->theme([
                'class' => [
                    'formie-nested-field-row',
                ],
            ]);
    }

    private function _resolvePositionValue(mixed $position): ?string
    {
        if ($position === null || $position === '') {
            return null;
        }

        $aliases = [
            'before' => 'left',
            'after' => 'right',
        ];

        $candidates = ['above', 'below', 'left', 'right', 'hidden'];

        if (is_object($position) && method_exists($position, 'shouldDisplay')) {
            foreach (['above', 'below', 'left', 'right'] as $candidate) {
                if ($position->shouldDisplay($candidate)) {
                    return $candidate;
                }
            }
        }

        $value = is_object($position) ? get_class($position) : (string)$position;
        $value = strtolower($value);

        if (isset($aliases[$value])) {
            return $aliases[$value];
        }

        foreach ($candidates as $candidate) {
            if (str_contains($value, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function _resolveErrorMessagePosition(RenderContext $context): ?string
    {
        $field = $context->field;

        if (!$field) {
            return null;
        }

        $positionClass = $field->errorMessagePosition
            ?: $context->form?->settings->defaultErrorMessagePosition
            ?: Formie::$plugin->getSettings()->defaultErrorMessagePosition;

        return $this->_resolvePositionValue($positionClass);
    }
}
