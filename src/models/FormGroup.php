<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\db\SoftDeleteTrait;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

use verbb\formie\records\FormGroup as FormGroupRecord;

use verbb\formie\models\FormGroupSettings;

use DateTime;

class FormGroup extends Model
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
    public ?int $sortOrder = null;
    public ?DateTime $dateDeleted = null;
    public ?string $uid = null;

    // Optional group policy and defaults payload from project config.
    public ?array $settings = null;


    // Public Methods
    // =========================================================================

    public function __toString(): string
    {
        return $this->getDisplayName();
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('formie/settings/form-groups/edit/' . $this->id);
    }

    public function getDisplayName(): string
    {
        if ($this->dateDeleted !== null) {
            return $this->name . Craft::t('formie', ' (Trashed)');
        }

        return $this->name;
    }

    public function canDelete(): bool
    {
        return true;
    }

    public function getSettingsModel(): FormGroupSettings
    {
        return FormGroupSettings::fromArray($this->settings ?? []);
    }

    public function setSettingsModel(FormGroupSettings $settings): void
    {
        $this->settings = $settings->toStorageArray() ?: null;
    }

    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'sortOrder' => $this->sortOrder,
        ];

        if ($this->settings) {
            $config['settings'] = $this->settings;
        }

        return $config;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        
        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title', 'ungrouped'],
        ];

        $rules[] = [
            ['handle'],
            UniqueValidator::class,
            'targetClass' => FormGroupRecord::class,
        ];

        return $rules;
    }
}
