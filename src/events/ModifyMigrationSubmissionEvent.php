<?php
namespace verbb\formie\events;

use craft\events\CancelableEvent;

class ModifyMigrationSubmissionEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public $form;
    public $submission;
    
}
