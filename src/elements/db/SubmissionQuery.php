<?php
namespace verbb\formie\elements\db;

use craft\elements\User;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\Table;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\Status;

use Craft;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\search\SearchQueryTerm;
use craft\search\SearchQueryTermGroup;

class SubmissionQuery extends ElementQuery
{
    // Static Methods
    // =========================================================================

    public static function invalidateStaticCaches(): void
    {
        self::$_fieldHandleCacheByScope = [];
        self::$_customFieldsByHandleScope = [];
    }

    private static function _storeFieldHandleScopeCache(string $scopeKey, array $mappedHandles): void
    {
        if (!isset(self::$_fieldHandleCacheByScope[$scopeKey]) && count(self::$_fieldHandleCacheByScope) >= self::FIELD_HANDLE_SCOPE_CACHE_MAX) {
            array_shift(self::$_fieldHandleCacheByScope);
        }

        self::$_fieldHandleCacheByScope[$scopeKey] = $mappedHandles;
    }

    private static function _storeCustomFieldLoadCache(string $scopeKey, array $fields): void
    {
        if (!isset(self::$_customFieldsByHandleScope[$scopeKey]) && count(self::$_customFieldsByHandleScope) >= self::FIELD_HANDLE_SCOPE_CACHE_MAX) {
            array_shift(self::$_customFieldsByHandleScope);
        }

        self::$_customFieldsByHandleScope[$scopeKey] = $fields;
    }

    // Properties
    // =========================================================================

    public mixed $id = null;
    public mixed $siteId = '*';
    public mixed $formId = null;
    public mixed $statusId = null;
    public mixed $userId = null;
    public ?bool $isIncomplete = false;
    public ?bool $isSpam = false;
    public mixed $before = null;
    public mixed $after = null;
    public mixed $updateTitle = null;

    protected array $defaultOrderBy = ['elements.dateCreated' => SORT_DESC];
    private const FIELD_HANDLE_SCOPE_CACHE_MAX = 128;
    private static array $_fieldHandleCacheByScope = [];
    private static array $_customFieldsByHandleScope = [];
    private array $_fieldCriteriaByHandle = [];
    private ?array $_availableFieldHandles = null;
    private ?array $_resolvedFormIdsCache = null;
    private ?string $_resolvedFormIdsCacheKey = null;


    // Public Methods
    // =========================================================================

    public function __call($name, $params)
    {
        // Keep Craft-like DX for custom-field querying: `$query->myField('value')`.
        if (count($params) === 1 && $name !== 'owner') {
            $this->_availableFieldHandles ??= $this->_resolveAvailableFieldHandles();
            $lookupHandle = strtolower((string)$name);

            if (isset($this->_availableFieldHandles[$lookupHandle])) {
                $canonicalHandle = $this->_availableFieldHandles[$lookupHandle];
                $this->_fieldCriteriaByHandle[$canonicalHandle] = $params[0];

                return $this;
            }
        }

        return parent::__call($name, $params);
    }

    public function form(Form|array|string|null $value): static
    {
        if ($value instanceof Form) {
            $this->formId = $value->id;
        } else if ($value !== null) {
            $this->formId = $this->_resolveFormIdValue($value);
        } else {
            $this->formId = null;
        }

        $this->_availableFieldHandles = null;
        $this->_resolvedFormIdsCache = null;
        $this->_resolvedFormIdsCacheKey = null;

        return $this;
    }

    public function formId($value): static
    {
        $this->formId = $value;
        $this->_availableFieldHandles = null;
        $this->_resolvedFormIdsCache = null;
        $this->_resolvedFormIdsCacheKey = null;

        return $this;
    }

    public function field(string $handle, mixed $value): static
    {
        $this->_fieldCriteriaByHandle[$handle] = $value;

        return $this;
    }

    public function status(array|string|null $value): static
    {
        if ($value instanceof Status) {
            $this->statusId = $value->id;
        } else if ($value !== null) {
            $this->statusId = $this->_resolveStatusIdValue($value);
        } else {
            parent::status(null);

            $this->statusId = null;
            $this->isIncomplete = null;
            $this->isSpam = null;
        }

        return $this;
    }

    public function statusId($value): static
    {
        $this->statusId = $value;

        return $this;
    }

