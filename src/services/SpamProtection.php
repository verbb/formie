<?php
namespace verbb\formie\services;

use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\SpamProtectionSettings;
use verbb\formie\helpers\Table;
use verbb\formie\models\Settings;
use verbb\formie\records\SpamSettings as SpamSettingsRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;

use DateTime;
use Exception;
use Throwable;

class SpamProtection extends Component
{
    // Constants
    // =========================================================================

    public const CONFIG_SPAM_SETTINGS_KEY = 'formie.spamSettings';

    public const SETTING_KEYS = [
        'saveSpam',
        'spamLimit',
        'spamEmailNotifications',
        'spamBehaviour',
        'spamBehaviourMessage',
        'spamKeywords',
        'enableHoneypot',
        'honeypotFieldName',
        'enableMinimumSubmitTime',
        'minimumSubmitTime',
        'enableReplayProtection',
        'enableBlockedEmailDomains',
        'blockedEmailDomains',
        'enableBlockFreeEmailDomains',
        'enableAllowedEmailDomains',
        'allowedEmailDomains',
        'enableFormSubmitExpiration',
        'formSubmitExpiration',
        'enableSuspiciousTextDetection',
        'suspiciousTextAllowedTerms',
        'enableMaximumLinks',
        'maximumLinks',
        'enableGlobalSubmissionThrottling',
        'globalSubmissionThrottleLimit',
        'globalSubmissionThrottleWindowSeconds',
        'enableIpSubmissionThrottling',
        'ipSubmissionThrottleMinutes',
    ];


    // Properties
    // =========================================================================

    private ?array $_row = null;


    // Public Methods
    // =========================================================================

