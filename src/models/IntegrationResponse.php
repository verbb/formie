<?php
namespace verbb\formie\models;

use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;

class IntegrationResponse extends Model
{
    // Properties
    // =========================================================================

    public $success;
    public $message;


    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function __construct($success, $message = [])
    {
        $this->success = $success;
        $this->message = $message;
    }

}
