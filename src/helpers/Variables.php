<?php
namespace verbb\formie\helpers;

use verbb\formie\base\FieldInterface;
use verbb\formie\elements\Submission;
use verbb\formie\fields\definitions\FieldValueType;
use verbb\formie\models\Notification;
use verbb\formie\Formie;
use verbb\formie\references\BuiltinReferenceTransforms;
use verbb\formie\references\ReferenceContext;
use verbb\formie\references\ReferenceDefinition;
use verbb\formie\references\ReferenceResolver;

use Craft;

class Variables
{
    // Static Methods
    // =========================================================================

    /**
     * Returns the full variable config for the form builder: category config, labels, and order.
     * Consumed by the client to resolve variable picker options (static + form fields).
     * Includes labels/order for builder categories and for sub-groups (fieldsVariables, formVariables, etc.).
     * Form builder only shows token-based groups: Fields, Form, General, Users.
     * We do not expose "Email", "Number", "Plain Text", or "Calculations" as display categories;
     * those keys are only used to look up config (which static variables + field types to include).
     */
    public static function getFormBuilderVariableConfig(): array
    {
        $config = self::getCategoryConfig();

        $labels = [
            self::GROUP_FIELDS => Craft::t('formie', 'Fields'),
            self::GROUP_FORM => Craft::t('formie', 'Form'),
            self::GROUP_SUBMISSION => Craft::t('formie', 'Submission'),
            self::GROUP_SYSTEM => Craft::t('formie', 'System'),
            self::GROUP_CURRENT_TIME => Craft::t('formie', 'Current Time'),
            self::GROUP_ENVIRONMENT => Craft::t('formie', 'Environment'),
            self::GROUP_CURRENT_SITE => Craft::t('formie', 'Site'),
            self::GROUP_CURRENT_USER => Craft::t('formie', 'Users'),
            self::GROUP_DISPATCH => Craft::t('formie', 'Dispatch'),
            self::GROUP_CUSTOM => Craft::t('formie', 'Custom'),
            self::STATIC_FORM => Craft::t('formie', 'Form'),
            self::STATIC_GENERAL => Craft::t('formie', 'General'),
            self::STATIC_SITE => Craft::t('formie', 'Site'),
            self::STATIC_DISPATCH => Craft::t('formie', 'Dispatch'),
            self::STATIC_CUSTOM => Craft::t('formie', 'Custom'),
        ];

        $order = [
            self::GROUP_FIELDS,
            self::STATIC_FORM,
            self::STATIC_DISPATCH,
            self::STATIC_GENERAL,
            self::STATIC_CUSTOM,
            self::STATIC_SITE,
            self::GROUP_FORM,
            self::GROUP_SUBMISSION,
            self::GROUP_SYSTEM,
            self::GROUP_CURRENT_TIME,
            self::GROUP_ENVIRONMENT,
            self::GROUP_CURRENT_SITE,
            self::GROUP_CURRENT_USER,
        ];

        return [
            'variableCategoriesConfig' => $config,
            'variableCategoryLabels' => $labels,
            'variableCategoryOrder' => $order,
        ];
    }

    public static function getReportExportFilenameVariableConfig(): array
    {
        $categoryConfig = self::getCategoryConfig();
        $staticGroups = $categoryConfig['staticGroups'] ?? [];

        return [
            'variableCategories' => [
                'report' => [
                    self::_reportPickerSource('handle', Craft::t('formie', 'Report Handle')),
                    self::_reportPickerSource('name', Craft::t('formie', 'Report Name')),
                ],
                'general' => array_values(array_merge(
                    $staticGroups[self::GROUP_CURRENT_TIME] ?? [],
                    $staticGroups[self::GROUP_SYSTEM] ?? [],
                )),
                'site' => array_values($staticGroups[self::GROUP_CURRENT_SITE] ?? []),
            ],
            'variableCategoryLabels' => [
                'report' => Craft::t('formie', 'Report'),
                'general' => Craft::t('formie', 'General'),
                'site' => Craft::t('formie', 'Site'),
            ],
            'variableCategoryOrder' => ['report', 'general', 'site'],
            'variableTransformerRegistry' => $categoryConfig['transformerRegistry'] ?? [],
        ];
    }

    /**
     * Returns variable picker configuration used by variableConfig:
     * - staticGroups: grouped static variable catalogs
     * - groupAliases: macro groups (STATIC_*) expanded client-side
     * - transformerRegistry: available v1 transforms by value type
     */
    public static function getCategoryConfig(): array
    {
        return [
            'groupAliases' => [
                self::STATIC_FORM => [self::GROUP_FORM, self::GROUP_SUBMISSION, self::GROUP_DISPATCH],
                self::STATIC_DISPATCH => [self::GROUP_DISPATCH],
                self::STATIC_GENERAL => [self::GROUP_SYSTEM, self::GROUP_ENVIRONMENT, self::GROUP_CURRENT_TIME, self::GROUP_CUSTOM],
                self::STATIC_SITE => [self::GROUP_CURRENT_SITE, self::GROUP_CURRENT_USER],
                self::STATIC_CUSTOM => [self::GROUP_CUSTOM],
            ],
            'staticGroups' => Formie::$plugin->getReferenceCatalogue()->pickerGroups(),
            'transformerRegistry' => self::_getTransformerRegistry(),
        ];
    }

