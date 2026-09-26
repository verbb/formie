<?php
namespace verbb\formie\base;

trait FieldCpEditConfigTrait
{
    // Public Methods
    // =========================================================================

    // Thin config used by CP submission editing, not the full REST/GQL/browser payload.
    public function getCpEditConfig(): array
    {
        return [
            'id' => (string)$this->id,
            'uid' => (string)$this->uid,
            'handle' => $this->handle,
            'type' => static::kebabClassName(),
            'label' => $this->label,
            'required' => (bool)$this->required,
            'validation' => $this->browserValidationRules(),
            'settings' => $this->getSettings(),
        ];
    }

}
