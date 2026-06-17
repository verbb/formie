<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\db\SoftDeleteTrait;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

use verbb\formie\records\Report as ReportRecord;

use DateTime;

class Report extends Model
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
    public ?array $settings = null;


    // Public Methods
    // =========================================================================

    public function __toString(): string
    {
        return $this->getDisplayName();
    }

    public function getCpEditUrl(): ?string
    {
        if (!$this->id) {
            return null;
        }

        return UrlHelper::cpUrl('formie/reports/edit/' . $this->id);
    }

    public function getCpRunUrl(): ?string
    {
        if (!$this->id) {
            return null;
        }

        return UrlHelper::cpUrl('formie/reports/' . $this->handle);
    }

    public function getDisplayName(): string
    {
        if ($this->dateDeleted !== null) {
            return $this->name . Craft::t('formie', ' (Trashed)');
        }

        return (string)$this->name;
    }

    public function getSettingsModel(): ReportSettings
    {
        return ReportSettings::fromArray($this->settings ?? []);
    }

    public function setSettingsModel(ReportSettings $settings): void
    {
        $this->settings = $settings->toStorageArray();
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
        $rules[] = [['name'], 'string', 'max' => 255];
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['new', 'index', 'view', 'edit', 'export', 'scheduled']];
        $rules[] = [
            ['handle'],
            UniqueValidator::class,
            'targetClass' => ReportRecord::class,
            'filter' => ['dateDeleted' => null],
        ];

        return $rules;
    }
}
