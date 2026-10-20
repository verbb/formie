<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\events\IntegrationEvent;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\SpamProtectionSettings;
use verbb\formie\helpers\Table;
use verbb\formie\records\CaptchaProvider as CaptchaProviderRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\App;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;

use DateTime;
use Exception;
use Throwable;

class CaptchaProviders extends Component
{
    // Constants
    // =========================================================================

    public const CONFIG_CAPTCHA_PROVIDERS_KEY = 'formie.captchaProviders';


    // Properties
    // =========================================================================

    private ?array $_providersByHandle = null;


    // Public Methods
    // =========================================================================

    public function getProviderRowByHandle(string $handle): ?array
    {
        $handle = trim($handle);

        if ($handle === '') {
            return null;
        }

        return $this->_getProvidersByHandle()[$handle] ?? null;
    }

    public function getIntegrationConfigForClass(string $captchaClass): array
    {
        $class = new $captchaClass();
        $handle = $class->getHandle();
        $row = $this->getProviderRowByHandle($handle);

        if ($row) {
            $config = $this->_rowToIntegrationConfig($row);
        } else {
            $config = [
                'handle' => $handle,
                'type' => $captchaClass,
                'scope' => Integrations::SCOPE_SITE,
                'enabled' => false,
                'settings' => [],
            ];
        }

        return $config;
    }

    /** @deprecated Providers are always local; no providers are exported to project config. */
    public function getProjectScopedProviders(): array
    {
        return [];
    }

    public function saveProvider(IntegrationInterface $integration): bool
    {
        return SpamProtectionSettings::atomic(function() use ($integration) {
            SpamProtectionSettings::requireManager('captcha:' . $integration->getHandle());
            $this->resetCache();

            $handle = $integration->getHandle();
            $row = $this->getProviderRowByHandle($handle);
            $integration->scope = Integrations::SCOPE_SITE;

            $integrations = Formie::$plugin->getIntegrations();

            if ($integrations->hasEventHandlers(Integrations::EVENT_BEFORE_SAVE_INTEGRATION)) {
                $integrations->trigger(Integrations::EVENT_BEFORE_SAVE_INTEGRATION, new IntegrationEvent([
                    'integration' => $integration,
                ]));
            }

            if (!$integration->beforeSave(false)) {
                return false;
            }

            $integration->scope = Integrations::SCOPE_SITE;

            if ($integration->getHandle() !== $handle) {
                $integration->addError('handle', Craft::t('formie', 'A CAPTCHA provider’s handle cannot be changed.'));
                return false;
            }

            if (!$integration->validate(array_keys($integration->getSettings()))) {
                return false;
            }

            $isNew = $row === null;

            if ($isNew) {
                $integration->scope = Integrations::SCOPE_SITE;
                $integration->uid = $integration->uid ?: StringHelper::UUID();
            } else {
                $integration->id = (int)$row['id'];
                $integration->uid = $integration->uid ?: $row['uid'];
            }

            if (!$this->_saveProviderRow($integration, $isNew)) {
                return false;
            }

            SpamProtectionSettings::afterCommit(function() use ($integrations, $integration) {
                if ($integrations->hasEventHandlers(Integrations::EVENT_AFTER_SAVE_INTEGRATION)) {
                    $integrations->trigger(Integrations::EVENT_AFTER_SAVE_INTEGRATION, new IntegrationEvent(['integration' => $integration]));
                }
                $integration->afterSave(false);
            });

            $this->resetCache();
            $integrations->resetCaptchaCaches();

            return true;

        });
    }

    public function seedRegistryFromLegacySettings(array $legacyCaptchas = []): void
    {
        $integrations = Formie::$plugin->getIntegrations();
        $now = Db::prepareDateForDb(new DateTime());
        $providerTypes = [];

        foreach ($integrations->getIntegrationTypes(Integration::TYPE_CAPTCHA) as $captchaClass) {
            $class = new $captchaClass();
            $providerTypes[$class->getHandle()] = $captchaClass;
        }

        // Retain unregistered legacy providers without loading their unavailable classes.
        foreach ($legacyCaptchas as $handle => $legacy) {
            $providerTypes[$handle] ??= $legacy['type'] ?? '';
        }

        foreach ($providerTypes as $handle => $captchaClass) {
            if ($this->getProviderRowByHandle($handle)) {
                continue;
            }

            $legacy = $legacyCaptchas[$handle] ?? [];
            $settings = $legacy['settings'] ?? [];

            Craft::$app->getDb()->createCommand()
                ->insert(Table::FORMIE_CAPTCHA_PROVIDERS, [
                    'handle' => $handle,
                    'type' => $legacy['type'] ?? $captchaClass,
                    'scope' => Integrations::SCOPE_SITE,
                    'enabled' => $this->_normalizeEnabledValue($legacy['enabled'] ?? false),
                    'saveSpam' => array_key_exists('saveSpam', $legacy) ? (bool)$legacy['saveSpam'] : null,
                    'settings' => Json::encode($settings),
                    'dateCreated' => $now,
                    'dateUpdated' => $now,
                    'uid' => StringHelper::UUID(),
                ])
                ->execute();

        }

        $this->resetCache();
    }

