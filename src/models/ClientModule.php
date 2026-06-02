<?php
namespace verbb\formie\models;

use yii\base\BaseObject;

class ClientModule extends BaseObject
{
    // Constants
    // =========================================================================

    public const RENDER_TARGET_FRONTEND = 'frontend';
    public const RENDER_TARGET_CP_EDIT = 'cp-edit';

    
    // Properties
    // =========================================================================

    public ?string $id = null;
    public ?string $src = null;
    public ?string $type = null;
    public array $targets = [];
    public array $renderTargets = [];
    public array $config = [];


    // Public Methods
    // =========================================================================

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'src' => $this->src,
            'type' => $this->type,
            'targets' => $this->targets,
            'renderTargets' => $this->renderTargets,
            'config' => $this->config ?: null,
        ], static function($value) {
            return $value !== null && $value !== '' && $value !== [];
        });
    }

    public function supportsRenderTarget(string $renderTarget): bool
    {
        if ($this->renderTargets === []) {
            return true;
        }

        return in_array($renderTarget, $this->renderTargets, true);
    }
}
