<?php
namespace verbb\formie\client\models;

use verbb\formie\client\BaseClientModel;

class PageTransitionRequest extends BaseClientModel
{
    // Properties
    // =========================================================================
    
    public string $handle = '';
    public ?int $siteId = null;
    public ?string $currentPageId = null;
    public ?string $targetPageId = null;
    public array $session = [];
    public array $values = [];
}