    public function createProviderConfig(IntegrationInterface $integration): array
    {
        $config = [
            'handle' => $integration->getHandle(),
            'type' => get_class($integration),
            'enabled' => $this->_normalizeEnabledValue($integration->getEnabled(false)),
            'settings' => ProjectConfig::packAssociativeArrays($integration->getSettings()),
        ];

        if ($integration->saveSpam !== null) {
            $config['saveSpam'] = (bool)$integration->saveSpam;
        }

        return $config;
    }

    /** @deprecated Provider settings are local; legacy project config is ignored. */
    public function handleChangedProvider(ConfigEvent $event): void
    {
    }

    /** @deprecated Removing legacy project config must not clear local credentials. */
    public function handleDeletedProvider(ConfigEvent $event): void
    {
    }

    public function stripFromPluginSettingsArray(array $settings): array
    {
        unset($settings['captchas']);

        return $settings;
    }


    // Private Methods
    // =========================================================================

    private function _getProvidersByHandle(): array
    {
        if ($this->_providersByHandle !== null) {
            return $this->_providersByHandle;
        }

        $select = [
            'id',
            'handle',
            'type',
            'scope',
            'enabled',
            'saveSpam',
            'settings',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        $rows = (new Query())
            ->select($select)
            ->from([Table::FORMIE_CAPTCHA_PROVIDERS])
            ->indexBy('handle')
            ->all();

        foreach ($rows as &$row) {
            if (is_string($row['settings']) && $row['settings'] !== '') {
                $decoded = Json::decodeIfJson($row['settings']);

                $row['settings'] = is_array($decoded) ? $decoded : [];
            }

            if (!is_array($row['settings'])) {
                $row['settings'] = [];
            }
        }
        unset($row);

        return $this->_providersByHandle = $rows;
    }

    private function _rowToIntegrationConfig(array $row): array
    {
        $config = [
            'id' => (int)$row['id'],
            'handle' => $row['handle'],
            'type' => $row['type'],
            'scope' => Integrations::SCOPE_SITE,
            'enabled' => $row['enabled'],
            'settings' => $row['settings'],
            'uid' => $row['uid'],
        ];

        if ($row['saveSpam'] !== null) {
            $config['saveSpam'] = (bool)$row['saveSpam'];
        }

        return $config;
    }

    private function _saveProviderRow(IntegrationInterface $integration, bool $isNew): bool
    {
        $settings = $integration->getSettings();
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            if ($isNew) {
                $record = new CaptchaProviderRecord();
                $record->uid = $integration->uid;
            } else {
                $record = CaptchaProviderRecord::findOne($integration->id);

                if (!$record) {
                    throw new Exception('Invalid captcha provider ID: ' . $integration->id);
                }
            }

            $record->handle = (string)$integration->getHandle();
            $record->type = get_class($integration);
            $record->scope = Integrations::SCOPE_SITE;
            $record->enabled = $this->_normalizeEnabledValue($integration->getEnabled(false));
            $record->saveSpam = $integration->saveSpam;
            $record->settings = $settings;

            $record->save(false);

            $integration->id = $record->id;
            $integration->uid = $record->uid;

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    private function _normalizeEnabledValue(mixed $enabled): string
    {
        if (is_string($enabled) && str_starts_with($enabled, '$')) {
            return $enabled;
        }

        return App::parseBooleanEnv($enabled) ? 'true' : 'false';
    }

    public function resetCache(): void
    {
        $this->_resetCache();
    }

    private function _resetCache(): void
    {
        $this->_providersByHandle = null;
    }
}
