<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\elements\Submission;

use Craft;
use craft\base\Model;

use DateTime;

class SubscriptionPlan extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $integrationId = null;
    public ?string $accountFingerprint = null;
    public string $amountMinor = '0';
    public ?string $currency = null;
    public ?string $interval = null;
    public int $intervalCount = 1;
    public ?string $providerStatus = null;
    public ?string $name = null;
    public ?string $handle = null;
    public ?string $reference = null;
    public ?bool $enabled = null;
    public ?array $planData = null;
    public ?bool $isArchived = null;
    public ?DateTime $dateArchived = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;
    public ?string $uid = null;

    private ?IntegrationInterface $_integration = null;


    // Public Methods
    // =========================================================================

    public function getIntegration(): ?IntegrationInterface
    {
        if (!isset($this->_integration)) {
            $this->_integration = Formie::$plugin->getIntegrations()->getIntegrationById($this->integrationId);
        }

        return $this->_integration;
    }

    public function getFrequencySummary(): string
    {
        $interval = $this->interval;
        $intervalCount = $this->intervalCount;

        if ($intervalCount) {
            if ($interval === 'day') {
                return Craft::t('formie', 'every {num, number} {num, plural, =1{day} other{days}}', ['num' => $intervalCount]);
            }

            if ($interval === 'week') {
                return Craft::t('formie', 'every {num, number} {num, plural, =1{week} other{weeks}}', ['num' => $intervalCount]);
            }

            if ($interval === 'month') {
                return Craft::t('formie', 'every {num, number} {num, plural, =1{month} other{months}}', ['num' => $intervalCount]);
            }

            if ($interval === 'year') {
                return Craft::t('formie', 'every {num, number} {num, plural, =1{year} other{years}}', ['num' => $intervalCount]);
            }
        }

        return '';
    }
}
