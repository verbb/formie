<?php
namespace verbb\formie\elements\conditions;

use verbb\formie\Formie;

use Craft;
use craft\elements\conditions\ElementCondition;
use craft\errors\InvalidTypeException;
use craft\elements\conditions\DateCreatedConditionRule;
use craft\elements\conditions\DateUpdatedConditionRule;
use craft\elements\conditions\IdConditionRule;
use craft\elements\conditions\StatusConditionRule;
use craft\elements\conditions\TitleConditionRule;
use verbb\formie\fields\conditions\FieldConditionRuleInterface;

class SubmissionCondition extends ElementCondition
{
    // Properties
    // =========================================================================

    private array $_fields = [];


    // Public Methods
    // =========================================================================

    public function getFields(): array
    {
        if ($this->_fields) {
            return $this->_fields;
        }

        if ($this->sourceKey) {
            if (preg_match('/^form:(\d+)$/', $this->sourceKey, $matches) && ($fields = Formie::$plugin->getFields()->getAllFieldsForForm($matches[1]))) {
                return $this->_fields = $fields;
            }
        }

        return [];
    }

    
    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['elementType', 'sourceKey'], 'safe'];
        
        return $rules;
    }

    protected function config(): array
    {
        return $this->toArray(['elementType', 'sourceKey']);
    }

    protected function selectableConditionRules(): array
    {
        $types = [
            DateCreatedConditionRule::class,
            DateUpdatedConditionRule::class,
            IdConditionRule::class,
            StatusConditionRule::class,
            TitleConditionRule::class,
        ];

        foreach ($this->getFields() as $field) {
            $type = $field->getElementConditionRuleType();

            if ($type === null) {
                continue;
            }

            if (is_string($type)) {
                $type = ['class' => $type];
            }

            if (!is_subclass_of($type['class'], FieldConditionRuleInterface::class)) {
                throw new InvalidTypeException($type['class'], FieldConditionRuleInterface::class);
            }

            $type['fieldUid'] = $field->uid;

            $types[] = $type;
        }

        return $types;
    }
}
