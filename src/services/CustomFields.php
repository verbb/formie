<?php
namespace verbb\formie\services;

use verbb\formie\events\RegisterCustomFieldAdaptersEvent;
use verbb\formie\fields\custom\CustomFieldAdapterInterface;

use Craft;

use yii\base\Component;
use yii\base\InvalidConfigException;

class CustomFields extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_CUSTOM_FIELD_ADAPTERS = 'registerCustomFieldAdapters';


    // Properties
    // =========================================================================

    private ?array $_adapterTypes = null;
    private array $_adapters = [];


    // Public Methods
    // =========================================================================

    public function getAdapterTypes(bool $availableOnly = true): array
    {
        if ($this->_adapterTypes === null) {
            $event = new RegisterCustomFieldAdaptersEvent([
                'adapters' => [
                ],
            ]);

            $this->trigger(self::EVENT_REGISTER_CUSTOM_FIELD_ADAPTERS, $event);

            $this->_adapterTypes = array_values(array_unique($event->adapters));
        }

        if (!$availableOnly) {
            return $this->_adapterTypes;
        }

        return array_values(array_filter($this->_adapterTypes, static function(string $adapterType): bool {
            return is_subclass_of($adapterType, CustomFieldAdapterInterface::class) && $adapterType::isAvailable();
        }));
    }

    public function getAdapterOptions(bool $availableOnly = true): array
    {
        $options = [];

        foreach ($this->getAdapterTypes($availableOnly) as $adapterType) {
            $definition = $this->createAdapter($adapterType::handle())->getFieldTypeDefinition();
            $options[] = [
                'label' => $definition['label'] ?? $adapterType::displayName(),
                'value' => $definition['handle'] ?? $adapterType::handle(),
                'icon' => $definition['icon'] ?? null,
            ];
        }

        return $options;
    }

    public function getAdapterDefinitions(bool $availableOnly = true): array
    {
        $definitions = [];

        foreach ($this->getAdapterTypes($availableOnly) as $adapterType) {
            $adapter = $this->createAdapter($adapterType::handle());
            $definitions[] = $adapter->getFieldTypeDefinition();
        }

        return $definitions;
    }

    public function createAdapter(?string $handle): CustomFieldAdapterInterface
    {
        $handle = trim((string)$handle);
        $adapterType = $this->getAdapterTypeByHandle($handle) ?? UrlCustomFieldAdapter::class;

        if (!isset($this->_adapters[$adapterType])) {
            $adapter = new $adapterType();

            if (!$adapter instanceof CustomFieldAdapterInterface) {
                throw new InvalidConfigException("Custom field adapter \"{$adapterType}\" must implement CustomFieldAdapterInterface.");
            }

            $this->_adapters[$adapterType] = $adapter;
        }

        return $this->_adapters[$adapterType];
    }

    public function getAdapterTypeByHandle(string $handle): ?string
    {
        foreach ($this->getAdapterTypes(false) as $adapterType) {
            if (!is_subclass_of($adapterType, CustomFieldAdapterInterface::class)) {
                Craft::warning("Custom field adapter \"{$adapterType}\" must implement CustomFieldAdapterInterface.", __METHOD__);

                continue;
            }

            if ($adapterType::handle() === $handle) {
                return $adapterType;
            }
        }

        return null;
    }
}
