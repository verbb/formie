<?php
namespace verbb\formie\client\bootstrap\models;

use verbb\formie\client\BaseClientModel;

class FormDefinition extends BaseClientModel
{
    // Properties
    // =========================================================================

    public string $id = '';
    public string $handle = '';
    public ?string $title = null;
    public ?string $locale = null;
    public ?int $siteId = null;
    public array $settings = [];
    public array $pages = [];
    public array $modules = [];
    public array $submission = [];
}
