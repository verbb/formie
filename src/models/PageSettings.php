<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;

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


    // Public Methods
    // =========================================================================

    public function init()
    {
        if (!$this->submitButtonLabel) {
            $this->submitButtonLabel = Craft::t('formie', 'Submit');
        }

        if (!$this->backButtonLabel) {
            $this->backButtonLabel = Craft::t('formie', 'Back');
        }
    }

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
                ]
            ]
        ];
    }
}
