<?php
namespace verbb\formie\models;

use craft\base\Model;
use craft\helpers\Json;

class OptionSource extends Model
{
    // Properties
    // =========================================================================

    public ?string $type = null;
    public ?string $provider = null;
    public array $params = [];
    public array $cache = [];


    // Public Methods
    // =========================================================================

    public function rules(): array
    {
        return [
            [['type', 'provider'], 'string'],
            [['params', 'cache'], 'safe'],
        ];
    }

    public static function fromConfig(mixed $config): ?self
    {
        if (is_string($config)) {
            $config = Json::decodeIfJson($config);
        }

        if (!is_array($config) || $config === []) {
            return null;
        }

        return new self([
            'type' => isset($config['type']) ? (string)$config['type'] : null,
            'provider' => isset($config['provider']) ? (string)$config['provider'] : null,
            'params' => is_array($config['params'] ?? null) ? $config['params'] : [],
            'cache' => is_array($config['cache'] ?? null) ? $config['cache'] : [],
        ]);
    }

    public function toConfig(): array
    {
        $config = [];

        if ($this->type !== null && $this->type !== '') {
            $config['type'] = $this->type;
        }

        if ($this->provider !== null && $this->provider !== '') {
            $config['provider'] = $this->provider;
        }

        if ($this->params !== []) {
            $config['params'] = $this->params;
        }

        if ($this->cache !== []) {
            $config['cache'] = $this->cache;
        }

        return $config;
    }
}
