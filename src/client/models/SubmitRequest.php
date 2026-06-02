<?php
namespace verbb\formie\client\models;

use verbb\formie\client\BaseClientModel;

class SubmitRequest extends BaseClientModel
{
    // Properties
    // =========================================================================

    public string $handle = '';
    public string $action = 'submit';
    public ?int $siteId = null;
    public array $session = [];
    public array $values = [];
}
