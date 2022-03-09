<?php
namespace verbb\formie\models;

use craft\base\Model;

use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\Formie;

class Sync extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;

    private ?array $_fields = null;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        $this->getCustomFields();
    }

    /**
     * Returns this sync's fields.
     *
     * @return SyncField[]
     */
    public function getCustomFields(): array
    {
        if (!$this->_fields) {
            $this->_fields = Formie::$plugin->getSyncs()->getSyncFieldsBySync($this);
        }

        return $this->_fields;
    }

    /**
     * Returns whether the sync contains any fields.
     *
     * @return bool
     */
    public function hasFields(): bool
    {
        return count($this->getCustomFields()) > 1;
    }

    /**
     * Adds a field to this sync.
     *
     * @param FormFieldInterface $field
     */
    public function addField(FormFieldInterface $field): void
    {
        /* @var FormField $field */
        if (!$field->id) {
            return;
        }

        if (!$this->_fields) {
            $this->_fields = [];
        }

        foreach ($this->_fields as $refField) {
            if ($refField->fieldId == $field->id) {
                // The field is already synced.
                return;
            }
        }

        $syncField = new SyncField();
        $syncField->fieldId = $field->id;

        $this->_fields[] = $syncField;
    }
}
