<?php
namespace verbb\formie\models;

class PageTransitionRequest extends FormMutationRequest
{
    // Properties
    // =========================================================================

    public string $handle = '';
    public ?string $operationId = null;
    public ?int $siteId = null;
    public ?string $currentPageId = null;
    public ?string $targetPageId = null;
    public array $session = [];
    public array $values = [];
}
