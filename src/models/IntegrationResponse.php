<?php
namespace verbb\formie\models;

use craft\base\Model;

/** @deprecated Stable Formie 3 compatibility. Return IntegrationResult from new providers. */
class IntegrationResponse extends Model
{
    // Properties
    // =========================================================================

    public ?bool $success = null;
    public ?array $message = null;


    // Public Methods
    // =========================================================================

    public function __construct($success, $message = [])
    {
        parent::__construct();

        $this->success = $success;
        $this->message = $message;
    }

}
