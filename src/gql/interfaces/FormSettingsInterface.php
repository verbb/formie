<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\gql\types\generators\FormSettingsGenerator;
use verbb\formie\models\FormSettings;

use craft\gql\base\InterfaceType as BaseInterfaceType;
use craft\gql\interfaces\Element;
use craft\gql\types\DateTime;
use craft\gql\TypeLoader;
use craft\gql\TypeManager;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class FormSettingsInterface extends BaseInterfaceType
{
    // Public Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return FormSettingsGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all forms.',
            'resolveType' => function(FormSettings $value) {
                return GqlEntityRegistry::getEntity(FormSettingsGenerator::getName());
            },
        ]));

        FormSettingsGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'FormSettingsInterface';
    }

    public static function getFieldDefinitions(): array
    {
        return TypeManager::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'displayFormTitle' => [
                'name' => 'displayFormTitle',
                'type' => Type::boolean(),
                'description' => 'Whether to show the form’s title.'
            ],
            'displayPageTabs' => [
                'name' => 'displayPageTabs',
                'type' => Type::boolean(),
                'description' => 'Whether to show the form’s page tabs.'
            ],
            'displayCurrentPageTitle' => [
                'name' => 'displayCurrentPageTitle',
                'type' => Type::boolean(),
                'description' => 'Whether to show the form’s current page title.'
            ],
            'displayPageProgress' => [
                'name' => 'displayPageProgress',
                'type' => Type::boolean(),
                'description' => 'Whether to show the form’s page progress.'
            ],
            'submitMethod' => [
                'name' => 'submitMethod',
                'type' => Type::string(),
                'description' => 'The form’s submit method.'
            ],
            'submitAction' => [
                'name' => 'submitAction',
                'type' => Type::string(),
                'description' => 'The form’s submit action.'
            ],
            'submitActionTab' => [
                'name' => 'submitActionTab',
                'type' => Type::string(),
                'description' => 'The form’s submit redirect option (if in new tab or same tab).'
            ],
            'submitActionUrl' => [
                'name' => 'submitActionUrl',
                'type' => Type::string(),
                'description' => 'The form’s submit action URL.'
            ],
            'submitActionFormHide' => [
                'name' => 'submitActionFormHide',
                'type' => Type::boolean(),
                'description' => 'Whether to hide the form’s success message.'
            ],
            'submitActionMessageHtml' => [
                'name' => 'submitActionMessageHtml',
                'type' => Type::string(),
                'description' => 'The form’s submit success message.'
            ],
            'submitActionMessageTimeout' => [
                'name' => 'submitActionMessageTimeout',
                'type' => Type::int(),
                'description' => 'The form’s submit success message timeout.'
            ],
            'errorMessageHtml' => [
                'name' => 'errorMessageHtml',
                'type' => Type::string(),
                'description' => 'The form’s submit error message.'
            ],
            'loadingIndicator' => [
                'name' => 'loadingIndicator',
                'type' => Type::boolean(),
                'description' => 'Whether to show the form’s loading indicator.'
            ],
            'loadingIndicatorText' => [
                'name' => 'loadingIndicatorText',
                'type' => Type::string(),
                'description' => 'The form’s loading indicator text.'
            ],
            'validationOnSubmit' => [
                'name' => 'validationOnSubmit',
                'type' => Type::boolean(),
                'description' => 'Whether to validate the form’s on submit.'
            ],
            'validationOnFocus' => [
                'name' => 'validationOnFocus',
                'type' => Type::boolean(),
                'description' => 'Whether to validate the form’s on focus.'
            ],
            'defaultLabelPosition' => [
                'name' => 'defaultLabelPosition',
                'type' => Type::string(),
                'description' => 'The form’s default label position for fields.'
            ],
            'defaultInstructionsPosition' => [
                'name' => 'defaultInstructionsPosition',
                'type' => Type::string(),
                'description' => 'The form’s default instructions position for fields.'
            ],
            'progressPosition' => [
                'name' => 'progressPosition',
                'type' => Type::string(),
                'description' => 'The form’s progress bar position.'
            ],
        ]), self::getName());
    }
}
