<?php
namespace verbb\formie\base;

use craft\helpers\Json;

trait FieldBrowserValidationTrait
{
    // Public Methods
    // =========================================================================

    public function browserValidationRules(): array
    {
        return array_values(array_filter(array_map(function(array $rule) {
            $type = (string)($rule['type'] ?? '');

            if ($type === '') {
                return null;
            }

            $definition = $rule;
            $params = array_intersect_key($rule, array_flip(['min', 'max', 'limit', 'value']));
            if ($type === 'match') {
                $params['value'] = $this->getForm()?->getFieldByHandle($this->getMatchField())?->label ?? '';
            }
            $keys = match ($type) {
                'number' => ['number', 'numberMin', 'numberMax'],
                'minmaxOptions' => ['minOptions', 'maxOptions'],
                default => [$type],
            };
            foreach ($keys as $key) {
                $definition['messages'][$key] = \verbb\formie\models\SubmissionErrors::plainText($this->getValidationMessage($key, $params));
            }
            return $definition;
        }, array_values($this->defineBrowserValidationRules()))));
    }

    public function getBrowserValidationRulesJson(): ?string
    {
        $rules = $this->browserValidationRules();

        if (!$rules) {
            return null;
        }

        return Json::encode($rules);
    }


    // Protected Methods
    // =========================================================================

    protected function defineBrowserValidationRules(): array
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
