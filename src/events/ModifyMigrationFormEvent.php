<?php
namespace verbb\formie\events;

use craft\events\CancelableEvent;

class ModifyMigrationFormEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $form;
    public $newForm;

}
