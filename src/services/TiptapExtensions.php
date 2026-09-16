<?php
namespace verbb\formie\services;

use verbb\formie\events\RegisterTiptapExtensionsEvent;
use verbb\formie\tiptap\RegisteredTextStyles;
use verbb\formie\tiptap\TextStyleDefinition;

use yii\base\Component;

use InvalidArgumentException;

use Tiptap\Core\Extension;
use verbb\tiptap\EditorFactory;

class TiptapExtensions extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_EXTENSIONS = 'registerExtensions';


    // Properties
    // =========================================================================

    private ?array $_extensions = null;
    private ?array $_textStyles = null;


    // Public Methods
    // =========================================================================

    public function getPhpExtensions(): array
    {
        $this->_resolve();
        $extensions = array_values($this->_extensions ?? []);

        if ($this->_textStyles !== []) {
            $extensions[] = new RegisteredTextStyles([
                'definitions' => $this->_textStyles,
            ]);
        }

        return $extensions;
    }

    public function getClientConfig(): array
    {
        $this->_resolve();

        return [
            'extensionIds' => array_keys($this->_extensions ?? []),
            'textStyles' => array_map(
                static fn(TextStyleDefinition $definition): array => [
                    'id' => $definition->getId(),
                    'label' => $definition->getLabel(),
                    'attribute' => $definition->getAttribute(),
                    'cssProperty' => $definition->getCssProperty(),
                    'allowedValues' => $definition->getAllowedValues(),
                    'toolbarValue' => $definition->getToolbarValue(),
                ],
                $this->_textStyles ?? [],
            ),
        ];
    }


    // Private Methods
    // =========================================================================

    private function _resolve(): void
    {
        if ($this->_extensions !== null && $this->_textStyles !== null) {
            return;
        }

        $event = new RegisterTiptapExtensionsEvent();
        $this->trigger(self::EVENT_REGISTER_EXTENSIONS, $event);

        $coreNames = [];
        foreach (EditorFactory::pluginKitExtensions() as $coreExtension) {
            $coreNames[] = $coreExtension::$name;
        }

        $schemaNames = [];
        foreach ($event->extensions as $id => $extension) {
            if (!is_string($id) || $id === '' || trim($id) !== $id) {
                throw new InvalidArgumentException('Formie TipTap extension IDs must be non-empty, trimmed strings.');
            }

            if (!$extension instanceof Extension) {
                throw new InvalidArgumentException("Formie TipTap extension \"{$id}\" must extend Tiptap\\Core\\Extension.");
            }

            $schemaName = $extension::$name;
            if (!is_string($schemaName) || $schemaName === '') {
                throw new InvalidArgumentException("Formie TipTap extension \"{$id}\" must have a schema name.");
            }

            if (in_array($schemaName, $coreNames, true)) {
                throw new InvalidArgumentException("Formie TipTap core extension \"{$schemaName}\" cannot be replaced.");
            }

            if (isset($schemaNames[$schemaName])) {
                throw new InvalidArgumentException("Formie TipTap extension name \"{$schemaName}\" is already registered by \"{$schemaNames[$schemaName]}\".");
            }

            $schemaNames[$schemaName] = $id;
        }

        $styleIds = [];
        $styleAttributes = [];
        foreach ($event->textStyles as $definition) {
            if (!$definition instanceof TextStyleDefinition) {
                throw new InvalidArgumentException('Formie TextStyle registrations must be TextStyleDefinition instances.');
            }

            $id = $definition->getId();
            $attribute = $definition->getAttribute();

            if (isset($styleIds[$id])) {
                throw new InvalidArgumentException("Formie TextStyle definition ID \"{$id}\" is already registered.");
            }

            if (isset($styleAttributes[$attribute])) {
                throw new InvalidArgumentException("Formie TextStyle attribute \"{$attribute}\" is already registered by \"{$styleAttributes[$attribute]}\".");
            }

            $styleIds[$id] = true;
            $styleAttributes[$attribute] = $id;
        }

        $this->_extensions = $event->extensions;
        $this->_textStyles = array_values($event->textStyles);
    }
}
