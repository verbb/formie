<?php
namespace verbb\formie\gql\types;

use verbb\formie\gql\types\Json as JsonType;
use verbb\formie\helpers\Gql as FormieGql;

use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\base\ObjectType;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Entry as EntryInterface;

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
                // Appearance
                'displayFormTitle' => [
                    'name' => 'displayFormTitle',
                    'type' => Type::boolean(),
                    'description' => 'Whether to show the form’s title.',
                ],
                'displayCurrentPageTitle' => [
                    'name' => 'displayCurrentPageTitle',
                    'type' => Type::boolean(),
                    'description' => 'Whether to show the form’s current page title.',
                ],
                'displayPageTabs' => [
                    'name' => 'displayPageTabs',
                    'type' => Type::boolean(),
                    'description' => 'Whether to show the form’s page tabs.',
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
                'progressPosition' => [
                    'name' => 'progressPosition',
                    'type' => Type::string(),
                    'description' => 'The form’s progress bar position. Either `start` or `end`.',
                ],
                'progressValuePosition' => [
                    'name' => 'progressValuePosition',
                    'type' => Type::string(),
                    'description' => 'The form’s progress bar value position. Either `left`, `right`, `inside-left`, `inside-center`, `inside-right` or `hidden`.',
                ],
                'defaultLabelPosition' => [
                    'name' => 'defaultLabelPosition',
                    'type' => Type::string(),
                    'description' => 'The form’s default label position for fields. This will be a `verbb\formie\positions` class name.',
                ],
                'defaultInstructionsPosition' => [
                    'name' => 'defaultInstructionsPosition',
                    'type' => Type::string(),
                    'description' => 'The form’s default instructions position for fields. This will be a `verbb\formie\positions` class name.',
                ],
                'defaultErrorMessagePosition' => [
                    'name' => 'defaultErrorMessagePosition',
                    'type' => Type::string(),
                    'description' => 'The form’s default validation error position for fields. This will be a `verbb\formie\positions` class name.',
                ],
                'requiredIndicator' => [
                    'name' => 'requiredIndicator',
                    'type' => Type::string(),
                    'description' => 'The form’s required fields indicator. Either `asterisk` or `optional`.',
                ],

                // Behaviour
                'submitMethod' => [
                    'name' => 'submitMethod',
                    'type' => Type::string(),
                    'description' => 'The form’s submit method. Either `page-reload` or `ajax`.',
                ],
                'completionBehavior' => [
                    'name' => 'completionBehavior',
                    'type' => Type::string(),
                    'description' => 'The form’s completion behavior. Either `message`, `redirect`, `reload` or `reset`.',
                ],
                'completionRedirectSource' => [
                    'name' => 'completionRedirectSource',
                    'type' => Type::string(),
                    'description' => 'The source used by redirect completion. Either `url` or `entry`.',
                ],
                'redirectTarget' => [
                    'name' => 'redirectTarget',
                    'type' => Type::string(),
                    'description' => 'The form’s submit redirect option (if in new tab or same tab). Either `same-tab` or `new-tab`.',
                ],
                'hideFormAfterSubmit' => [
                    'name' => 'hideFormAfterSubmit',
                    'type' => Type::boolean(),
                    'description' => 'Whether to hide the form’s success message.',
                ],
                'automaticSubmissionState' => [
                    'name' => 'automaticSubmissionState',
                    'type' => Type::boolean(),
                    'description' => 'Whether to automatically restore an in-progress submission when the visitor returns to the form.',
                ],
                'successMessageHtml' => [
                    'name' => 'successMessageHtml',
                    'type' => Type::string(),
                    'description' => 'The form’s submit success message.',
                ],
                'successMessageJson' => [
                    'name' => 'successMessageJson',
                    'type' => JsonType::getType(),
                    'description' => 'The form’s submit success message as stored rich-text JSON (`type: doc`). Variable tags are not resolved.',
                    'resolve' => static fn($settings) => FormieGql::resolveRichTextJson($settings->successMessage),
                ],
                'successMessageTimeout' => [
                    'name' => 'successMessageTimeout',
                    'type' => Type::int(),
                    'description' => 'The form’s submit success message timeout in seconds.',
                    'resolve' => function($class) {
                        return (int)$class->successMessageTimeout;
                    },
                ],
                'successMessagePosition' => [
                    'name' => 'successMessagePosition',
                    'type' => Type::string(),
                    'description' => 'The form’s submit message position. Either `top-form` or `bottom-form`.',
                ],
                'submitAction' => [
                    'name' => 'submitAction',
                    'type' => Type::string(),
                    'description' => 'Deprecated Formie 3 completion action.',
                    'deprecationReason' => 'Use `completionBehavior` and `completionRedirectSource`.',
                    'resolve' => static fn($settings) => $settings->getSubmitAction(),
                ],
                'submitActionTab' => [
                    'name' => 'submitActionTab',
                    'type' => Type::string(),
                    'description' => 'Deprecated Formie 3 redirect target.',
                    'deprecationReason' => 'Use `redirectTarget`.',
                    'resolve' => static fn($settings) => $settings->redirectTarget,
                ],
                'submitActionFormHide' => [
                    'name' => 'submitActionFormHide',
                    'type' => Type::boolean(),
                    'description' => 'Deprecated Formie 3 form visibility setting.',
                    'deprecationReason' => 'Use `hideFormAfterSubmit`.',
                    'resolve' => static fn($settings) => $settings->hideFormAfterSubmit,
                ],
                'submitActionMessageHtml' => [
                    'name' => 'submitActionMessageHtml',
                    'type' => Type::string(),
                    'description' => 'Deprecated Formie 3 success message.',
                    'deprecationReason' => 'Use `successMessageHtml`.',
                    'resolve' => static fn($settings) => $settings->getSuccessMessageHtml(),
                ],
                'submitActionMessageJson' => [
                    'name' => 'submitActionMessageJson',
                    'type' => JsonType::getType(),
                    'description' => 'Deprecated Formie 3 success-message rich-text JSON.',
                    'deprecationReason' => 'Use `successMessageJson`.',
                    'resolve' => static fn($settings) => FormieGql::resolveRichTextJson($settings->successMessage),
                ],
                'submitActionMessageTimeout' => [
                    'name' => 'submitActionMessageTimeout',
                    'type' => Type::int(),
                    'description' => 'Deprecated Formie 3 success-message timeout.',
                    'deprecationReason' => 'Use `successMessageTimeout`.',
                    'resolve' => static fn($settings) => (int)$settings->successMessageTimeout,
                ],
                'submitActionMessagePosition' => [
                    'name' => 'submitActionMessagePosition',
                    'type' => Type::string(),
                    'description' => 'Deprecated Formie 3 success-message position.',
                    'deprecationReason' => 'Use `successMessagePosition`.',
                    'resolve' => static fn($settings) => $settings->successMessagePosition,
                ],
                'loadingIndicator' => [
                    'name' => 'loadingIndicator',
                    'type' => Type::string(),
                    'description' => 'The type of loading indicator to use. Either `spinner` or `text`.',
                ],
                'loadingIndicatorText' => [
                    'name' => 'loadingIndicatorText',
                    'type' => Type::string(),
                    'description' => 'The form’s loading indicator text.',
                ],

                // Behaviour - Validation
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
                'disableSubmitButtonUntilValid' => [
                    'name' => 'disableSubmitButtonUntilValid',
                    'type' => Type::boolean(),
                    'description' => 'Whether to disable the submit button until the current page passes validation.',
                ],
                'errorMessageHtml' => [
                    'name' => 'errorMessageHtml',
                    'type' => Type::string(),
                    'description' => 'The form’s submit error message.',
                ],
                'errorMessageJson' => [
                    'name' => 'errorMessageJson',
                    'type' => JsonType::getType(),
                    'description' => 'The form’s submit error message as stored rich-text JSON (`type: doc`).',
                    'resolve' => static fn($settings) => FormieGql::resolveRichTextJson($settings->errorMessage),
                ],
                'errorMessagePosition' => [
                    'name' => 'errorMessagePosition',
                    'type' => Type::string(),
                    'description' => 'The form’s error message position. Either `null`, `top-form` or `bottom-form`.',
                ],

                // Other
                'redirectUrl' => [
                    'name' => 'redirectUrl',
                    'type' => Type::string(),
                    'description' => 'The resolved completion redirect URL.',
                    'resolve' => function($class) {
                        return $class->getFormRedirectUrl(false);
                    },
                ],
                'redirectEntry' => [
                    'name' => 'redirectEntry',
                    'type' => EntryInterface::getType(),
                    'args' => EntryArguments::getArguments(),
                    'description' => 'The entry selected as the completion redirect source.',
                ],
                'integrations' => [
                    'name' => 'integrations',
                    'type' => Type::listOf(FormIntegrationsType::getType()),
                    'description' => 'The form’s enabled integrations.',
                    'resolve' => function($source, $arguments) {
                        return $source->getEnabledIntegrations();
                    },
                ],
            ],
        ]));
    }
}
