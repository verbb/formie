<?php
namespace verbb\formie\models;

use yii\base\BaseObject;

use InvalidArgumentException;

/** One configured declaration. Executable code is resolved only by a trusted registry. */
class BrowserModuleEntry extends BaseObject
{
    // Constants
    // =========================================================================

    public const SURFACE_SERVER_RENDERED = 'server-rendered';
    public const SURFACE_CLIENT_RENDERED = 'client-rendered';
    public const SURFACE_CP_EDIT = 'cp-edit';


    // Properties
    // =========================================================================

    public string $key = '';
    public string $moduleId = '';
    public ?string $type = null;
    public string $capability = '';
    public array $targets = [];
    public array $surfaces = [self::SURFACE_SERVER_RENDERED];
    public array $config = [];
    public bool $required = true;


    // Public Methods
    // =========================================================================

    public function toArray(): array
    {
        if (!preg_match('/^[a-z][a-z0-9.-]*:[a-z][a-z0-9.-]*$/', $this->moduleId)) {
            throw new InvalidArgumentException('Browser module IDs must be namespaced registry identifiers: ' . $this->moduleId);
        }

        foreach ($this->surfaces as $surface) {
            if (!in_array($surface, [self::SURFACE_SERVER_RENDERED, self::SURFACE_CLIENT_RENDERED, self::SURFACE_CP_EDIT], true)) {
                throw new InvalidArgumentException('Invalid browser module surface.');
            }
        }

        foreach ($this->targets as $target) {
            if (!in_array($target['targetType'] ?? null, ['field', 'form', 'page', 'button', 'global'], true) || !is_string($target['targetId'] ?? null)) {
                throw new InvalidArgumentException('Invalid browser module target.');
            }
        }

        return [
            'key' => $this->key,
            'moduleId' => $this->moduleId,
            'type' => $this->type ?? 'field',
            'capability' => $this->capability ?: substr($this->moduleId, strpos($this->moduleId, ':') + 1),
            'targets' => $this->targets,
            'surfaces' => $this->surfaces,
            'config' => $this->config,
            'required' => $this->required,
        ];
    }

    public function supportsSurface(string $surface): bool
    {
        return in_array($surface, $this->surfaces, true);
    }
}
