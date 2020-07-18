<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\db\SoftDeleteTrait;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

use yii\behaviors\AttributeTypecastBehavior;

use verbb\formie\elements\Submission;
use verbb\formie\records\Status as SubmissionStatusRecord;

class Status extends Model
{
    // Traits
    // =========================================================================

    use SoftDeleteTrait {
        behaviors as softDeleteBehaviors;
    }


    // Public Properties
    // =========================================================================

    public $id;
    public $name;
    public $handle;
    public $description;
    public $color = 'green';
    public $sortOrder;
    public $isDefault;
    public $dateDeleted;
    public $uid;


    // Public Methods
    // =========================================================================

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
                'color' => AttributeTypecastBehavior::TYPE_STRING,
                'sortOrder' => AttributeTypecastBehavior::TYPE_INTEGER,
                'isDefault' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                'uid' => AttributeTypecastBehavior::TYPE_STRING,
            ]
        ];

        return $behaviors;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getDisplayName();
    }

    /**
     * Gets the display name for the status.
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
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']
        ];
        $rules[] = [
            ['handle'],
            UniqueValidator::class,
            'targetClass' => SubmissionStatusRecord::class
        ];

        return $rules;
    }

    /**
     * Returns the CP URL for editing the status.
     *
     * @return string
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/statuses/edit/' . $this->id);
    }

    /**
     * Gets the status HTML label.
     *
     * @return string
     */
    public function getLabelHtml(): string
    {
        return Html::tag('span', Html::tag('span', '', [
            'class' => ['status', $this->color],
        ]) . $this->getDisplayName(), [
            'class' => 'formieStatusLabel',
        ]);
    }

    /**
     * Returns true if the status is allowed to be deleted.
     *
     * @return bool
     */
    public function canDelete(): bool
    {
        return !$this->isDefault && !Submission::find()->trashed(null)->status($this)->one();
    }
}
