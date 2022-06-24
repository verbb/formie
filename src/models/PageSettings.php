<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

use yii\behaviors\AttributeTypecastBehavior;

class PageSettings extends Model
{
    // Public Properties
    // =========================================================================

    public $submitButtonLabel;
    public $backButtonLabel;
    public $showBackButton = false;
    public $buttonsPosition = 'left';
    public $cssClasses;
    public $containerAttributes;
    public $inputAttributes;
    public $enableNextButtonConditions = false;
    public $nextButtonConditions = [];
    public $enablePageConditions = false;
    public $pageConditions = [];
    public $enableJsEvents = false;
    public $jsGtmEventOptions = [];


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->submitButtonLabel) {
            $this->submitButtonLabel = Craft::t('formie', 'Submit');
        }

        if (!$this->backButtonLabel) {
            $this->backButtonLabel = Craft::t('formie', 'Back');
        }
    }

    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        return [
            'typecast' => [
                'class' => AttributeTypecastBehavior::class,
                'attributeTypes' => [
                    'submitButtonLabel' => AttributeTypecastBehavior::TYPE_STRING,
                    'backButtonLabel' => AttributeTypecastBehavior::TYPE_STRING,
                    'showBackButton' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                    'buttonsPosition' => AttributeTypecastBehavior::TYPE_STRING,
                    'cssClasses' => AttributeTypecastBehavior::TYPE_STRING,
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getContainerAttributes(): array
    {
        if (!$this->containerAttributes) {
            return [];
        }

        return ArrayHelper::map($this->containerAttributes, 'label', 'value');
    }

    /**
     * @inheritDoc
     */
    public function getInputAttributes(): array
    {
        if (!$this->inputAttributes) {
            return [];
        }

        return ArrayHelper::map($this->inputAttributes, 'label', 'value');
    }

    /**
     * @inheritDoc
     */
    public function getConditionsJson()
    {
        if ($this->enableNextButtonConditions) {
            $conditionSettings = Json::decode($this->nextButtonConditions) ?? [];
            $conditions = $conditionSettings['conditions'] ?? [];

            // Prep the conditions for JS
            foreach ($conditions as &$condition) {
                ArrayHelper::remove($condition, 'id');

                // Dot-notation to name input syntax
                $condition['field'] = 'fields[' . str_replace(['{', '}', '.'], ['', '', ']['], $condition['field']) . ']';
            }

            $conditionSettings['conditions'] = $conditions;

            return Json::encode($conditionSettings);
        }

        return null;
    }
}
