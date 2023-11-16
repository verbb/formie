<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;

use verbb\formie\base\FormField;
use verbb\formie\base\FormFieldInterface;
use verbb\formie\Formie;

class SyncField extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?string $syncId = null;
    public ?string $fieldId = null;


    // Private Properties
    // =========================================================================

    private ?Sync $_sync = null;
    private ?FormFieldInterface $_field = null;


    // Public Methods
    // =========================================================================

    public function getField(): ?FormFieldInterface
    {
        if (!$this->_field) {
            $this->_field = Craft::$app->getFields()->getFieldById($this->fieldId);
        }

        return $this->_field;
    }

    public function setField(FormFieldInterface $field): void
    {
        /* @var FormField $field */
        $this->fieldId = $field->id;
        $this->_field = $field;
    }

    public function getSync(): ?Sync
    {
        if (!$this->_sync) {
            $this->_sync = Formie::$plugin->getSyncs()->getSyncById($this->syncId);
        }

        return $this->_sync;
    }

    public function setSync(Sync $sync): void
    {
        $this->syncId = $sync->id;
        $this->_sync = $sync;
    }
}
