<?php
namespace verbb\formie\client\models;

use verbb\formie\base\FormPayload;

class LoadContext extends FormPayload
{
    // Properties
    // =========================================================================

    public ?array $query = null;
    public string $handle = '';
    public ?int $siteId = null;
    public ?string $locale = null;
    public ?string $grantToken = null;
    public string $grantPurpose = 'continue-incomplete';
    public ?string $draftContext = null;
}
