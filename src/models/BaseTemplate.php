<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use yii\behaviors\AttributeTypecastBehavior;

abstract class BaseTemplate extends Model
{
    use SoftDeleteTrait {
        behaviors as softDeleteBehaviors;
    }


    // Public Properties
    // =========================================================================

    public $id;
    public $name;
    public $handle;
    public $template;
    public $sortOrder;
    public $dateDeleted;
    public $uid;


    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getDisplayName();
    }

    /**
     * Gets the display name for the template.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        if ($this->dateDeleted !== null)
        {
            return $this->name . Craft::t('formie', ' (Trashed)');
        }

        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle', 'template'], 'string', 'max' => 255];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title'],
        ];
        $rules[] = [
            ['handle'],
            UniqueValidator::class,
            'targetClass' => $this->getRecordClass(),
        ];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function behaviors(): array
    {
        $behaviors = $this->softDeleteBehaviors();

        $behaviors['typecast'] = [
            'class' => AttributeTypecastBehavior::class,
            'attributeTypes' => [
                'id' => AttributeTypecastBehavior::TYPE_INTEGER,
                'name' => AttributeTypecastBehavior::TYPE_STRING,
                'handle' => AttributeTypecastBehavior::TYPE_STRING,
                'template' => AttributeTypecastBehavior::TYPE_STRING,
                'sortOrder' => AttributeTypecastBehavior::TYPE_INTEGER,
                'uid' => AttributeTypecastBehavior::TYPE_STRING,
            ]
        ];

        return $behaviors;
    }

    /**
     * Returns the class of the template active record.
     *
     * @return string
     */
    abstract protected function getRecordClass(): string;
}
