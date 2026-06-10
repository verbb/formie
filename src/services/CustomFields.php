<?php
namespace verbb\formie\services;

use verbb\formie\events\RegisterCustomFieldAdaptersEvent;
use verbb\formie\fields\custom\CustomFieldAdapterInterface;
use verbb\formie\fields\custom\adapters\LinkCustomFieldAdapter;

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
                    LinkCustomFieldAdapter::class,
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
            $definition = $this->createAdapter($adapterType)->getFieldTypeDefinition();
            $options[] = [
                'label' => $definition['label'] ?? $adapterType::displayName(),
                'value' => $definition['type'] ?? $adapterType,
                'icon' => $definition['icon'] ?? null,
            ];
        }

        return $options;
    }

    public function getAdapterDefinitions(bool $availableOnly = true): array
    {
        $definitions = [];

        foreach ($this->getAdapterTypes($availableOnly) as $adapterType) {
            $adapter = $this->createAdapter($adapterType);
            $definitions[] = $adapter->getFieldTypeDefinition();
        }

        return $definitions;
    }

    public function createAdapter(?string $adapterType): CustomFieldAdapterInterface
    {
        $adapterType = trim((string)$adapterType) ?: LinkCustomFieldAdapter::class;

        if (!is_subclass_of($adapterType, CustomFieldAdapterInterface::class)) {
            $adapterType = LinkCustomFieldAdapter::class;
        }

        if (!isset($this->_adapters[$adapterType])) {
            $adapter = new $adapterType();

            if (!$adapter instanceof CustomFieldAdapterInterface) {
                throw new InvalidConfigException("Custom field adapter \"{$adapterType}\" must implement CustomFieldAdapterInterface.");
            }

            $this->_adapters[$adapterType] = $adapter;
        }

        return $this->_adapters[$adapterType];
    }
}