    public function user(string|User|null $value): static
    {
        if ($value instanceof User) {
            $this->userId = $value->id;
        } else if ($value !== null) {
            $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($value);
            $this->userId = $user ? $user->id : false;
        } else {
            $this->userId = null;
        }

        return $this;
    }

    public function userId($value): static
    {
        $this->userId = $value;

        return $this;
    }

    public function isIncomplete(?bool $value): static
    {
        $this->isIncomplete = $value;
        return $this;
    }

    public function isSpam(?bool $value): static
    {
        $this->isSpam = $value;
        return $this;
    }

    public function anyStatus(): static
    {
        return $this->status(null);
    }

    public function before(mixed $value): self
    {
        $this->before = $value;
        return $this;
    }

    public function after(mixed $value): self
    {
        $this->after = $value;
        return $this;
    }

    public function afterPopulate(array $elements): array
    {
        $elements = parent::afterPopulate($elements);

        // Allow setting an element query property on the resave controller
        if ($this->updateTitle) {
            foreach ($elements as $element) {
                $element->setUpdateTitle(true);
            }
        }

        return $elements;
    }

    private function _resolveStatusIdValue(array|string $value): mixed
    {
        return Formie::$plugin->getStatuses()->resolveStatusIdParam($value);
    }


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('formie_submissions');

        $submissionColumns = [
            'formie_submissions.id',
            'formie_submissions.formId',
            'formie_submissions.statusId',
            'formie_submissions.userId',
            'formie_submissions.isIncomplete',
            'formie_submissions.isSpam',
            'formie_submissions.spamReason',
            'formie_submissions.spamClass',
            'formie_submissions.snapshot',
            'formie_submissions.ipAddress',
        ];

        $db = Craft::$app->getDb();

        if ($db->columnExists(Table::FORMIE_SUBMISSIONS, 'integrationDispatchContext')) {
            $submissionColumns[] = 'formie_submissions.integrationDispatchContext';
        }

        if ($db->columnExists(Table::FORMIE_SUBMISSIONS, 'metadata')) {
            $submissionColumns[] = 'formie_submissions.metadata';
        }

        // Should always be at the end, due to `setFieldContent` triggering order, so that `formId` (and other props) are set first
        $submissionColumns[] = 'formie_submissions.content as fieldContent';

        $this->query->select($submissionColumns);

        if ($this->formId) {
            $this->subQuery->andWhere(Db::parseParam('formie_submissions.formId', $this->formId));
        }

        if ($this->statusId) {
            $this->subQuery->andWhere(Db::parseParam('formie_submissions.statusId', $this->statusId));
        }

        if ($this->userId !== null) {
            $this->subQuery->andWhere(Db::parseNumericParam('formie_submissions.userId', $this->userId));
        }

        if ($this->isIncomplete !== null) {
            $this->subQuery->andWhere(Db::parseParam('formie_submissions.isIncomplete', $this->isIncomplete));
        }

        if ($this->isSpam !== null) {
            $this->subQuery->andWhere(Db::parseParam('formie_submissions.isSpam', $this->isSpam));
        }

        if ($this->before) {
            $this->subQuery->andWhere(Db::parseDateParam('formie_submissions.dateCreated', $this->before, '<'));
        }

        if ($this->after) {
            $this->subQuery->andWhere(Db::parseDateParam('formie_submissions.dateCreated', $this->after, '>='));
        }

