<?php
namespace verbb\formie\events;

use verbb\formie\tiptap\TextStyleDefinition;

use yii\base\Event;

use InvalidArgumentException;
use Tiptap\Core\Extension;

class RegisterTiptapExtensionsEvent extends Event
{
    // Properties
    // =========================================================================

    public array $extensions = [];
    public array $textStyles = [];


    // Public Methods
    // =========================================================================

    public function registerExtension(string $id, Extension $extension): void
    {
        if (isset($this->extensions[$id])) {
            throw new InvalidArgumentException("A Formie TipTap extension with ID \"{$id}\" is already registered.");
        }

        $this->extensions[$id] = $extension;
    }

    public function registerTextStyle(TextStyleDefinition $definition): void
    {
        foreach ($this->textStyles as $registeredDefinition) {
            if ($registeredDefinition->getId() === $definition->getId()) {
                throw new InvalidArgumentException("A Formie TextStyle definition with ID \"{$definition->getId()}\" is already registered.");
            }
        }

        $this->textStyles[] = $definition;
    }
}
