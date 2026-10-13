<?php
namespace verbb\formie\client\models;

use verbb\formie\client\ClientMutationRequest;

class SessionRefreshRequest extends ClientMutationRequest
{
    // Properties
    // =========================================================================

    public string $handle = '';
    public ?int $siteId = null;
    public array $session = [];
}
