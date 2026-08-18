<?php
namespace verbb\formie\helpers;

use verbb\formie\conditions\ConditionOperator;
use verbb\formie\conditions\ConditionRowEvaluator;
use verbb\formie\conditions\ConditionSetEvaluator;
use verbb\formie\conditions\ConditionValueResolver;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\models\SubmissionStatus;

use Craft;

use craft\models\Site;

class ConditionsHelper
{
    // Properties
    // =========================================================================

    private static ?ConditionSetEvaluator $_setEvaluator = null;
    // Static Methods
    // =========================================================================

    public static function evaluateConditions(array $conditions, Submission $submission, $callback = null): array
    {
        return self::_getSetEvaluator()->evaluateRows($conditions, $submission, $callback);
    }

    public static function getConditionalTestResult(array $conditionSettings, Submission $submission): bool
    {
        return self::_getSetEvaluator()->matches($conditionSettings, $submission);
    }

    public static function getConditionOptions(): array
    {
        return [
            ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
            ['label' => Craft::t('formie', 'is'), 'value' => ConditionOperator::EQ, 'pickable' => true],
            ['label' => Craft::t('formie', 'is not'), 'value' => ConditionOperator::NEQ, 'pickable' => true],
            ['label' => Craft::t('formie', 'greater than'), 'value' => ConditionOperator::GT],
            ['label' => Craft::t('formie', 'less than'), 'value' => ConditionOperator::LT],
            ['label' => Craft::t('formie', 'contains'), 'value' => ConditionOperator::CONTAINS],
            ['label' => Craft::t('formie', 'starts with'), 'value' => ConditionOperator::STARTS_WITH],
            ['label' => Craft::t('formie', 'ends with'), 'value' => ConditionOperator::ENDS_WITH],
            ['label' => Craft::t('formie', 'is empty'), 'value' => ConditionOperator::EMPTY],
            ['label' => Craft::t('formie', 'is not empty'), 'value' => ConditionOperator::NOT_EMPTY],
        ];
    }

    /**
     * Default select options for condition Value columns (sites, statuses).
     * Pass `form` to limit statuses to that form's allowed set; values are handles
     * because `{submission:status}` resolves via Submission::getStatus().
     */
    public static function getConditionFieldOptionConfig(array $config = []): array
    {
        /** @var Form|null $form */
        $form = $config['form'] ?? null;

        return [
            'includeSubmissionDate' => (bool)($config['includeSubmissionDate'] ?? false),
            'siteNameOptions' => $config['siteNameOptions'] ?? self::getSiteNameSelectOptions(),
            'siteHandleOptions' => $config['siteHandleOptions'] ?? self::getSiteHandleSelectOptions(),
            'statusOptions' => $config['statusOptions'] ?? self::getStatusSelectOptions($form),
        ];
    }

    public static function getSiteNameSelectOptions(): array
    {
        return self::_selectOptionsWithPlaceholder(array_map(function(Site $site) {
            return [
                'label' => $site->name,
                'value' => $site->name,
            ];
        }, Craft::$app->getSites()->getAllSites()));
    }

    public static function getSiteHandleSelectOptions(): array
    {
        return self::_selectOptionsWithPlaceholder(array_map(function(Site $site) {
            return [
                'label' => $site->name,
                'value' => $site->handle,
            ];
        }, Craft::$app->getSites()->getAllSites()));
    }

    public static function getStatusSelectOptions(?Form $form = null): array
    {
        $statuses = $form
            ? Formie::$plugin->getFormGroupPolicy()->getSubmissionStatusesForForm($form)
            : Formie::$plugin->getSubmissionStatuses()->getAllStatuses();

        return self::_selectOptionsWithPlaceholder(array_map(function(SubmissionStatus $status) {
            return [
                'label' => $status->name,
                'value' => $status->handle,
            ];
        }, $statuses));
    }

