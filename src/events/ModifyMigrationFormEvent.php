<?php
namespace verbb\formie\events;

use verbb\formie\elements\Form;

use craft\events\CancelableEvent;

class ModifyMigrationFormEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public mixed $form = null;
    public ?Form $newForm = null;
    
}
