<?php
namespace verbb\formie\client\modules;

use verbb\formie\elements\Form;
use verbb\formie\helpers\CpSubmissionFieldConditions;
use verbb\formie\models\BrowserModule;

class ConditionsModuleProvider implements BrowserModuleProviderInterface
{
    // Public Methods
    // =========================================================================

    public function build(Form $form, string $surface = BrowserModule::SURFACE_SERVER_RENDERED): array
    {
        if (!$form->hasConditions()) {
            return [];
        }

        $surfaces = [BrowserModule::SURFACE_SERVER_RENDERED, BrowserModule::SURFACE_CLIENT_RENDERED];

        if ($form->cpSubmissionFollowsFieldConditions()) {
            $surfaces[] = BrowserModule::SURFACE_CP_EDIT;
        }

        $cpDisplayMode = CpSubmissionFieldConditions::clientDisplayMode($form->getCpSubmissionFieldConditions());

        return [
            new BrowserModule([
                'moduleId' => 'formie:conditions',
                'kind' => 'core',
                'surfaces' => $surfaces,
                'config' => $surface === BrowserModule::SURFACE_CP_EDIT ? ['cpDisplayMode' => $cpDisplayMode] : [],
                'targets' => [[
                    'type' => 'form',
                ]],
            ]),
        ];
    }
}
