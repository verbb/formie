<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\db\SoftDeleteTrait;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

use verbb\formie\elements\Submission;
use verbb\formie\records\Status as SubmissionStatusRecord;
use DateTime;

class Status extends Model
{
    // Traits
    // =========================================================================

    use SoftDeleteTrait {
        behaviors as softDeleteBehaviors;
    }


    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?string $name = null;
    public ?string $handle = null;
    public ?string $description = null;
    public string $color = 'green';
    public ?int $sortOrder = null;
    public ?bool $isDefault = null;
    public ?DateTime $dateDeleted = null;
    public ?string $uid = null;


    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getDisplayName();
    }

    /**
     * Gets the display name for the status.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        if ($this->dateDeleted !== null) {
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
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title'],
        ];
        $rules[] = [
            ['handle'],
            UniqueValidator::class,
            'targetClass' => SubmissionStatusRecord::class,
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

    /**
     * Returns the template’s config.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'name' => $this->name,
            'handle' => $this->handle,
            'color' => $this->color,
            'description' => $this->description,
            'sortOrder' => $this->sortOrder,
            'isDefault' => (bool)$this->isDefault,
        ];
    }
}
