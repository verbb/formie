<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\IntegrationInterface;
use verbb\formie\events\IntegrationEvent;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\records\CaptchaProvider as CaptchaProviderRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;

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

        if (!$integration->canEdit()) {
            $integration->addError('name', Craft::t('formie', 'This integration cannot be edited in the current environment.'));

            return false;
        }

        $handle = $integration->getHandle();
        $row = $this->getProviderRowByHandle($handle);
        $isNew = $row === null;

        if ($isNew) {
            $integration->scope = $integration->scope ?: Integrations::resolveScopeForNew();
            $integration->uid = $integration->uid ?: StringHelper::UUID();
        } else {
            $integration->id = (int)$row['id'];
            $integration->uid = $integration->uid ?: $row['uid'];
            $integration->scope = $integration->scope ?: $row['scope'];
        }

        $saved = $this->_saveProviderRow($integration, $isNew);

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


    // Private Methods
    // =========================================================================

    private function _getProvidersByHandle(): array
    {
        if ($this->_providersByHandle !== null) {
            return $this->_providersByHandle;
        }

        $rows = (new Query())
            ->select([
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
            ])
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
            $record->enabled = $integration->getEnabled(false);
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
        if (is_string($enabled) && $enabled !== '') {
            return $enabled;
        }

        return $enabled ? 'true' : 'false';
    }

    private function _resetCache(): void
    {
        $this->_providersByHandle = null;
    }
}
