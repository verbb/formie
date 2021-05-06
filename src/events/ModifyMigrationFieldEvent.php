<?php
namespace verbb\formie\events;

use craft\events\CancelableEvent;

class ModifyMigrationFieldEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $form;
    public $originForm;
    public $field;
    public $newField;
    
}