    public static function getConditionFieldOptions(array $config = []): array
    {
        $config = self::getConditionFieldOptionConfig($config);
        $includeSubmissionDate = (bool)$config['includeSubmissionDate'];
        $siteNameOptions = $config['siteNameOptions'];
        $siteHandleOptions = $config['siteHandleOptions'];
        $statusOptions = $config['statusOptions'];

        $submissionOptions = [
            ['label' => Craft::t('formie', 'Title'), 'value' => '{submission:title}'],
            ['label' => Craft::t('formie', 'ID'), 'value' => '{submission:id}'],
            ['label' => Craft::t('formie', 'Form Name'), 'value' => '{submission:formName}'],
        ];

        if ($includeSubmissionDate) {
            $submissionOptions[] = ['label' => Craft::t('formie', 'Submission Date'), 'value' => '{submission:dateCreated}'];
        }

        $submissionOptions[] = [
            'label' => Craft::t('formie', 'Site Name'),
            'value' => '{submission:siteName}',
            'column' => [
                'type' => 'select',
                'options' => $siteNameOptions,
            ],
        ];

        $submissionOptions[] = [
            'label' => Craft::t('formie', 'Site Handle'),
            'value' => '{submission:siteHandle}',
            'column' => [
                'type' => 'select',
                'options' => $siteHandleOptions,
            ],
        ];

        $submissionOptions[] = [
            'label' => Craft::t('formie', 'Status'),
            'value' => '{submission:status}',
            'column' => [
                'type' => 'select',
                'options' => $statusOptions,
            ],
        ];

        return [
            ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
            [
                'group' => Craft::t('formie', 'Submission'),
                'options' => $submissionOptions,
            ],
        ];
    }

    public static function normalizeClientConditions(array $conditions, Form $form): array
    {
        $fieldMap = self::getClientFieldReferenceMap($form);

        foreach (($conditions['conditions'] ?? []) as $index => $condition) {
            $fieldReference = $condition['field'] ?? null;

            if (!is_string($fieldReference) || $fieldReference === '') {
                continue;
            }

            $source = self::_buildClientConditionSource($fieldReference, $fieldMap);
            $conditions['conditions'][$index]['field'] = self::_normalizeClientFieldReference($fieldReference, $source);
            $conditions['conditions'][$index]['source'] = $source;
        }

        return $conditions;
    }

