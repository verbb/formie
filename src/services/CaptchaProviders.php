<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\events\IntegrationEvent;
use verbb\formie\helpers\DbSchema;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Settings;
use verbb\formie\records\CaptchaProvider as CaptchaProviderRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\App;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;

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
                'scope' => Integrations::SCOPE_PROJECT,
                'enabled' => false,
                'settings' => [],
            ];
        }

        return $config;
    }

    public function getProjectScopedProviders(): array
    {
        $providers = [];

        foreach ($this->_getProvidersByHandle() as $handle => $row) {
            if (($row['scope'] ?? Integrations::SCOPE_PROJECT) !== Integrations::SCOPE_PROJECT) {
                continue;
            }

            $providers[$handle] = Formie::$plugin->getIntegrations()->createIntegration(
                $this->_rowToIntegrationConfig($row),
            );
        }

        return $providers;
    }

    public function saveProvider(IntegrationInterface $integration): bool
    {
        $integrations = Formie::$plugin->getIntegrations();

        if ($integrations->hasEventHandlers(Integrations::EVENT_BEFORE_SAVE_INTEGRATION)) {
            $integrations->trigger(Integrations::EVENT_BEFORE_SAVE_INTEGRATION, new IntegrationEvent([
                'integration' => $integration,
            ]));
        }

        if (!$integration->beforeSave(false)) {
            return false;
        }

        $handle = $integration->getHandle();
        $row = $this->getProviderRowByHandle($handle);
        $wasProjectScoped = $row && ($row['scope'] ?? Integrations::SCOPE_PROJECT) === Integrations::SCOPE_PROJECT;

        // Captcha credentials and enabled state are environment-specific. CP saves always
        // persist as site-scoped overrides so they are editable on every environment.
        if ($integration->isProjectScope()) {
            $integration->scope = Integrations::SCOPE_SITE;
        }

        if (!$integration->canEdit()) {
            $integration->addError('name', Craft::t('formie', 'This integration cannot be edited in the current environment.'));

            return false;
        }

        if ($wasProjectScoped) {
            $configPath = self::CONFIG_CAPTCHA_PROVIDERS_KEY . '.' . $handle;

            if (Craft::$app->getProjectConfig()->get($configPath) !== null) {
                Craft::$app->getProjectConfig()->remove(
                    $configPath,
                    "Promote the “{$handle}” captcha provider to a site-scoped override",
                );
            }
        }

        $isNew = $row === null;

        if ($isNew) {
            $integration->scope = $integration->scope ?: Integrations::resolveScopeForNew();
            $integration->uid = $integration->uid ?: StringHelper::UUID();
        } else {
            $integration->id = (int)$row['id'];
            $integration->uid = $integration->uid ?: $row['uid'];
            $integration->scope = $integration->scope ?: $row['scope'];
        }

        if ($integration->isProjectScope()) {
            $saved = $this->_saveProjectProvider($integration, $isNew);
        } else {
            $saved = $this->_saveProviderRow($integration, $isNew);
        }

        if (!$saved) {
            return false;
        }

        if ($integrations->hasEventHandlers(Integrations::EVENT_AFTER_SAVE_INTEGRATION)) {
            $integrations->trigger(Integrations::EVENT_AFTER_SAVE_INTEGRATION, new IntegrationEvent([
                'integration' => $integration,
            ]));
        }

        $integration->afterSave(false);

        $this->_resetCache();
        $integrations->resetCaptchaCaches();

        return true;
    }

    public function seedRegistryFromLegacySettings(array $legacyCaptchas = []): void
    {
        if (!DbSchema::tableExists(Table::FORMIE_CAPTCHA_PROVIDERS)) {
            return;
        }

        $integrations = Formie::$plugin->getIntegrations();
        $now = Db::prepareDateForDb(new \DateTime());

        foreach ($integrations->getIntegrationTypes(Integration::TYPE_CAPTCHA) as $captchaClass) {
            $class = new $captchaClass();
            $handle = $class->getHandle();

            if ($this->getProviderRowByHandle($handle)) {
                continue;
            }

            $legacy = $legacyCaptchas[$handle] ?? [];
            $settings = $legacy['settings'] ?? [];

            Craft::$app->getDb()->createCommand()
                ->insert(Table::FORMIE_CAPTCHA_PROVIDERS, [
                    'handle' => $handle,
                    'type' => $legacy['type'] ?? $captchaClass,
                    'scope' => Integrations::SCOPE_PROJECT,
                    'enabled' => $this->_normalizeEnabledValue($legacy['enabled'] ?? false),
                    'saveSpam' => array_key_exists('saveSpam', $legacy) ? (bool)$legacy['saveSpam'] : null,
                    'settings' => Json::encode($settings),
                    'dateCreated' => $now,
                    'dateUpdated' => $now,
                    'uid' => StringHelper::UUID(),
                ])
                ->execute();
        }

        $this->_resetCache();
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

    public function handleChangedProvider(ConfigEvent $event): void
    {
        $handle = $event->tokenMatches[0];
        $data = $event->newValue;
        $existing = CaptchaProviderRecord::findOne(['handle' => $handle]);

        // Site-scoped captcha credentials are environment-specific and must not be overwritten by project config sync.
        if ($existing && ($existing->scope ?? Integrations::SCOPE_PROJECT) === Integrations::SCOPE_SITE) {
            return;
        }

        $settings = ProjectConfig::unpackAssociativeArrays($data['settings'] ?? []);
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $record = CaptchaProviderRecord::findOne(['handle' => $handle]) ?? new CaptchaProviderRecord();
            $isNew = $record->getIsNewRecord();

            if ($isNew) {
                $record->uid = StringHelper::UUID();
            }

            $record->handle = $handle;
            $record->type = $data['type'];
            $record->scope = Integrations::SCOPE_PROJECT;
            $record->enabled = $this->_normalizeEnabledValue($data['enabled'] ?? false);
            $record->saveSpam = array_key_exists('saveSpam', $data) ? (bool)$data['saveSpam'] : null;
            $record->settings = $settings;
            $record->save(false);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->_resetCache();
        Formie::$plugin->getIntegrations()->resetCaptchaCaches();
    }

    public function handleDeletedProvider(ConfigEvent $event): void
    {
        $handle = $event->tokenMatches[0];
        $record = CaptchaProviderRecord::findOne(['handle' => $handle]);

        if (!$record) {
            return;
        }

        // Site-scoped captcha credentials are environment-specific and must not be cleared by project config sync.
        if (($record->scope ?? Integrations::SCOPE_PROJECT) === Integrations::SCOPE_SITE) {
            return;
        }

        $record->enabled = 'false';
        $record->saveSpam = null;
        $record->settings = [];
        $record->scope = Integrations::SCOPE_PROJECT;
        $record->save(false);

        $this->_resetCache();
        Formie::$plugin->getIntegrations()->resetCaptchaCaches();
    }

    public function hydrateLegacyCaptchas(Settings $settings): void
    {
        if (empty($settings->captchas) || !DbSchema::tableExists(Table::FORMIE_CAPTCHA_PROVIDERS)) {
            return;
        }

        $this->seedRegistryFromLegacySettings($settings->captchas);
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

        if (!DbSchema::tableExists(Table::FORMIE_CAPTCHA_PROVIDERS)) {
            return $this->_providersByHandle = [];
        }

        $select = [
            'id',
            'handle',
            'type',
            'enabled',
            'saveSpam',
            'settings',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        if (DbSchema::columnExists(Table::FORMIE_CAPTCHA_PROVIDERS, 'scope')) {
            array_splice($select, 3, 0, ['scope']);
        }

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
            'scope' => $row['scope'] ?: Integrations::SCOPE_PROJECT,
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
                    throw new \Exception('Invalid captcha provider ID: ' . $integration->id);
                }
            }

            $record->handle = (string)$integration->getHandle();
            $record->type = get_class($integration);
            $record->scope = $integration->getScope();
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

    private function _saveProjectProvider(IntegrationInterface $integration, bool $isNew): bool
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $integration->addError('name', Craft::t('formie', 'Project integrations cannot be saved when admin changes are disabled.'));

            return false;
        }

        $integration->scope = Integrations::SCOPE_PROJECT;
        $handle = $integration->getHandle();
        $configPath = self::CONFIG_CAPTCHA_PROVIDERS_KEY . '.' . $handle;

        Craft::$app->getProjectConfig()->set(
            $configPath,
            $this->createProviderConfig($integration),
            "Save the “{$handle}” captcha provider",
        );

        $saved = $this->_saveProviderRow($integration, $isNew);

        if ($isNew) {
            $integration->id = (int)(new Query())
                ->select(['id'])
                ->from([Table::FORMIE_CAPTCHA_PROVIDERS])
                ->where(['handle' => $handle])
                ->scalar();
        }

        return $saved;
    }

    private function _normalizeEnabledValue(mixed $enabled): string
    {
        return App::parseBooleanEnv($enabled) ? 'true' : 'false';
    }

    private function _resetCache(): void
    {
        $this->_providersByHandle = null;
    }
}
