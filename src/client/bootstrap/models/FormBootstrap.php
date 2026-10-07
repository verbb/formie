<?php
namespace verbb\formie\client\bootstrap\models;

use verbb\formie\client\BaseClientModel;
use verbb\formie\client\models\FormSession;

use InvalidArgumentException;

class FormBootstrap extends BaseClientModel
{
    // Properties
    // =========================================================================

    public int $contractVersion = 1;
    public FormDefinition $definition;
    public FormSession $session;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        $this->definition = new FormDefinition();
        $this->session = new FormSession();

        parent::__construct($config);

        $this->_assertVersion();
    }

    public function toArrayRecursive(): array
    {
        $this->_assertVersion();

        return parent::toArrayRecursive();
    }


    // Private Methods
    // =========================================================================

    private function _assertVersion(): void
    {
        if ($this->contractVersion !== 1) {
            throw new InvalidArgumentException('Unsupported client-rendered contractVersion. Update Formie and its browser packages together.');
        }
    }
}
