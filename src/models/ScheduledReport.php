<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\db\SoftDeleteTrait;
use craft\helpers\UrlHelper;
use craft\validators\UniqueValidator;

use verbb\formie\records\ScheduledReport as ScheduledReportRecord;

use DateTime;

class ScheduledReport extends Model
{
    // Traits
    // =========================================================================

    use SoftDeleteTrait {
        behaviors as softDeleteBehaviors;
    }


    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $reportId = null;
    public ?string $name = null;
    public bool $enabled = true;
    public ?DateTime $lastSentAt = null;
    public ?DateTime $dateDeleted = null;
    public ?string $uid = null;
    public ?array $delivery = null;


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

        return UrlHelper::cpUrl('formie/settings/scheduled-reports/edit/' . $this->id);
    }

    public function getDisplayName(): string
    {
        if ($this->dateDeleted !== null) {
            return $this->name . Craft::t('formie', ' (Trashed)');
        }

        return (string)$this->name;
    }

    public function getDeliveryModel(): ScheduledReportDelivery
    {
        return ScheduledReportDelivery::fromArray($this->delivery ?? []);
    }

    public function setDeliveryModel(ScheduledReportDelivery $delivery): void
    {
        $this->delivery = $delivery->toStorageArray();
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        $valid = parent::validate($attributeNames, $clearErrors);

        $delivery = $this->getDeliveryModel();

        if (!$delivery->validate()) {
            foreach ($delivery->getErrors() as $attribute => $errors) {
                foreach ($errors as $error) {
                    $this->addError('delivery.' . $attribute, $error);
                }
            }

            $valid = false;
        }

        return $valid;
    }

    public function getConfig(): array
    {
        $delivery = $this->getDeliveryModel();
        $config = [
            'reportUid' => $delivery->reportUid,
            'name' => $this->name,
            'enabled' => $this->enabled,
            'delivery' => $delivery->toStorageArray(),
        ];

        return $config;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'reportId'], 'required'];
        $rules[] = [['name'], 'string', 'max' => 255];
        $rules[] = [['enabled'], 'boolean'];
        $rules[] = [['reportId'], 'integer'];
        $rules[] = [
            ['name'],
            UniqueValidator::class,
            'targetClass' => ScheduledReportRecord::class,
            'filter' => ['dateDeleted' => null],
        ];

        return $rules;
    }
}
