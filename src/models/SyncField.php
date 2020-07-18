<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;

use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\Formie;

class SyncField extends Model
{
    // Public Properties
    // =========================================================================

    public $id;
    public $syncId;
    public $fieldId;


    // Private Properties
    // =========================================================================

    private $_sync;
    private $_field;


    // Public Methods
    // =========================================================================

    /**
     * Returns the field.
     *
     * @return FormFieldInterface|null
     */
    public function getField()
    {
        if (!$this->_field) {
            $this->_field = Craft::$app->getFields()->getFieldById($this->fieldId);
        }

        return $this->_field;
    }

    /**
     * Sets the field.
     *
     * @param FormFieldInterface $field
     */
    public function setField(FormFieldInterface $field)
    {
        /* @var FormField $field */
        $this->fieldId = $field->id;
        $this->_field = $field;
    }

    /**
     * Returns the reference.
     *
     * @return Sync|null
     */
    public function getSync()
    {
        if (!$this->_sync) {
            $this->_sync = Formie::$plugin->getSyncs()->getSyncById($this->syncId);
        }

        return $this->_sync;
    }

    /**
     * Sets the sync.
     *
     * @param Sync $sync
     */
    public function setSync(Sync $sync)
    {
        $this->syncId = $sync->id;
        $this->_sync = $sync;
    }
}
