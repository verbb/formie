<?php
namespace verbb\formie\models;

use craft\base\Model;

class IntegrationCollection extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?string $name = null;
    public array $fields = [];

}