    /**
     * Returns the merged list of variable definitions (with headings) for migrations/legacy use.
     */
    public static function getVariables(): array
    {
        $variables = [];
        $walk = static function(array $items) use (&$variables, &$walk): void {
            foreach ($items as $item) {
                if (isset($item['value'])) {
                    $variables[] = $item;
                }

                if (isset($item['children'])) {
                    $walk($item['children']);
                }
            }
        };

        foreach (Formie::$plugin->getReferenceCatalogue()->pickerGroups() as $items) {
            $walk($items);
        }
        return $variables;
    }

    public static function getFieldForReference(string $refValue, Submission $submission): ?FieldInterface
    {
        $expression = References::parseReferenceExpression($refValue);
        return $expression->isValid && $expression->target === 'field'
            ? (new ReferenceResolver())->fieldFor($expression->identifier, ReferenceContext::forSubmission($submission))
            : null;
    }

    /**
     * Returns both the field (when the reference is a field reference) and the resolved value
     * with a single parse and single variables lookup. Use this when you need both to avoid
     * calling getFieldForReference() and References::parseValue() separately.
     */
    public static function getFieldAndValueForReference(string $refValue, Submission $submission, ?array $variables = null): array
    {
        $result = References::resolveValue($refValue, ReferenceContext::forSubmission($submission));
        return ['field' => $result->field, 'value' => $result->requireValue(), 'result' => $result];
    }

    public static function applyVariableTransformer(mixed $value, string $transformerId, array $params = []): mixed
    {
        return BuiltinReferenceTransforms::apply($value, $transformerId, $params);
    }

    public static function getSummaryVariables(Submission $submission, Notification $notification): array
    {
        $allFields = [];
        $allContentFields = [];
        $allVisibleFields = [];

        // Build expensive summary variables used by multi-line content references.
        foreach ($submission->getFields() as $field) {
            if (!$field->includeInEmailFieldSummaries || $field->isConditionallyHidden($submission)) {
                continue;
            }

            $value = $submission->getFieldValue($field->valueKey());

            $allFields[] = $field;

            if (!$field->isValueEmpty($value, $submission)) {
                $allContentFields[] = $field;
            }

            if (!$field->getIsHidden()) {
                $allVisibleFields[] = $field;
            }
        }

        return [
            'allFields' => self::_renderSummaryTemplate($notification, $submission, 'all-fields', $allFields),
            'allContentFields' => self::_renderSummaryTemplate($notification, $submission, 'all-content-fields', $allContentFields),
            'allVisibleFields' => self::_renderSummaryTemplate($notification, $submission, 'all-visible-fields', $allVisibleFields),
        ];
    }

    private static function _getTransformerRegistry(): array
    {
        $registry = BuiltinReferenceTransforms::pickerDefinitions();

        foreach (Formie::$plugin->getReferenceCatalogue()->pickerTransforms() as $type => $transforms) {
            $registry[$type] = [...($registry[$type] ?? []), ...$transforms];
        }
        return $registry;
    }

    private static function _renderSummaryTemplate(Notification $notification, Submission $submission, string $template, array $fields): string
    {
        if (!$fields) {
            return '';
        }

        $html = $notification->renderTemplate($template, [
            'notification' => $notification,
            'submission' => $submission,
            'fields' => $fields,
        ]);

        return StringHelper::cleanString($html);
    }

    private static function _reportPickerSource(string $identifier, string $label): array
    {
        $definition = new ReferenceDefinition(
            id: 'report:' . $identifier,
            label: $label,
            category: 'report',
            valueType: FieldValueType::string(),
        );

        // Report filenames retain their concise stored tokens while using the
        // canonical picker metadata shared by the reference catalogue.
        return $definition->toPickerSource('{' . $identifier . '}');
    }


    // Constants
    // =========================================================================

    public const TARGET_CUSTOM = 'custom';
    public const TYPE_TEXT = 'text';
    public const TYPE_EMAIL = 'email';
    public const TYPE_NUMBER = 'number';
    public const TYPE_CALCULATIONS = 'calculations';
    public const TYPE_URL = 'url';
    public const TYPE_DATE = 'date';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_ARRAY = 'array';

    public const GROUP_FIELDS = 'fieldsVariables';
    public const GROUP_FORM = 'formVariables';
    public const GROUP_SUBMISSION = 'submissionVariables';
    public const GROUP_SYSTEM = 'systemVariables';
    public const GROUP_CURRENT_TIME = 'currentTimeVariables';
    public const GROUP_ENVIRONMENT = 'environmentVariables';
    public const GROUP_CURRENT_SITE = 'siteVariables';
    public const GROUP_CURRENT_USER = 'userVariables';
    public const GROUP_DISPATCH = 'dispatchVariables';
    public const GROUP_CUSTOM = 'customVariables';

    public const STATIC_FIELDS = self::GROUP_FIELDS;
    public const STATIC_FORM = 'staticFormVariables';
    public const STATIC_GENERAL = 'staticGeneralVariables';
    public const STATIC_SITE = 'staticSiteVariables';
    public const STATIC_DISPATCH = 'staticDispatchVariables';
    public const STATIC_CUSTOM = 'staticCustomVariables';
}