    /**
     * Snapshot of `{submission:*}` values for client condition evaluation (CP + front-end).
     * Keys match condition option identifiers (`status`, `title`, `formName`, …).
     */
    public static function getClientSubmissionContext(?Submission $submission): array
    {
        if (!$submission) {
            return [];
        }

        $form = $submission->getForm();
        $site = Craft::$app->getSites()->getSiteById((int)$submission->siteId);
        $dateCreated = $submission->dateCreated;

        return [
            'title' => (string)($submission->title ?? ''),
            'id' => (string)($submission->id ?? ''),
            'uid' => (string)($submission->uid ?? ''),
            'status' => (string)($submission->getStatus() ?? ''),
            'formName' => (string)($form?->title ?? ''),
            'siteName' => (string)($site?->name ?? ''),
            'siteHandle' => (string)($site?->handle ?? ''),
            'dateCreated' => $dateCreated?->format('c') ?? '',
            'date' => $dateCreated?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    public static function getClientFieldReferenceMap(Form $form): array
    {
        return FieldReferenceHelper::getClientFieldReferenceMap($form->getFields());
    }

    public static function toComponentConditionDefinition(array $conditions): ?array
    {
        if (!$conditions || !($conditions['conditions'] ?? null)) {
            return null;
        }

        $effect = ($conditions['showRule'] ?? 'show') === 'show' ? 'show' : 'hide';
        $rules = array_values(array_filter(array_map(static function(array $rule) {
            $field = $rule['field'] ?? null;
            $fieldId = $field['field'] ?? $field['fieldId'] ?? null;

            if (!$fieldId) {
                return null;
            }

            return [
                'fieldId' => (string)$fieldId,
                'operator' => (string)($rule['condition'] ?? '=='),
                'value' => $rule['value'] ?? null,
            ];
        }, $conditions['conditions'])));

        if (!$rules) {
            return null;
        }

        return [
            'mode' => ($conditions['match'] ?? 'all') === 'any' ? 'any' : 'all',
            'effect' => $effect,
            'clearOnHide' => ($conditions['clearOnHide'] ?? true) !== false,
            'rules' => $rules,
        ];
    }

    private static function _selectOptionsWithPlaceholder(array $options): array
    {
        return array_merge([
            ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
        ], $options);
    }

    private static function _getSetEvaluator(): ConditionSetEvaluator
    {
        if (!self::$_setEvaluator) {
            self::$_setEvaluator = new ConditionSetEvaluator(
                new ConditionRowEvaluator(
                    new ConditionValueResolver()
                )
            );
        }

        return self::$_setEvaluator;
    }

    private static function _normalizeClientFieldReference(string $fieldReference, ?array $source = null): string
    {
        $trimmedReference = trim($fieldReference);

        if ($trimmedReference === '') {
            return $fieldReference;
        }

        if (($source['target'] ?? '') === 'field' && !empty($source['handle'])) {
            return self::_buildFieldReferenceToken($source);
        }

        return $trimmedReference;
    }

    private static function _buildClientConditionSource(string $fieldReference, array $fieldMap): ?array
    {
        $trimmedReference = trim($fieldReference);

        if ($trimmedReference === '') {
            return null;
        }

        $expression = References::parseReferenceExpression($trimmedReference);

        if ($expression->isValid) {
            if ($expression->target !== 'field') {
                return [
                    'raw' => $trimmedReference,
                    'target' => $expression->target,
                    // Property key for client snapshots (`status`, `title`, …).
                    'handle' => $expression->identifier,
                    'selector' => $expression->selector,
                    'defaultValue' => $expression->default,
                    'transformerId' => $expression->transformerId,
                    'transformerParams' => $expression->transformerParams,
                    'isValid' => $expression->identifier !== '',
                ];
            }

            return [
                'raw' => $trimmedReference,
                'target' => 'field',
                'handle' => self::_resolveClientFieldHandle($expression->identifier, $fieldMap),
                'selector' => $expression->selector,
                'defaultValue' => $expression->default,
                'transformerId' => $expression->transformerId,
                'transformerParams' => $expression->transformerParams,
                'isValid' => $expression->identifier !== '',
            ];
        }

        [$handle, $path] = array_pad(explode('.', $trimmedReference, 2), 2, '');

        return [
            'raw' => $trimmedReference,
            'target' => 'field',
            'handle' => self::_resolveClientFieldHandle($handle, $fieldMap),
            'selector' => str_replace('.', ':', $path),
            'defaultValue' => '',
            'transformerId' => '',
            'transformerParams' => [],
            'isValid' => $handle !== '',
        ];
    }

    private static function _buildFieldReferenceToken(array $source): string
    {
        $token = References::field((string)($source['handle'] ?? ''), ($source['selector'] ?? '') ?: null);
        $transformerId = trim((string)($source['transformerId'] ?? ''));
        $transformerParams = is_array($source['transformerParams'] ?? null) ? $source['transformerParams'] : [];

        if ($transformerId !== '') {
            $trimmedToken = preg_replace('/\}$/', '', $token);
            $metadata = ';transform=' . urlencode($transformerId);

            foreach ($transformerParams as $key => $value) {
                $normalizedKey = trim((string)$key);

                if ($normalizedKey === '' || $normalizedKey === 'transform') {
                    continue;
                }

                $metadata .= ';' . $normalizedKey . '=' . urlencode((string)$value);
            }

            $token = $trimmedToken . $metadata . '}';
        }

        $defaultValue = trim((string)($source['defaultValue'] ?? ''));

        if ($defaultValue !== '') {
            $token = References::withDefault($token, $defaultValue);
        }

        return $token;
    }

    private static function _resolveClientFieldHandle(string $reference, array $fieldMap): string
    {
        return FieldReferenceHelper::resolveClientFieldKey($reference, $fieldMap);
    }
}