    public function getRow(): ?array
    {
        if ($this->_row !== null) {
            return $this->_row;
        }

        $select = [
            'id',
            'scope',
            'saveSpam',
            'spamLimit',
            'spamEmailNotifications',
            'spamBehaviour',
            'spamBehaviourMessage',
            'spamKeywords',
            'enableHoneypot',
            'honeypotFieldName',
            'enableMinimumSubmitTime',
            'minimumSubmitTime',
            'enableReplayProtection',
            'enableBlockedEmailDomains',
            'blockedEmailDomains',
            'enableBlockFreeEmailDomains',
            'enableAllowedEmailDomains',
            'allowedEmailDomains',
            'enableFormSubmitExpiration',
            'formSubmitExpiration',
            'enableSuspiciousTextDetection',
            'suspiciousTextAllowedTerms',
            'enableMaximumLinks',
            'maximumLinks',
            'enableGlobalSubmissionThrottling',
            'globalSubmissionThrottleLimit',
            'globalSubmissionThrottleWindowSeconds',
            'enableIpSubmissionThrottling',
            'ipSubmissionThrottleMinutes',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        $row = (new Query())
            ->select($select)
            ->from([Table::FORMIE_SPAM_SETTINGS])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        return $this->_row = $row ?: null;
    }

    public function getScope(): string
    {
        return Integrations::SCOPE_SITE;
    }

    public function isSiteScope(): bool
    {
        return $this->getScope() === Integrations::SCOPE_SITE;
    }

    public function isProjectScope(): bool
    {
        return $this->getScope() === Integrations::SCOPE_PROJECT;
    }

    public function getScopeLabel(): string
    {
        if ($this->isSiteScope()) {
            return '';
        }

        return Craft::t('formie', 'Project');
    }

    public function canEdit(): bool
    {
        return true;
    }

    public function hydrateSettings(Settings $settings): void
    {
        // Preserve legacy values until the settings migration has seeded their new store.
        if (!$this->getRow()) {
            return;
        }

        $values = $this->getSettingsValues();

        foreach (self::SETTING_KEYS as $key) {
            if (array_key_exists($key, $values)) {
                $settings->$key = $values[$key];
            }
        }
    }

    public function getSettingsValues(): array
    {
        $row = $this->getRow();

        if (!$row) {
            return $this->getDefaultValues();
        }

        return [
            'saveSpam' => (bool)$row['saveSpam'],
            'spamLimit' => (int)$row['spamLimit'],
            'spamEmailNotifications' => (bool)$row['spamEmailNotifications'],
            'spamBehaviour' => (string)$row['spamBehaviour'],
            'spamBehaviourMessage' => (string)$row['spamBehaviourMessage'],
            'spamKeywords' => (string)$row['spamKeywords'],
            ...$this->_guardValuesFromRow($row),
            ...$this->_extendedSpamValuesFromRow($row),
            ...$this->_emailAllowlistValuesFromRow($row),
            ...$this->_abuseControlValuesFromRow($row),
        ];
    }

    public function getDefaultValues(): array
    {
        return [
            'saveSpam' => true,
            'spamLimit' => 500,
            'spamEmailNotifications' => false,
            'spamBehaviour' => Settings::SPAM_BEHAVIOUR_SUCCESS,
            'spamBehaviourMessage' => '',
            'spamKeywords' => '',
            'enableHoneypot' => true,
            'honeypotFieldName' => 'formieHoneypot',
            'enableMinimumSubmitTime' => true,
            'minimumSubmitTime' => 3,
            'enableReplayProtection' => true,
            'enableBlockedEmailDomains' => false,
            'blockedEmailDomains' => '',
            'enableBlockFreeEmailDomains' => false,
            'enableAllowedEmailDomains' => false,
            'allowedEmailDomains' => '',
            'enableFormSubmitExpiration' => false,
            'formSubmitExpiration' => 86400,
            'enableSuspiciousTextDetection' => false,
            'suspiciousTextAllowedTerms' => '',
            'enableMaximumLinks' => false,
            'maximumLinks' => 10,
            'enableGlobalSubmissionThrottling' => false,
            'globalSubmissionThrottleLimit' => 50,
            'globalSubmissionThrottleWindowSeconds' => 60,
            'enableIpSubmissionThrottling' => false,
            'ipSubmissionThrottleMinutes' => 5,
        ];
    }

    public function saveFromSettings(Settings $settings): bool
    {
        $values = [];

        foreach (self::SETTING_KEYS as $key) {
            $values[$key] = $settings->$key;
        }

        if (!$this->saveValues($values)) {
            $settings->addError('saveSpam', Craft::t('formie', 'Couldn’t save spam rules. Check the entered values.'));

            return false;
        }

        return true;
    }

    public function saveValues(array $values): bool
    {
        return SpamProtectionSettings::atomic(function() use ($values) {
            SpamProtectionSettings::requireManager(SpamProtectionSettings::RULES);
            $this->resetCache();

            $row = $this->getRow();
            $isNew = $row === null;
            $transaction = Craft::$app->getDb()->beginTransaction();

            try {
                if ($isNew) {
                    $record = new SpamSettingsRecord();
                    $record->uid = StringHelper::UUID();
                } else {
                    $record = SpamSettingsRecord::findOne((int)$row['id']);

                    if (!$record) {
                        throw new Exception('Invalid spam settings ID: ' . $row['id']);
                    }
                }

                $record->scope = Integrations::SCOPE_SITE;
                $record->saveSpam = (bool)($values['saveSpam'] ?? true);
                $record->spamLimit = (int)($values['spamLimit'] ?? 500);
                $record->spamEmailNotifications = (bool)($values['spamEmailNotifications'] ?? false);
                $record->spamBehaviour = (string)($values['spamBehaviour'] ?? Settings::SPAM_BEHAVIOUR_SUCCESS);
                $record->spamBehaviourMessage = (string)($values['spamBehaviourMessage'] ?? '');
                $record->spamKeywords = (string)($values['spamKeywords'] ?? '');
                $this->_assignGuardValues($record, $values);
                $this->_assignExtendedSpamValues($record, $values);
                $this->_assignEmailAllowlistValues($record, $values);
                $this->_assignAbuseControlValues($record, $values);

                $record->save(false);

                $transaction->commit();
            } catch (Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }

            $this->resetCache();


            return true;

        });
    }

    public function seedFromLegacySettings(array $legacy = []): void
    {
        if ($this->getRow()) {
            return;
        }

        $defaults = $this->getDefaultValues();
        $values = [];

        foreach (self::SETTING_KEYS as $key) {
            $values[$key] = array_key_exists($key, $legacy) ? $legacy[$key] : $defaults[$key];
        }

        $now = Db::prepareDateForDb(new DateTime());
        $insert = [
            'scope' => Integrations::SCOPE_SITE,
            'saveSpam' => (bool)$values['saveSpam'],
            'spamLimit' => (int)$values['spamLimit'],
            'spamEmailNotifications' => (bool)$values['spamEmailNotifications'],
            'spamBehaviour' => (string)$values['spamBehaviour'],
            'spamBehaviourMessage' => (string)$values['spamBehaviourMessage'],
            'spamKeywords' => (string)$values['spamKeywords'],
            'enableHoneypot' => (bool)$values['enableHoneypot'],
            'honeypotFieldName' => (string)$values['honeypotFieldName'],
            'enableMinimumSubmitTime' => (bool)$values['enableMinimumSubmitTime'],
            'minimumSubmitTime' => (int)$values['minimumSubmitTime'],
            'enableReplayProtection' => (bool)$values['enableReplayProtection'],
            'enableBlockedEmailDomains' => (bool)$values['enableBlockedEmailDomains'],
            'blockedEmailDomains' => (string)$values['blockedEmailDomains'],
            'enableBlockFreeEmailDomains' => (bool)$values['enableBlockFreeEmailDomains'],
            'enableAllowedEmailDomains' => (bool)$values['enableAllowedEmailDomains'],
            'allowedEmailDomains' => (string)$values['allowedEmailDomains'],
            'enableFormSubmitExpiration' => (bool)$values['enableFormSubmitExpiration'],
            'formSubmitExpiration' => (int)$values['formSubmitExpiration'],
            ...$this->_abuseControlValuesFromArray($values),
            'dateCreated' => $now,
            'dateUpdated' => $now,
            'uid' => StringHelper::UUID(),
        ];

        Craft::$app->getDb()->createCommand()
            ->insert(Table::FORMIE_SPAM_SETTINGS, $insert)
            ->execute();

        $this->resetCache();

    }

    public function stripFromPluginSettingsArray(array $settings): array
    {
        foreach (self::SETTING_KEYS as $key) {
            unset($settings[$key]);
        }

        return $settings;
    }

    public function createSettingsConfig(array $values): array
    {
        return [
            'scope' => Integrations::SCOPE_SITE,
            'saveSpam' => (bool)($values['saveSpam'] ?? true),
            'spamLimit' => (int)($values['spamLimit'] ?? 500),
            'spamEmailNotifications' => (bool)($values['spamEmailNotifications'] ?? false),
            'spamBehaviour' => (string)($values['spamBehaviour'] ?? Settings::SPAM_BEHAVIOUR_SUCCESS),
            'spamBehaviourMessage' => (string)($values['spamBehaviourMessage'] ?? ''),
            'spamKeywords' => (string)($values['spamKeywords'] ?? ''),
            'enableHoneypot' => (bool)($values['enableHoneypot'] ?? true),
            'honeypotFieldName' => (string)($values['honeypotFieldName'] ?? 'formieHoneypot'),
            'enableMinimumSubmitTime' => (bool)($values['enableMinimumSubmitTime'] ?? true),
            'minimumSubmitTime' => (int)($values['minimumSubmitTime'] ?? 3),
            'enableReplayProtection' => (bool)($values['enableReplayProtection'] ?? true),
            'enableBlockedEmailDomains' => (bool)($values['enableBlockedEmailDomains'] ?? false),
            'blockedEmailDomains' => (string)($values['blockedEmailDomains'] ?? ''),
            'enableBlockFreeEmailDomains' => (bool)($values['enableBlockFreeEmailDomains'] ?? false),
            'enableAllowedEmailDomains' => (bool)($values['enableAllowedEmailDomains'] ?? false),
            'allowedEmailDomains' => (string)($values['allowedEmailDomains'] ?? ''),
            'enableFormSubmitExpiration' => (bool)($values['enableFormSubmitExpiration'] ?? false),
            'formSubmitExpiration' => (int)($values['formSubmitExpiration'] ?? 86400),
            ...$this->_abuseControlValuesFromArray($values, true),
        ];
    }

    /** @deprecated Spam rules are local; legacy project config is ignored. */
    public function handleChangedSettings(ConfigEvent $event): void
    {
    }

    /** @deprecated Removing legacy project config must not reset local rules. */
    public function handleDeletedSettings(ConfigEvent $event): void
    {
    }


    // Private Methods
    // =========================================================================

    public function resetCache(): void
    {
        $this->_row = null;
    }

    private function _guardValuesFromRow(array $row): array
    {
        return [
            'enableHoneypot' => (bool)($row['enableHoneypot'] ?? true),
            'honeypotFieldName' => (string)($row['honeypotFieldName'] ?? 'formieHoneypot'),
            'enableMinimumSubmitTime' => (bool)($row['enableMinimumSubmitTime'] ?? true),
            'minimumSubmitTime' => (int)($row['minimumSubmitTime'] ?? 3),
            'enableReplayProtection' => (bool)($row['enableReplayProtection'] ?? true),
        ];
    }

    private function _assignGuardValues(SpamSettingsRecord $record, array $values): void
    {
        $record->enableHoneypot = (bool)($values['enableHoneypot'] ?? true);
        $record->honeypotFieldName = (string)($values['honeypotFieldName'] ?? 'formieHoneypot');
        $record->enableMinimumSubmitTime = (bool)($values['enableMinimumSubmitTime'] ?? true);
        $record->minimumSubmitTime = (int)($values['minimumSubmitTime'] ?? 3);
        $record->enableReplayProtection = (bool)($values['enableReplayProtection'] ?? true);
    }

    private function _extendedSpamValuesFromRow(array $row): array
    {
        return [
            'enableBlockedEmailDomains' => (bool)($row['enableBlockedEmailDomains'] ?? false),
            'blockedEmailDomains' => (string)($row['blockedEmailDomains'] ?? ''),
            'enableBlockFreeEmailDomains' => (bool)($row['enableBlockFreeEmailDomains'] ?? false),
            'enableFormSubmitExpiration' => (bool)($row['enableFormSubmitExpiration'] ?? false),
            'formSubmitExpiration' => (int)($row['formSubmitExpiration'] ?? 86400),
        ];
    }

    private function _assignExtendedSpamValues(SpamSettingsRecord $record, array $values): void
    {
        $record->enableBlockedEmailDomains = (bool)($values['enableBlockedEmailDomains'] ?? false);
        $record->blockedEmailDomains = (string)($values['blockedEmailDomains'] ?? '');
        $record->enableBlockFreeEmailDomains = (bool)($values['enableBlockFreeEmailDomains'] ?? false);
        $record->enableFormSubmitExpiration = (bool)($values['enableFormSubmitExpiration'] ?? false);
        $record->formSubmitExpiration = (int)($values['formSubmitExpiration'] ?? 86400);
    }

    private function _emailAllowlistValuesFromRow(array $row): array
    {
        return [
            'enableAllowedEmailDomains' => (bool)($row['enableAllowedEmailDomains'] ?? false),
            'allowedEmailDomains' => (string)($row['allowedEmailDomains'] ?? ''),
        ];
    }

    private function _assignEmailAllowlistValues(SpamSettingsRecord $record, array $values): void
    {
        $record->enableAllowedEmailDomains = (bool)($values['enableAllowedEmailDomains'] ?? false);
        $record->allowedEmailDomains = (string)($values['allowedEmailDomains'] ?? '');
    }

    private function _abuseControlValuesFromRow(array $row): array
    {
        return $this->_abuseControlValuesFromArray($row);
    }

    private function _abuseControlValuesFromArray(array $values, bool $withDefaults = false): array
    {
        $defaults = $this->getDefaultValues();

        return [
            'enableSuspiciousTextDetection' => (bool)($values['enableSuspiciousTextDetection'] ?? ($withDefaults ? $defaults['enableSuspiciousTextDetection'] : false)),
            'suspiciousTextAllowedTerms' => (string)($values['suspiciousTextAllowedTerms'] ?? ($withDefaults ? $defaults['suspiciousTextAllowedTerms'] : '')),
            'enableMaximumLinks' => (bool)($values['enableMaximumLinks'] ?? ($withDefaults ? $defaults['enableMaximumLinks'] : false)),
            'maximumLinks' => (int)($values['maximumLinks'] ?? ($withDefaults ? $defaults['maximumLinks'] : 10)),
            'enableGlobalSubmissionThrottling' => (bool)($values['enableGlobalSubmissionThrottling'] ?? ($withDefaults ? $defaults['enableGlobalSubmissionThrottling'] : false)),
            'globalSubmissionThrottleLimit' => (int)($values['globalSubmissionThrottleLimit'] ?? ($withDefaults ? $defaults['globalSubmissionThrottleLimit'] : 50)),
            'globalSubmissionThrottleWindowSeconds' => (int)($values['globalSubmissionThrottleWindowSeconds'] ?? ($withDefaults ? $defaults['globalSubmissionThrottleWindowSeconds'] : 60)),
            'enableIpSubmissionThrottling' => (bool)($values['enableIpSubmissionThrottling'] ?? ($withDefaults ? $defaults['enableIpSubmissionThrottling'] : false)),
            'ipSubmissionThrottleMinutes' => (int)($values['ipSubmissionThrottleMinutes'] ?? ($withDefaults ? $defaults['ipSubmissionThrottleMinutes'] : 5)),
        ];
    }

    private function _assignAbuseControlValues(SpamSettingsRecord $record, array $values): void
    {
        $abuseValues = $this->_abuseControlValuesFromArray($values, true);
        $record->enableSuspiciousTextDetection = (bool)$abuseValues['enableSuspiciousTextDetection'];
        $record->suspiciousTextAllowedTerms = (string)$abuseValues['suspiciousTextAllowedTerms'];
        $record->enableMaximumLinks = (bool)$abuseValues['enableMaximumLinks'];
        $record->maximumLinks = (int)$abuseValues['maximumLinks'];
        $record->enableGlobalSubmissionThrottling = (bool)$abuseValues['enableGlobalSubmissionThrottling'];
        $record->globalSubmissionThrottleLimit = (int)$abuseValues['globalSubmissionThrottleLimit'];
        $record->globalSubmissionThrottleWindowSeconds = (int)$abuseValues['globalSubmissionThrottleWindowSeconds'];
        $record->enableIpSubmissionThrottling = (bool)$abuseValues['enableIpSubmissionThrottling'];
        $record->ipSubmissionThrottleMinutes = (int)$abuseValues['ipSubmissionThrottleMinutes'];
    }

}
