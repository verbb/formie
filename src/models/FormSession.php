<?php
namespace verbb\formie\models;

use verbb\formie\base\FormPayload;

class FormSession extends FormPayload
{
    // Properties
    // =========================================================================

    public int $version = 0;
    public string $id = '';
    public string $currentPageId = '';
    public array $tokens = [];
    public ?array $continuation = null;
}
