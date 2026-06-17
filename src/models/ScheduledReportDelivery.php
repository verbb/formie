<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;

class ScheduledReportDelivery extends Model
{
    // Static Methods
    // =========================================================================

    public static function fromArray(mixed $config): self
    {
        if (!is_array($config)) {
            return new self();
        }

        $delivery = new self();
        $defaults = self::defaults();

        foreach ($defaults as $key => $defaultValue) {
            if (array_key_exists($key, $config)) {
                $delivery->$key = $config[$key];
            } else {
                $delivery->$key = $defaultValue;
            }
        }

        return $delivery;
    }

    public static function defaults(): array
    {
        return [
            'reportUid' => null,
            'frequency' => 'weekly',
            'weekday' => 1,
            'hour' => 8,
            'recipients' => [],
            'recipientUserGroupId' => null,
            'startAt' => null,
            'endAt' => null,
            'emailSubject' => null,
            'emailMessage' => null,
            'templateId' => null,
            'format' => 'csv',
        ];
    }


    // Properties
    // =========================================================================

    public ?string $reportUid = null;
    public string $frequency = 'weekly';
    public int $weekday = 1;
    public int $hour = 8;
    public array $recipients = [];
    public ?int $recipientUserGroupId = null;
    public mixed $startAt = null;
    public mixed $endAt = null;
    public ?string $emailSubject = null;
    public ?string $emailMessage = null;
    public ?int $templateId = null;
    public string $format = 'csv';


    // Public Methods
    // =========================================================================

    public function toStorageArray(): array
    {
        $data = [];

        foreach (self::defaults() as $key => $defaultValue) {
            $value = $this->$key;

            if ($value === $defaultValue && in_array($key, ['recipients', 'emailSubject', 'emailMessage', 'startAt', 'endAt', 'recipientUserGroupId', 'reportUid', 'templateId'], true)) {
                if ($value === null || $value === [] || $value === '') {
                    continue;
                }
            }

            $data[$key] = $value;
        }

        return $data;
    }

    public function attributeLabels(): array
    {
        return [
            'recipients' => Craft::t('formie', 'Recipients'),
            'frequency' => Craft::t('formie', 'Frequency'),
            'weekday' => Craft::t('formie', 'Day of Week'),
            'hour' => Craft::t('formie', 'Hour'),
        ];
    }

    protected function defineRules(): array
    {
        return [
            [['frequency'], 'in', 'range' => ['daily', 'weekly']],
            [['hour'], 'integer', 'min' => 0, 'max' => 23],
            [['weekday'], 'integer', 'min' => 0, 'max' => 6],
            [['templateId'], 'integer'],
            [['format'], 'in', 'range' => ['csv', 'json', 'xml', 'text', 'xlsx']],
        ];
    }
}
