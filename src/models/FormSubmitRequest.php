<?php
namespace verbb\formie\models;

class FormSubmitRequest extends FormMutationRequest
{
    // Properties
    // =========================================================================

    public string $handle = '';
    public ?string $operationId = null;
    public ?int $targetPageId = null;
    public string $action = 'submit';
    public ?int $siteId = null;
    public array $session = [];
    public array $values = [];
    public array $browserData = [];
}
