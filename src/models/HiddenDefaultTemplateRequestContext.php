<?php
namespace verbb\formie\models;

use craft\base\Model;

class HiddenDefaultTemplateRequestContext extends Model
{
    /** @var array<string, mixed> */
    public array $param = [];

    public string $userIp = '';
    public string $absoluteUrl = '';
    public string $userAgent = '';
}
