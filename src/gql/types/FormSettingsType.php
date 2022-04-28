<?php
namespace verbb\formie\gql\types;

use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\arguments\elements\Entry as EntryArguments;

use GraphQL\Type\Definition\Type;

class FormSettingsType extends ObjectType
{
    // Static Methods
    // =========================================================================

    public static function getName(): string
    {
        return 'FormSettingsType';
    }

    public static function getType()
    {
        return GqlEntityRegistry::getEntity(self::getName()) ?: GqlEntityRegistry::createEntity(self::getName(), new self([
            'name' => self::getName(),
            'fields' => [
                'displayFormTitle' => [
                    'name' => 'displayFormTitle',
                    'type' => Type::boolean(),
                    'description' => 'Whether to show the form’s title.',
                ],
                'displayPageTabs' => [
                    'name' => 'displayPageTabs',
                    'type' => Type::boolean(),
                    'description' => 'Whether to show the form’s page tabs.',
                ],
                'displayCurrentPageTitle' => [
                    'name' => 'displayCurrentPageTitle',
                    'type' => Type::boolean(),
                    'description' => 'Whether to show the form’s current page title.',
                ],
                'displayPageProgress' => [
                    'name' => 'displayPageProgress',
                    'type' => Type::boolean(),
                    'description' => 'Whether to show the form’s page progress.',
                ],
                'scrollToTop' => [
                    'name' => 'scrollToTop',
                    'type' => Type::boolean(),
                    'description' => 'Whether to the form should scroll to the top of the page when submitted.',
                ],
                'submitMethod' => [
                    'name' => 'submitMethod',
                    'type' => Type::string(),
                    'description' => 'The form’s submit method.',
                ],
                'submitAction' => [
                    'name' => 'submitAction',
                    'type' => Type::string(),
                    'description' => 'The form’s submit action.',
                ],
                'submitActionTab' => [
                    'name' => 'submitActionTab',
                    'type' => Type::string(),
                    'description' => 'The form’s submit redirect option (if in new tab or same tab).',
                ],
                'submitActionUrl' => [
                    'name' => 'submitActionUrl',
                    'type' => Type::string(),
                    'description' => 'The form’s submit action URL.',
                ],
                'submitActionFormHide' => [
                    'name' => 'submitActionFormHide',
                    'type' => Type::boolean(),
                    'description' => 'Whether to hide the form’s success message.',
                ],
                'submitActionMessageHtml' => [
                    'name' => 'submitActionMessageHtml',
                    'type' => Type::string(),
                    'description' => 'The form’s submit success message.',
                ],
                'submitActionMessageTimeout' => [
                    'name' => 'submitActionMessageTimeout',
                    'type' => Type::int(),
                    'description' => 'The form’s submit success message timeout.',
                    'resolve' => function($class) {
                        return (int)$class->submitActionMessageTimeout;
                    },
                ],
                'submitActionMessagePosition' => [
                    'name' => 'submitActionMessagePosition',
                    'type' => Type::string(),
                    'description' => 'The form’s submit message position.',
                ],
                'redirectUrl' => [
                    'name' => 'redirectUrl',
                    'type' => Type::string(),
                    'description' => 'The form’s submit action redirect URL.',
                    'resolve' => function($class) {
                        return $class->getFormRedirectUrl(false);
                    },
                ],
                'redirectEntry' => [
                    'name' => 'redirectEntry',
                    'type' => EntryInterface::getType(),
                    'args' => EntryArguments::getArguments(),
                    'description' => 'The form’s submit action entry (for redirection).',
                ],
                'errorMessageHtml' => [
                    'name' => 'errorMessageHtml',
                    'type' => Type::string(),
                    'description' => 'The form’s submit error message.',
                ],
                'errorMessagePosition' => [
                    'name' => 'errorMessagePosition',
                    'type' => Type::string(),
                    'description' => 'The form’s error message position.',
                ],
                'loadingIndicator' => [
                    'name' => 'loadingIndicator',
                    'type' => Type::boolean(),
                    'description' => 'Whether to show the form’s loading indicator.',
                ],
                'loadingIndicatorText' => [
                    'name' => 'loadingIndicatorText',
                    'type' => Type::string(),
                    'description' => 'The form’s loading indicator text.',
                ],
                'validationOnSubmit' => [
                    'name' => 'validationOnSubmit',
                    'type' => Type::boolean(),
                    'description' => 'Whether to validate the form’s on submit.',
                ],
                'validationOnFocus' => [
                    'name' => 'validationOnFocus',
                    'type' => Type::boolean(),
                    'description' => 'Whether to validate the form’s on focus.',
                ],
                'defaultLabelPosition' => [
                    'name' => 'defaultLabelPosition',
                    'type' => Type::string(),
                    'description' => 'The form’s default label position for fields.',
                ],
                'defaultInstructionsPosition' => [
                    'name' => 'defaultInstructionsPosition',
                    'type' => Type::string(),
                    'description' => 'The form’s default instructions position for fields.',
                ],
                'progressPosition' => [
                    'name' => 'progressPosition',
                    'type' => Type::string(),
                    'description' => 'The form’s progress bar position.',
                ],
                'integrations' => [
                    'name' => 'integrations',
                    'type' => Type::listOf(FormIntegrationsType::getType()),
                    'description' => 'The form’s integrations.',
                    'resolve' => function($source, $arguments) {
                        return $source->getEnabledIntegrations();
                    },
                ],
            ],
        ]));
    }
}
