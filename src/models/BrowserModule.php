<?php
namespace verbb\formie\models;

use InvalidArgumentException;

/**
 * One immutable PHP declaration of browser behaviour required by a form resource.
 *
 * Formie projects declarations into surface-specific BrowserModuleEntry instances;
 * executable JavaScript remains registered separately against moduleId.
 */
final readonly class BrowserModule
{
    // Constants
    // =========================================================================

    public const SURFACE_SERVER_RENDERED = 'server-rendered';
    public const SURFACE_CLIENT_RENDERED = 'client-rendered';
    public const SURFACE_CP_EDIT = 'cp-edit';
    public const KIND_FIELD = 'field';
    public const KIND_CAPTCHA = 'captcha';
    public const KIND_PAYMENT = 'payment';
    public const KIND_ADDRESS = 'address';
    public const KIND_CORE = 'core';


    // Properties
    // =========================================================================

    public string $moduleId;
    public ?string $key;
    public ?string $kind;
    public array $targets;
    public array $surfaces;
    public array $config;
    public bool $required;


    // Public Methods
    // =========================================================================

    public function __construct(
        string|array $moduleId,
        ?string $key = null,
        ?string $kind = null,
        array $targets = [],
        array $surfaces = [self::SURFACE_SERVER_RENDERED],
        array $config = [],
        bool $required = true,
    ) {
        if (is_array($moduleId)) {
            $configValues = $moduleId;
            $unknownKeys = array_diff(array_keys($configValues), ['moduleId', 'key', 'kind', 'targets', 'surfaces', 'config', 'required']);
            if ($unknownKeys !== []) {
                throw new InvalidArgumentException('Unknown browser module declaration keys: ' . implode(', ', $unknownKeys));
            }
            $moduleId = (string)($configValues['moduleId'] ?? '');
            $key = isset($configValues['key']) ? (string)$configValues['key'] : $key;
            $kindValue = $configValues['kind'] ?? $kind;
            $kind = $kindValue === null ? null : (string)$kindValue;
            $targets = (array)($configValues['targets'] ?? $targets);
            $surfaces = (array)($configValues['surfaces'] ?? $surfaces);
            $config = (array)($configValues['config'] ?? $config);
            $required = (bool)($configValues['required'] ?? $required);
        }

        self::validateModuleId($moduleId);
        self::validateSurfaces($surfaces);
        self::validateTargets($targets);

        if ($kind !== null) {
            self::validateKind($kind);
        }

        $this->moduleId = $moduleId;
        $this->key = $key;
        $this->kind = $kind;
        $this->targets = array_values($targets);
        $this->surfaces = array_values(array_unique($surfaces));
        $this->config = $config;
        $this->required = $required;
    }

    public function supportsSurface(string $surface): bool
    {
        return in_array($surface, $this->surfaces, true);
    }

    public function withProjectionDefaults(string $kind, array $targets): self
    {
        return new self(
            moduleId: $this->moduleId,
            key: $this->key,
            kind: $this->kind ?: $kind,
            targets: $this->targets ?: $targets,
            surfaces: $this->surfaces,
            config: $this->config,
            required: $this->required,
        );
    }

    public function withConfig(array $config): self
    {
        return new self(
            moduleId: $this->moduleId,
            key: $this->key,
            kind: $this->kind,
            targets: $this->targets,
            surfaces: $this->surfaces,
            config: $config,
            required: $this->required,
        );
    }

    public static function validateModuleId(string $moduleId): void
    {
        if (!preg_match('/^[a-z][a-z0-9.-]*:[a-z][a-z0-9.-]*$/', $moduleId)) {
            throw new InvalidArgumentException('Browser module IDs must be namespaced registry identifiers: ' . $moduleId);
        }
    }

    public static function validateSurface(string $surface): void
    {
        if (!in_array($surface, [self::SURFACE_SERVER_RENDERED, self::SURFACE_CLIENT_RENDERED, self::SURFACE_CP_EDIT], true)) {
            throw new InvalidArgumentException('Invalid browser module surface.');
        }
    }

    public static function validateSurfaces(array $surfaces): void
    {
        if ($surfaces === []) {
            throw new InvalidArgumentException('Browser module declarations require at least one surface.');
        }

        foreach ($surfaces as $surface) {
            if (!is_string($surface)) {
                throw new InvalidArgumentException('Invalid browser module surface.');
            }

            self::validateSurface($surface);
        }
    }

    public static function validateKind(string $kind): void
    {
        if (!in_array($kind, [self::KIND_FIELD, self::KIND_CAPTCHA, self::KIND_PAYMENT, self::KIND_ADDRESS, self::KIND_CORE], true)) {
            throw new InvalidArgumentException('Invalid browser module kind.');
        }
    }

    public static function validateTargets(array $targets): void
    {
        foreach ($targets as $target) {
            if (!is_array($target)) {
                throw new InvalidArgumentException('Invalid browser module target.');
            }

            $type = $target['type'] ?? null;
            $valid = match ($type) {
                'form' => count($target) === 1,
                'field' => count($target) === 2 && is_string($target['uid'] ?? null) && $target['uid'] !== '',
                'page' => count($target) === 2 && is_string($target['id'] ?? null) && $target['id'] !== '',
                'action' => count($target) === 2 && is_string($target['action'] ?? null) && $target['action'] !== '',
                'selector' => count($target) === 2 && is_string($target['selector'] ?? null) && $target['selector'] !== '',
                default => false,
            };

            if (!$valid) {
                throw new InvalidArgumentException('Invalid browser module target.');
            }
        }
    }
}
