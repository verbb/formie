<?php
namespace verbb\formie\client\modules;

use verbb\formie\elements\Form;
use verbb\formie\models\ClientModule;

use craft\helpers\Json;

use yii\base\Component;

class ClientModuleManifestBuilder extends Component
{
    // Properties
    // =========================================================================

    private array $_canonicalManifestByForm = [];


    // Public Methods
    // =========================================================================

    public function buildCanonical(Form $form, string $renderTarget = ClientModule::RENDER_TARGET_FRONTEND): array
    {
        $cacheKey = $this->_getFormCacheKey($form, $renderTarget);

        if (array_key_exists($cacheKey, $this->_canonicalManifestByForm)) {
            return $this->_canonicalManifestByForm[$cacheKey];
        }

        $dedupedModules = [];

        foreach ($this->_buildNormalizedManifest($form, $renderTarget) as $module) {
            $key = md5(Json::encode([
                $module['id'] ?? null,
                $module['src'] ?? null,
                $module['type'] ?? null,
                $module['targets'] ?? [],
                $module['renderTargets'] ?? [],
                $module['config'] ?? [],
            ]));
            
            $dedupedModules[$key] = $module;
        }

        return $this->_canonicalManifestByForm[$cacheKey] = array_values($dedupedModules);
    }


    // Private Methods
    // =========================================================================

    private function _buildNormalizedManifest(Form $form, string $renderTarget): array
    {
        $manifest = [];

        foreach ($this->_providers() as $provider) {
            foreach ($provider->build($form, $renderTarget) as $module) {
                $normalizedModule = $this->_normalizeModule($module);

                if (!$normalizedModule || !isset($normalizedModule['id'], $normalizedModule['type'])) {
                    continue;
                }

                if (!$this->_supportsRenderTarget($normalizedModule, $renderTarget)) {
                    continue;
                }

                $key = implode(':', [
                    $normalizedModule['id'],
                    md5(Json::encode($normalizedModule['src'] ?? null)),
                    $normalizedModule['type'],
                    md5(Json::encode($normalizedModule['targets'] ?? [])),
                    md5(Json::encode($normalizedModule['renderTargets'] ?? [])),
                    md5(Json::encode($normalizedModule['config'] ?? [])),
                ]);

                if (!isset($manifest[$key])) {
                    $manifest[$key] = $normalizedModule;
                    continue;
                }

                if (!empty($normalizedModule['config'])) {
                    $manifest[$key]['config'] = array_replace_recursive(
                        $manifest[$key]['config'] ?? [],
                        $normalizedModule['config']
                    );
                }
            }
        }

        return $manifest;
    }

    private function _providers(): array
    {
        return [
            new ConditionsModuleManifest(),
            new FieldModuleManifest(),
            new CaptchaModuleManifest(),
        ];
    }

    private function _normalizeModule(array|ClientModule|null $module): ?array
    {
        if ($module instanceof ClientModule) {
            return $module->toArray();
        }

        return $module;
    }

    private function _supportsRenderTarget(array $module, string $renderTarget): bool
    {
        $normalizedModule = new ClientModule($module);

        return $normalizedModule->supportsRenderTarget($renderTarget);
    }

    private function _getFormCacheKey(Form $form, string $renderTarget): string
    {
        $formId = (int)($form->id ?? 0);
        $siteId = (int)($form->siteId ?? 0);

        if ($formId) {
            return 'id:' . $formId . ':site:' . $siteId . ':target:' . $renderTarget;
        }

        return 'obj:' . spl_object_id($form) . ':target:' . $renderTarget;
    }
}
