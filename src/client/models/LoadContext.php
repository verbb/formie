<?php
namespace verbb\formie\client\models;

use verbb\formie\client\BaseClientModel;

class LoadContext extends BaseClientModel
{
    // Properties
    // =========================================================================

    public string $handle = '';
    public ?int $siteId = null;
    public ?string $locale = null;
}
