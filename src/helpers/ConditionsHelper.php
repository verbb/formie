<?php
namespace verbb\formie\helpers;

use verbb\formie\conditions\ConditionOperator;
use verbb\formie\conditions\ConditionSetEvaluator;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\models\SubmissionStatus;

use Craft;

use craft\models\Site;

class ConditionsHelper
{
    // Static Methods
    // =========================================================================

    public static function evaluate(array|\verbb\formie\conditions\ConditionSet $settings, Submission $submission, string $purpose = 'visibility', array $rows = []): \verbb\formie\conditions\ConditionEvaluation
    {
        $set = $settings instanceof \verbb\formie\conditions\ConditionSet ? $settings : \verbb\formie\conditions\ConditionSet::fromArray($settings, $purpose);
        return $submission->getContentState()->conditions->evaluate($set, $submission, $rows);
    }

    public static function matchingRules(array $rules, Submission $submission): array
    {
        return array_map(static fn($rule): array => $rule->metadata, (new ConditionSetEvaluator())->matchingRules(\verbb\formie\conditions\ConditionSet::fromArray(['conditions' => $rules], 'recipients'), $submission));
    }

    // Stable Formie 3 callback compatibility lives at the boundary, never in predicate evaluation.
    public static function evaluateConditions(array $conditions, Submission $submission, $callback = null): array
    {
        $results = [];
        foreach ($conditions as $row) {
            $result = self::evaluate(['conditions' => [$row]], $submission)->matches();
            if ($callback) {
                if ($value = $callback($result, $row)) {
                    $results[] = $value;
                }
            } else {
                $results[] = $result;
            }
        }
        return $results;
    }

    public static function getConditionalTestResult(array $conditionSettings, Submission $submission): bool
    {
        return self::evaluate($conditionSettings, $submission)->matches();
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
            ['label' => Craft::t('formie', 'does not contain'), 'value' => ConditionOperator::NOT_CONTAINS],
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
        return (new \verbb\formie\conditions\ConditionCompiler())->compile(\verbb\formie\conditions\ConditionSet::fromArray($conditions), $form);
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
        if (!$conditions) {
            return null;
        }
        return isset($conditions['version'], $conditions['rules']) ? $conditions : (new \verbb\formie\conditions\ConditionCompiler())->compile(\verbb\formie\conditions\ConditionSet::fromArray($conditions));
    }

    private static function _selectOptionsWithPlaceholder(array $options): array
    {
        return array_merge([
            ['label' => Craft::t('formie', 'Select an option'), 'value' => ''],
        ], $options);
    }

}
