<?php
namespace verbb\formie\base;

use craft\helpers\Json;

trait FieldClientValidationTrait
{
    // Public Methods
    // =========================================================================

    public function validationRules(): array
    {
        return array_values(array_filter(array_map(function(array $rule) {
            $type = (string)($rule['type'] ?? '');

            if ($type === '') {
                return null;
            }

            $definition = ['type' => $type];

            if (array_key_exists('fieldId', $rule)) {
                $definition['fieldId'] = $rule['fieldId'];
            }

            if (array_key_exists('fieldHandle', $rule)) {
                $definition['fieldHandle'] = $rule['fieldHandle'];
            }

            if (array_key_exists('min', $rule)) {
                $definition['min'] = $rule['min'];
            }

            if (array_key_exists('max', $rule)) {
                $definition['max'] = $rule['max'];
            }

            return $definition;
        }, array_values($this->defineValidationRules()))));
    }

    public function getValidationRulesJson(): ?string
    {
        $rules = $this->validationRules();

        if (!$rules) {
            return null;
        }

        return Json::encode($rules);
    }


    // Protected Methods
    // =========================================================================

    protected function defineValidationRules(): array
    {
        $validators = [];

        if ($this->required) {
            $validators[] = ['type' => 'required'];
        }

        if ($matchField = $this->getMatchField()) {
            $validators[] = [
                'type' => 'match',
                'fieldHandle' => $matchField,
            ];
        }

        return $validators;
    }
}
