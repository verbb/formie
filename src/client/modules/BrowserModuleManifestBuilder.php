<?php
namespace verbb\formie\client\modules;

use verbb\formie\elements\Form;
use verbb\formie\events\RegisterBrowserModulesEvent;
use verbb\formie\models\BrowserModule;
use verbb\formie\models\BrowserModuleEntry;
use verbb\formie\models\BrowserModuleManifest;

use yii\base\Component;

use InvalidArgumentException;

class BrowserModuleManifestBuilder extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_MODULES = 'registerModules';


    // Public Methods
    // =========================================================================

    public function buildForSurface(Form $form, string $surface = BrowserModule::SURFACE_SERVER_RENDERED): BrowserModuleManifest
    {
        BrowserModule::validateSurface($surface);

        $entries = [];
        $occurrences = [];
        $modules = [];

        foreach ([new ConditionsModuleProvider(), new FieldModuleProvider(), new CaptchaModuleProvider()] as $provider) {
            array_push($modules, ...$provider->build($form, $surface));
        }

        $event = new RegisterBrowserModulesEvent(['form' => $form, 'surface' => $surface]);
        $this->trigger(self::EVENT_REGISTER_MODULES, $event);

        foreach ($event->modules as $module) {
            if (!$module instanceof BrowserModule) {
                throw new InvalidArgumentException('Form module contributions must be BrowserModule declarations.');
            }
            $modules[] = $module->withProjectionDefaults(BrowserModule::KIND_CORE, [['type' => 'form']]);
        }

        foreach ($modules as $module) {
            if (!$module instanceof BrowserModule || !$module->supportsSurface($surface)) {
                continue;
            }

            if (!$module->kind || !$module->targets) {
                throw new InvalidArgumentException('Browser module declarations must resolve kind and targets before manifest projection.');
            }

            $identity = $this->_entryIdentity($module);
            $occurrence = $occurrences[$identity] ?? 0;
            $occurrences[$identity] = $occurrence + 1;
            $entries[] = new BrowserModuleEntry(
                key: $module->key ?: $identity . ':' . $occurrence,
                moduleId: $module->moduleId,
                kind: $module->kind,
                targets: $module->targets,
                config: $module->config,
                required: $module->required,
            );
        }

        return new BrowserModuleManifest($surface, $entries);
    }


    // Private Methods
    // =========================================================================

    private function _entryIdentity(BrowserModule $module): string
    {
        if (count($module->targets) === 1) {
            $target = $module->targets[0];
            $targetIdentity = match ($target['type']) {
                'form' => 'form',
                'field' => 'field:' . $target['uid'],
                'page' => 'page:' . $target['id'],
                'action' => 'action:' . $target['action'],
                'selector' => 'selector:' . hash('sha256', $target['selector']),
            };

            return $targetIdentity . ':' . $module->moduleId;
        }

        return 'targets:' . hash('sha256', json_encode($module->targets, JSON_THROW_ON_ERROR)) . ':' . $module->moduleId;
    }
}
