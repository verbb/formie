<?php
namespace verbb\formie\gql\interfaces;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\gql\types\CaptchaValueType;
use verbb\formie\gql\types\CsrfTokenType;
use verbb\formie\gql\types\FormSettingsType;
use verbb\formie\gql\types\generators\FormGenerator;

use Craft;
use craft\gql\interfaces\Element;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class FormInterface extends Element
{
    // Static Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return FormGenerator::class;
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
            'resolveType' => function(Form $value) {
                return $value->getGqlTypeName();
            },
        ]));

        FormGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'FormInterface';
    }

    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'handle' => [
                'name' => 'handle',
                'type' => Type::string(),
                'description' => 'The form’s handle.',
            ],
            'pages' => [
                'name' => 'pages',
                'type' => Type::listOf(PageInterface::getType()),
                'description' => 'The form’s pages.',
            ],
            'rows' => [
                'name' => 'rows',
                'type' => Type::listOf(RowInterface::getType()),
                'description' => 'The form’s rows.',
                'args' => [
                    'includeDisabled' => [
                        'name' => 'includeDisabled',
                        'description' => 'Whether to include fields with visibility "disabled".',
                        'type' => Type::boolean(),
                    ],
                ],
                'resolve' => function($source, $arguments) {
                    $includeDisabled = $arguments['includeDisabled'] ?? false;

                    return $source->getRows($includeDisabled);
                },
            ],
            'formFields' => [
                'name' => 'formFields',
                'type' => Type::listOf(FieldInterface::getType()),
                'description' => 'The form’s fields.',
                'args' => [
                    'includeDisabled' => [
                        'name' => 'includeDisabled',
                        'description' => 'Whether to include fields with visibility "disabled".',
                        'type' => Type::boolean(),
                    ],
                ],
                'resolve' => function($source, $arguments) {
                    $includeDisabled = $arguments['includeDisabled'] ?? false;

                    return $source->getFields($includeDisabled);
                },
            ],
            'settings' => [
                'name' => 'settings',
                'type' => FormSettingsType::getType(),
                'description' => 'The form’s settings.',
            ],
            'configJson' => [
                'name' => 'configJson',
                'type' => Type::string(),
                'description' => 'The form’s config as JSON.',
            ],
            'templateHtml' => [
                'name' => 'templateHtml',
                'type' => Type::string(),
                'description' => 'The form’s rendered HTML.',
                'args' => [
                    'options' => [
                        'name' => 'options',
                        'description' => 'The form template HTML will be rendered with these JSON serialized options.',
                        'type' => Type::string(),
                    ],
                    'populateFormValues' => [
                        'name' => 'populateFormValues',
                        'description' => 'The form field values will be populated with these JSON serialized options.',
                        'type' => Type::string(),
                    ],
                ],
                'resolve' => function($source, $arguments) {
                    $options = Json::decodeIfJson($arguments['options'] ?? []);
                    $populateFormValues = Json::decodeIfJson($arguments['populateFormValues'] ?? []);

                    if ($populateFormValues) {
                        Formie::$plugin->getRendering()->populateFormValues($source, $populateFormValues);
                    }

                    return Formie::$plugin->getRendering()->renderForm($source, $options);
                },
            ],
            'templateCss' => [
                'name' => 'templateCss',
                'type' => Type::string(),
                'description' => 'The form’s CSS for rendering.',
                'resolve' => function($source, $arguments) {
                    return Formie::$plugin->getRendering()->renderCss(true);
                },
            ],
            'templateJs' => [
                'name' => 'templateJs',
                'type' => Type::string(),
                'description' => 'The form’s JS for rendering and functionality.',
                'args' => [
                    'useObserver' => [
                        'name' => 'useObserver',
                        'description' => 'Whether to automatically initialize the form’s JS when visible.',
                        'type' => Type::boolean(),
                    ],
                    'initJs' => [
                        'name' => 'initJs',
                        'description' => 'Whether to automatically initialize the form’s JS.',
                        'type' => Type::boolean(),
                    ],
                ],
                'resolve' => function($source, $arguments) {
                    return Formie::$plugin->getRendering()->renderJs(true, $arguments);
                },
            ],
            'csrfToken' => [
                'name' => 'csrfToken',
                'type' => CsrfTokenType::getType(),
                'description' => 'A CSRF token (name and value).',
                'resolve' => function() {
                    if (!Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
                        return null;
                    }

                    return [
                        'name' => Craft::$app->getRequest()->csrfParam,
                        'value' => Craft::$app->getRequest()->getCsrfToken(),
                    ];
                },
            ],
            'captchas' => [
                'name' => 'captchas',
                'type' => Type::listOf(CaptchaValueType::getType()),
                'description' => 'A list of captcha values (name and value) to assist with spam protection.',
                'resolve' => function ($source, $arguments) {
                    $values = [];

                    $captchas = Formie::$plugin->getIntegrations()->getAllEnabledCaptchasForForm($source);

                    foreach ($captchas as $captcha) {
                        if ($jsVariables = $captcha->getGqlVariables($source)) {
                            $values[] = [
                                'handle' => $captcha->getGqlHandle(),
                                'name' => $jsVariables['sessionKey'] ?? '',
                                'value' => $jsVariables['value'] ?? '',
                            ];
                        }
                    }

                    return $values;
                },
            ],
            'submissionMutationName' => [
                'name' => 'submissionMutationName',
                'type' => Type::string(),
                'description' => 'The form’s GQL mutation name for submissions to use.',
                'resolve' => function ($source) {
                    return Submission::gqlMutationNameByContext($source);
                },
            ],
            'submissionEndpoint' => [
                'name' => 'submissionEndpoint',
                'type' => Type::string(),
                'description' => 'The form’s endpoint for sending submissions to, if using POST requests.',
                'resolve' => function ($source) {
                    return UrlHelper::actionUrl('formie/submissions/submit');
                },
            ],
            'isAvailable' => [
                'name' => 'isAvailable',
                'type' => Type::boolean(),
                'description' => 'Whether the form is considered available according to user checks, scheduling and more.',
                'resolve' => function ($source) {
                    return $source->isAvailable();
                },
            ],
        ]), self::getName());
    }
}
