<?php
namespace verbb\formie\client\modules;

use verbb\formie\elements\Form;
use verbb\formie\models\ClientModule;

class ConditionsModuleManifest implements ModuleManifestProviderInterface
{
    // Public Methods
    // =========================================================================

    public function build(Form $form, string $renderTarget = ClientModule::RENDER_TARGET_FRONTEND): array
    {
        if (!$form->hasConditions()) {
            return [];
        }

        return [
            new ClientModule([
                'id' => 'conditions',
                'type' => 'field',
                'renderTargets' => [ClientModule::RENDER_TARGET_FRONTEND],
                'targets' => [[
                    'targetType' => 'form',
                    'targetId' => 'form',
                ]],
            ]),
        ];
    }
}
