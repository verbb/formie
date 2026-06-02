<?php
namespace verbb\formie\fields\definitions;

use verbb\formie\models\ClientModule;
use verbb\formie\models\ClientModuleContext;

use yii\base\BaseObject;

/**
 * Collects the lazy browser modules a field can contribute to managed clients.
 */
class FieldClientModules extends BaseObject
{
    // Static Methods
    // =========================================================================

    public static function make(): self
    {
        return new self();
    }


    // Properties
    // =========================================================================

    public array $modules = [];


    // Public Methods
    // =========================================================================

    public function withModule(array|ClientModule|callable $module): self
    {
        $this->modules[] = $module;

        return $this;
    }

    public function withModules(array $modules): self
    {
        foreach ($modules as $module) {
            $this->withModule($module);
        }

        return $this;
    }

    public function toModules(ClientModuleContext $context): array
    {
        $modules = [];

        foreach ($this->modules as $module) {
            $resolvedModule = is_callable($module) ? $module($context) : $module;

            foreach ($this->_normalizeResolvedModules($resolvedModule, $context) as $normalizedModule) {
                $modules[] = $normalizedModule;
            }
        }

        return $modules;
    }

    public function getModuleIds(ClientModuleContext $context): array
    {
        return array_values(array_filter(array_map(static function(ClientModule $module) {
            return $module->id ? (string)$module->id : null;
        }, $this->toModules($context))));
    }


    // Private Methods
    // =========================================================================

    private function _normalizeResolvedModules(array|ClientModule|null $module, ClientModuleContext $context): array
    {
        if ($module === null) {
            return [];
        }

        if (is_array($module) && array_is_list($module)) {
            if ($module === []) {
                return [];
            }

            return array_values(array_filter(array_merge(...array_map(function(array|ClientModule|null $item) use ($context) {
                return $this->_normalizeResolvedModules($item, $context);
            }, $module))));
        }

        $normalizedModule = $module instanceof ClientModule ? $module->toArray() : $module;

        if (!is_array($normalizedModule)) {
            return [];
        }

        if (!isset($normalizedModule['type']) || !$normalizedModule['type']) {
            $normalizedModule['type'] = 'field';
        }

        if (!isset($normalizedModule['targets']) || !$normalizedModule['targets']) {
            $normalizedModule['targets'] = $context->getTargets();
        }

        return [new ClientModule($normalizedModule)];
    }
}
