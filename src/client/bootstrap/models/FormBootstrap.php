<?php
namespace verbb\formie\client\bootstrap\models;

use verbb\formie\client\BaseClientModel;
use verbb\formie\client\models\FormSession;

class FormBootstrap extends BaseClientModel
{
    // Properties
    // =========================================================================

    public int $schemaVersion = 1;
    public FormDefinition $definition;
    public FormSession $session;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        $this->definition = new FormDefinition();
        $this->session = new FormSession();

        parent::__construct($config);
    }
}
