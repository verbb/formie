<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;

use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\Formie;

class Sync extends Model
{
    // Public Properties
    // =========================================================================

    public $id;


    // Private Properties
    // =========================================================================

    /**
     * @var SyncField[]
     */
    private $_fields;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        $this->getFields();
    }

    /**
     * Returns this sync's fields.
     *
     * @return SyncField[]
     */
    public function getFields()
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
        return count($this->getFields()) > 1;
    }

    /**
     * Adds a field to this sync.
     *
     * @param FormFieldInterface $field
     */
    public function addField(FormFieldInterface $field)
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
