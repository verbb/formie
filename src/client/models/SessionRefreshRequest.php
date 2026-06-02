<?php
namespace verbb\formie\client\models;

use verbb\formie\client\BaseClientModel;

class SessionRefreshRequest extends BaseClientModel
{
    // Properties
    // =========================================================================

    public string $handle = '';
    public ?int $siteId = null;
    public array $session = [];
}
