<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Settings;
use verbb\formie\records\SpamSettings as SpamSettingsRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\ProjectConfig;

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

        if (!Craft::$app->getDb()->tableExists(Table::FORMIE_SPAM_SETTINGS)) {
            return null;
        }

        $row = (new Query())
            ->select([
                'id',
                'scope',
                'saveSpam',
                'spamLimit',
                'spamEmailNotifications',
                'spamBehaviour',
                'spamBehaviourMessage',
                'spamKeywords',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->from([Table::FORMIE_SPAM_SETTINGS])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        return $this->_row = $row ?: null;
    }

    public function getScope(): string
    {
        $row = $this->getRow();

        if (!$row) {
            return Integrations::SCOPE_PROJECT;
        }

        $scope = $row['scope'] ?? Integrations::SCOPE_PROJECT;

        return in_array($scope, [Integrations::SCOPE_PROJECT, Integrations::SCOPE_SITE], true)
            ? $scope
            : Integrations::SCOPE_PROJECT;
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
        if ($this->isSiteScope()) {
            return true;
        }

        return (bool)Craft::$app->getConfig()->getGeneral()->allowAdminChanges;
    }

    public function hydrateSettings(Settings $settings): void
    {
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
        ];
    }

    public function saveFromSettings(Settings $settings): bool
    {
        $values = [];

        foreach (self::SETTING_KEYS as $key) {
            $values[$key] = $settings->$key;
        }

        if (!$this->saveValues($values)) {
            $settings->addError('saveSpam', Craft::t('formie', 'Spam settings cannot be edited in the current environment.'));

            return false;
        }

        return true;
    }

    public function saveValues(array $values): bool
    {
        if (!$this->canEdit()) {
            if ($this->isProjectScope() && !Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
                $values['scope'] = Integrations::SCOPE_SITE;
            } else {
                return false;
            }
        }

        $row = $this->getRow();
        $isNew = $row === null;

        if (array_key_exists('scope', $values)) {
            $requestedScope = $values['scope'];
            $scope = in_array($requestedScope, [Integrations::SCOPE_PROJECT, Integrations::SCOPE_SITE], true)
                ? $requestedScope
                : ($isNew ? Integrations::resolveScopeForNew(null) : $this->getScope());
        } elseif ($isNew) {
            $scope = Integrations::resolveScopeForNew(null);
        } else {
            $scope = $this->getScope();
        }

        if ($scope === Integrations::SCOPE_PROJECT && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            return $this->_saveProjectSettings($values);
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            if ($isNew) {
                $record = new SpamSettingsRecord();
                $record->uid = StringHelper::UUID();
            } else {
                $record = SpamSettingsRecord::findOne((int)$row['id']);

                if (!$record) {
                    throw new \Exception('Invalid spam settings ID: ' . $row['id']);
                }
            }

            $record->scope = $scope;
            $record->saveSpam = (bool)($values['saveSpam'] ?? true);
            $record->spamLimit = (int)($values['spamLimit'] ?? 500);
            $record->spamEmailNotifications = (bool)($values['spamEmailNotifications'] ?? false);
            $record->spamBehaviour = (string)($values['spamBehaviour'] ?? Settings::SPAM_BEHAVIOUR_SUCCESS);
            $record->spamBehaviourMessage = (string)($values['spamBehaviourMessage'] ?? '');
            $record->spamKeywords = (string)($values['spamKeywords'] ?? '');

            $record->save(false);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->_resetCache();

        return true;
    }

    public function seedFromLegacySettings(array $legacy = []): void
    {
        if (!Craft::$app->getDb()->tableExists(Table::FORMIE_SPAM_SETTINGS) || $this->getRow()) {
            return;
        }

        $defaults = $this->getDefaultValues();
        $values = [];

        foreach (self::SETTING_KEYS as $key) {
            $values[$key] = array_key_exists($key, $legacy) ? $legacy[$key] : $defaults[$key];
        }

        $now = Db::prepareDateForDb(new \DateTime());

        Craft::$app->getDb()->createCommand()
            ->insert(Table::FORMIE_SPAM_SETTINGS, [
                'scope' => Integrations::SCOPE_PROJECT,
                'saveSpam' => (bool)$values['saveSpam'],
                'spamLimit' => (int)$values['spamLimit'],
                'spamEmailNotifications' => (bool)$values['spamEmailNotifications'],
                'spamBehaviour' => (string)$values['spamBehaviour'],
                'spamBehaviourMessage' => (string)$values['spamBehaviourMessage'],
                'spamKeywords' => (string)$values['spamKeywords'],
                'dateCreated' => $now,
                'dateUpdated' => $now,
                'uid' => StringHelper::UUID(),
            ])
            ->execute();

        $this->_resetCache();
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
            'scope' => Integrations::SCOPE_PROJECT,
            'saveSpam' => (bool)($values['saveSpam'] ?? true),
            'spamLimit' => (int)($values['spamLimit'] ?? 500),
            'spamEmailNotifications' => (bool)($values['spamEmailNotifications'] ?? false),
            'spamBehaviour' => (string)($values['spamBehaviour'] ?? Settings::SPAM_BEHAVIOUR_SUCCESS),
            'spamBehaviourMessage' => (string)($values['spamBehaviourMessage'] ?? ''),
            'spamKeywords' => (string)($values['spamKeywords'] ?? ''),
        ];
    }

    public function handleChangedSettings(ConfigEvent $event): void
    {
        $data = $event->newValue;
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
                    throw new \Exception('Invalid spam settings ID: ' . $row['id']);
                }
            }

            $record->scope = Integrations::SCOPE_PROJECT;
            $record->saveSpam = (bool)($data['saveSpam'] ?? true);
            $record->spamLimit = (int)($data['spamLimit'] ?? 500);
            $record->spamEmailNotifications = (bool)($data['spamEmailNotifications'] ?? false);
            $record->spamBehaviour = (string)($data['spamBehaviour'] ?? Settings::SPAM_BEHAVIOUR_SUCCESS);
            $record->spamBehaviourMessage = (string)($data['spamBehaviourMessage'] ?? '');
            $record->spamKeywords = (string)($data['spamKeywords'] ?? '');
            $record->save(false);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->_resetCache();
    }

    public function handleDeletedSettings(ConfigEvent $event): void
    {
        $row = $this->getRow();

        if (!$row) {
            return;
        }

        $record = SpamSettingsRecord::findOne((int)$row['id']);

        if (!$record) {
            return;
        }

        $defaults = $this->getDefaultValues();
        $record->scope = Integrations::SCOPE_PROJECT;
        $record->saveSpam = (bool)$defaults['saveSpam'];
        $record->spamLimit = (int)$defaults['spamLimit'];
        $record->spamEmailNotifications = (bool)$defaults['spamEmailNotifications'];
        $record->spamBehaviour = (string)$defaults['spamBehaviour'];
        $record->spamBehaviourMessage = (string)$defaults['spamBehaviourMessage'];
        $record->spamKeywords = (string)$defaults['spamKeywords'];
        $record->save(false);

        $this->_resetCache();
    }


    // Private Methods
    // =========================================================================

    private function _resetCache(): void
    {
        $this->_row = null;
    }

    private function _saveProjectSettings(array $values): bool
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            return false;
        }

        Craft::$app->getProjectConfig()->set(
            self::CONFIG_SPAM_SETTINGS_KEY,
            $this->createSettingsConfig($values),
            'Save Formie spam settings',
        );

        $scope = Integrations::SCOPE_PROJECT;
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
                    throw new \Exception('Invalid spam settings ID: ' . $row['id']);
                }
            }

            $record->scope = $scope;
            $record->saveSpam = (bool)($values['saveSpam'] ?? true);
            $record->spamLimit = (int)($values['spamLimit'] ?? 500);
            $record->spamEmailNotifications = (bool)($values['spamEmailNotifications'] ?? false);
            $record->spamBehaviour = (string)($values['spamBehaviour'] ?? Settings::SPAM_BEHAVIOUR_SUCCESS);
            $record->spamBehaviourMessage = (string)($values['spamBehaviourMessage'] ?? '');
            $record->spamKeywords = (string)($values['spamKeywords'] ?? '');
            $record->save(false);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->_resetCache();

        return true;
    }
}
