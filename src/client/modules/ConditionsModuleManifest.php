<?php
namespace verbb\formie\client\modules;

use verbb\formie\elements\Form;
use verbb\formie\helpers\CpSubmissionFieldConditions;
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

        $renderTargets = [ClientModule::RENDER_TARGET_FRONTEND];

        if ($form->cpSubmissionFollowsFieldConditions()) {
            $renderTargets[] = ClientModule::RENDER_TARGET_CP_EDIT;
        }

        $cpDisplayMode = CpSubmissionFieldConditions::clientDisplayMode($form->getCpSubmissionFieldConditions());

        return [
            new ClientModule([
                'id' => 'conditions',
                'type' => 'field',
                'renderTargets' => $renderTargets,
                'config' => [
                    'cpDisplayMode' => $cpDisplayMode,
                ],
                'targets' => [[
                    'targetType' => 'form',
                    'targetId' => 'form',
                ]],
            ]),
        ];
    }
}
