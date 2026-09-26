<?php
namespace verbb\formie\client\modules;

use verbb\formie\elements\Form;
use verbb\formie\helpers\CpSubmissionFieldConditions;
use verbb\formie\models\BrowserModuleEntry;

class ConditionsModuleProvider implements BrowserModuleProviderInterface
{
    // Public Methods
    // =========================================================================

    public function build(Form $form, string $surface = BrowserModuleEntry::SURFACE_SERVER_RENDERED): array
    {
        if (!$form->hasConditions()) {
            return [];
        }

        $surfaces = [BrowserModuleEntry::SURFACE_SERVER_RENDERED, BrowserModuleEntry::SURFACE_CLIENT_RENDERED];

        if ($form->cpSubmissionFollowsFieldConditions()) {
            $surfaces[] = BrowserModuleEntry::SURFACE_CP_EDIT;
        }

        $cpDisplayMode = CpSubmissionFieldConditions::clientDisplayMode($form->getCpSubmissionFieldConditions());

        return [
            new BrowserModuleEntry([
                'moduleId' => 'formie:conditions',
                'type' => 'field',
                'surfaces' => $surfaces,
                'config' => $surface === BrowserModuleEntry::SURFACE_CP_EDIT ? ['cpDisplayMode' => $cpDisplayMode] : [],
                'targets' => [[
                    'targetType' => 'form',
                    'targetId' => 'form',
                ]],
            ]),
        ];
    }
}