        return parent::beforePrepare();
    }

    protected function afterPrepare(): bool
    {
        // Apply Formie-owned field criteria collected via dynamic field-handle methods
        // (for example: Submission::find()->myCustomField('value')).
        if ($this->_fieldCriteriaByHandle) {
            $this->_applyCustomFieldParams();
        }

        return parent::afterPrepare();
    }

    protected function statusCondition(string $status): mixed
    {
        // ElementQuery asks this for Craft-native statuses like `live` on every query.
        // Resolve real Formie status handles from the cached status service first, then
        // let Craft handle native element statuses without an avoidable status-table miss.
        $statusId = $this->_resolveCachedStatusId($status);

        if ($statusId) {
            return ['formie_submissions.statusId' => $statusId];
        }

        return parent::statusCondition($status);
    }

    protected function customFields(): array
    {
        if (!$this->withCustomFields) {
            return [];
        }

        // Craft will try and load custom fields when dealing with provisional drafts, which is rough for performance
        // As submissions don't make use of provisional draft, we can discard this.
        if ($this->withProvisionalDrafts || $this->provisionalDrafts) {
            return [];
        }

        $formIds = $this->_resolveFormIds();
        $criteriaHandles = array_keys($this->_fieldCriteriaByHandle);

        // If we have explicit field criteria, only hydrate those fields.
        if ($criteriaHandles) {
            return $this->_loadCustomFieldsForHandles($criteriaHandles, $formIds);
        }

        // If restricting to a form, only load the fields we need for performance.
        if ($formIds) {
            $fieldsByForm = Formie::$plugin->getFields()->getAllFieldsForForms($formIds);
            $fieldsById = [];

            foreach ($fieldsByForm as $fields) {
                foreach ($fields as $field) {
                    $fieldsById[$field->id] = $field;
                }
            }

            return array_values($fieldsById);
        }

        // For all-source queries, avoid instantiating every Formie field definition unless
        // the query actually references field handles (criteria/search/orderBy).
        $requiredHandles = $this->_resolveRequiredFieldHandlesForAllSources();

        if ($requiredHandles) {
            return $this->_loadCustomFieldsForHandles($requiredHandles);
        }

        return [];
    }


    // Protected Methods
    // =========================================================================

    private function _applyCustomFieldParams(): void
    {
        if (!$this->_fieldCriteriaByHandle) {
            return;
        }

        $criteriaHandles = array_keys($this->_fieldCriteriaByHandle);
        $formIds = $this->_resolveFormIds();
        $criteriaFields = $this->_loadCustomFieldsForHandles($criteriaHandles, $formIds);

        if (!$criteriaFields) {
            return;
        }

        // Group only criteria-targeted fields by handle and field UUID.
        $criteriaHandleMap = array_flip($criteriaHandles);
        $fieldsByHandle = [];

        foreach ($criteriaFields as $field) {
            if (!isset($criteriaHandleMap[$field->handle])) {
                continue;
            }

            $fieldsByHandle[$field->handle][$field->uid][] = $field;
        }

        foreach ($this->_fieldCriteriaByHandle as $handle => $fieldValue) {
            $instancesByUid = $fieldsByHandle[$handle] ?? null;

            if (!$instancesByUid) {
                continue;
            }

            $conditions = [];
            $params = [];

            foreach ($instancesByUid as $instances) {
                $firstInstance = $instances[0];
                $condition = $firstInstance::queryCondition($instances, $fieldValue, $params);

                // aborting?
                if ($condition === false) {
                    throw new QueryAbortedException();
                }

                if ($condition !== null) {
                    $conditions[] = $condition;
                }
            }

            if (!empty($conditions)) {
                if (count($conditions) === 1) {
                    $this->subQuery->andWhere(reset($conditions), $params);
                } else {
                    $this->subQuery->andWhere(['or', ...$conditions], $params);
                }
            }
        }
    }

    private function _resolveFormIdValue(array|string $value): mixed
    {
        if (is_string($value) && $this->_isExactHandleParam($value)) {
            return Formie::$plugin->getForms()->getFormByHandle($value)?->id ?: false;
        }

        if (is_array($value) && $this->_isExactHandleListParam($value)) {
            $ids = [];

            foreach ($value as $handle) {
                $form = Formie::$plugin->getForms()->getFormByHandle($handle);

                if ($form) {
                    $ids[] = (int)$form->id;
                }
            }

            return $ids ?: false;
        }

        return (new Query())
            ->select(['forms.id'])
            ->from(['forms' => Table::FORMIE_FORMS])
            ->where(Db::parseParam('handle', $value))
            ->leftJoin(['elements' => Table::ELEMENTS], '[[forms.id]] = [[elements.id]]')
            ->andWhere(['dateDeleted' => null])
            ->scalar();
    }

    private function _resolveCachedStatusId(string $status): ?int
    {
        $normalizedStatus = strtolower(trim($status));

        if ($normalizedStatus === '' || str_starts_with($normalizedStatus, 'not ')) {
            return null;
        }

        return Formie::$plugin->getStatuses()->resolveStatusId($status);
    }

    private function _isExactHandleParam(string $value): bool
    {
        $value = trim($value);

        if ($value === '' || str_starts_with(strtolower($value), 'not ')) {
            return false;
        }

        return !str_contains($value, '*') && !str_contains($value, ',');
    }

    private function _isExactHandleListParam(array $value): bool
    {
        if (!$value || array_is_list($value) === false) {
            return false;
        }

        foreach ($value as $handle) {
            if (!is_string($handle) || !$this->_isExactHandleParam($handle)) {
                return false;
            }
        }

        return true;
    }

    private function _resolveAvailableFieldHandles(): array
    {
        $scopeKey = $this->_fieldHandleScopeCacheKey();

        if (isset(self::$_fieldHandleCacheByScope[$scopeKey])) {
            return self::$_fieldHandleCacheByScope[$scopeKey];
        }

        $query = (new Query())
            ->select(['f.handle'])
            ->from(['ff' => Table::FORMIE_FORM_FIELDS])
            ->innerJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]')
            ->distinct();

        if ($formIds = $this->_resolveFormIds()) {
            $query->innerJoin(['fo' => Table::FORMIE_FORMS], '[[ff.layoutId]] = [[fo.layoutId]]');
            $query->andWhere(['fo.id' => $formIds]);
        }

        $handles = $query->column();
        $mappedHandles = [];

        foreach ($handles as $handle) {
            if (is_string($handle) && $handle !== '') {
                $mappedHandles[strtolower($handle)] = $handle;
            }
        }

        self::_storeFieldHandleScopeCache($scopeKey, $mappedHandles);

        return $mappedHandles;
    }

    private function _fieldHandleScopeCacheKey(): string
    {
        $formIds = $this->_resolveFormIds();

        if (!$formIds) {
            return '*';
        }

        $formIds = array_values(array_unique(array_map('intval', $formIds)));
        sort($formIds, SORT_NUMERIC);

        return implode(',', $formIds);
    }

    private function _loadCustomFieldsForHandles(array $handles, array $formIds = []): array
    {
        $handles = array_values(array_unique(array_filter($handles, fn($handle) => is_string($handle) && $handle !== '')));

        if (!$handles) {
            return [];
        }

        sort($handles, SORT_STRING);
        $formIds = array_values(array_unique(array_map('intval', $formIds)));
        sort($formIds, SORT_NUMERIC);
        $cacheKey = Json::encode([
            'handles' => $handles,
            'formIds' => $formIds,
        ]);

        if (isset(self::$_customFieldsByHandleScope[$cacheKey])) {
            return self::$_customFieldsByHandleScope[$cacheKey];
        }

        $usageQuery = (new Query())
            ->select([
                'fieldId',
                'count' => 'COUNT(*)',
            ])
            ->from(Table::FORMIE_FORM_FIELDS)
            ->groupBy(['fieldId']);

        $query = (new Query())
            ->select([
                'ff.id',
                'ff.fieldId',
                'ff.layoutId',
                'ff.pageId',
                'ff.rowId',
                'f.label',
                'f.handle',
                'ff.reference',
                'f.type',
                'ff.sortOrder',
                'f.settings',
                'ff.dateCreated',
                'ff.dateUpdated',
                'ff.uid',
                'ff.settings as formFieldSettings',
                'COALESCE(usage.count, 1) as usageCount',
            ])
            ->from(['ff' => Table::FORMIE_FORM_FIELDS])
            ->innerJoin(['f' => Table::FORMIE_FIELDS], '[[f.id]] = [[ff.fieldId]]')
            ->leftJoin(['usage' => $usageQuery], '[[usage.fieldId]] = [[ff.fieldId]]')
            ->where(['f.handle' => $handles]);

        if ($formIds) {
            $query->innerJoin(['fo' => Table::FORMIE_FORMS], '[[ff.layoutId]] = [[fo.layoutId]]');
            $query->andWhere(['fo.id' => $formIds]);
        }

        $fieldRecords = $query->all();
        $fieldsById = [];

        foreach ($fieldRecords as $fieldRecord) {
            $formFieldSettings = Json::decodeIfJson($fieldRecord['formFieldSettings'] ?? null);

            if (is_array($formFieldSettings) && array_key_exists('required', $formFieldSettings)) {
                $fieldRecord['required'] = (bool)$formFieldSettings['required'];
            }

            $fieldRecord['isSynced'] = (int)($fieldRecord['usageCount'] ?? 1) > 1;
            $fieldRecord['syncId'] = $fieldRecord['isSynced'] ? (int)($fieldRecord['fieldId'] ?? 0) : null;
            unset($fieldRecord['formFieldSettings']);

            $field = Formie::$plugin->getFields()->createField($fieldRecord);

            if ($field->id) {
                $fieldsById[$field->id] = $field;
            } else {
                $fieldsById[] = $field;
            }
        }

        $fields = array_values($fieldsById);

        // Criteria queries can ask for the same handle-scoped field definitions twice in a
        // single execution: once to build SQL conditions, then again while populating custom
        // fields on the matched submissions. Cache that hydrated field set for the request so
        // repeated submission queries and the second pass in the same query can reuse it.
        self::_storeCustomFieldLoadCache($cacheKey, $fields);

        return $fields;
    }

    private function _resolveFormIds(): array
    {
        $cacheKey = Json::encode([
            'formId' => $this->formId,
            'id' => $this->id,
            'uid' => $this->uid,
        ]);

        if ($this->_resolvedFormIdsCacheKey === $cacheKey && $this->_resolvedFormIdsCache !== null) {
            return $this->_resolvedFormIdsCache;
        }

        // If `formId` is directly available
        if ($this->formId) {
            $result = (array)$this->formId;
            $this->_resolvedFormIdsCacheKey = $cacheKey;
            $this->_resolvedFormIdsCache = $result;

            return $result;
        }

        // If working with submission IDs
        if ($this->id) {
            $result = (new Query())
                ->select(['formId'])
                ->from(Table::FORMIE_SUBMISSIONS)
                ->where(['id' => (array)$this->id])
                ->column();

            $this->_resolvedFormIdsCacheKey = $cacheKey;
            $this->_resolvedFormIdsCache = $result;

            return $result;
        }

        // If working with submission UIDs
        if ($this->uid) {
            $result = (new Query())
                ->select(['formId'])
                ->from(Table::FORMIE_SUBMISSIONS)
                ->where(['uid' => (array)$this->uid])
                ->column();

            $this->_resolvedFormIdsCacheKey = $cacheKey;
            $this->_resolvedFormIdsCache = $result;

            return $result;
        }

        $this->_resolvedFormIdsCacheKey = $cacheKey;
        $this->_resolvedFormIdsCache = [];

        return [];
    }

    private function _resolveRequiredFieldHandlesForAllSources(): array
    {
        $requiredHandles = array_keys($this->_fieldCriteriaByHandle);

        // Handle `search('fieldHandle:value')` on all-source queries by loading only those handles.
        $requiredHandles = array_merge($requiredHandles, $this->_extractSearchAttributeHandles());

        // Allow orderBy on field handles without loading all fields.
        $orderBy = (array)$this->orderBy;
        $requiredHandles = array_merge($requiredHandles, array_keys($orderBy));

        $requiredHandles = array_values(array_unique(array_filter($requiredHandles, fn($handle) => is_string($handle) && $handle !== '')));

        if (!$requiredHandles) {
            return [];
        }

        $this->_availableFieldHandles ??= $this->_resolveAvailableFieldHandles();
        $availableHandlesByLower = $this->_availableFieldHandles;
        $canonicalHandles = [];

        foreach ($requiredHandles as $handle) {
            $lookupHandle = strtolower($handle);

            if (isset($availableHandlesByLower[$lookupHandle])) {
                $canonicalHandles[] = $availableHandlesByLower[$lookupHandle];
            }
        }

        return array_values(array_unique($canonicalHandles));
    }

    private function _extractSearchAttributeHandles(): array
    {
        if (!$this->search) {
            return [];
        }

        $searchQuery = Craft::$app->getSearch()->normalizeSearchQuery($this->search);
        $tokens = $searchQuery->getTokens();
        $attributes = [];

        $collectTermAttribute = static function($term) use (&$attributes): void {
            if ($term instanceof SearchQueryTerm && is_string($term->attribute) && $term->attribute !== '') {
                $attributes[] = $term->attribute;
            }
        };

        foreach ($tokens as $token) {
            if ($token instanceof SearchQueryTermGroup) {
                foreach ($token->terms as $term) {
                    $collectTermAttribute($term);
                }

                continue;
            }

            $collectTermAttribute($token);
        }

        return array_values(array_unique($attributes));
    }
}
