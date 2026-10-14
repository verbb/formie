<?php
namespace verbb\formie\models;

class SessionRefreshRequest extends FormMutationRequest
{
    // Properties
    // =========================================================================

    public string $handle = '';
    public ?int $siteId = null;
    public array $session = [];
}
