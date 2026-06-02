<?php
namespace verbb\formie\client\models;

use verbb\formie\client\BaseClientModel;

class FormSession extends BaseClientModel
{
    // Properties
    // =========================================================================

    public string $id = '';
    public string $currentPageId = '';
    public array $tokens = [];
    public ?array $continuation = null;
}
