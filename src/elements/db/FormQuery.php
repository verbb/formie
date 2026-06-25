<?php
namespace verbb\formie\elements\db;

use verbb\formie\Formie;
use verbb\formie\helpers\Table;
use verbb\formie\models\FormGroup;
use verbb\formie\models\FormTemplate;

use Craft;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Cp;
use craft\helpers\Db;

class FormQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public mixed $handle = null;
    public mixed $layoutId = null;
    public mixed $templateId = null;
    public mixed $groupId = null;
    public mixed $formStatusId = null;
    public mixed $pageCount = null;

    protected array $defaultOrderBy = ['elements.dateCreated' => SORT_DESC];


    // Public Methods
    // =========================================================================

    public function handle($value): static
    {
        $this->handle = $value;
        return $this;
    }

    public function layoutId($value): static
    {
        $this->layoutId = $value;
        return $this;
    }

    public function template($value): static
    {
        if ($value instanceof FormTemplate) {
            $this->templateId = $value->id;
        } else if ($value !== null) {
            $this->templateId = (new Query())
                ->select(['id'])
                ->from([Table::FORMIE_FORM_TEMPLATES])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->templateId = null;
        }

        return $this;
    }

    public function templateId($value): static
    {
        $this->templateId = $value;
        return $this;
    }

    public function group($value): static
    {
        if ($value instanceof FormGroup) {
            $this->groupId = $value->id;
        } elseif ($value !== null) {
            $groups = Formie::$plugin->getFormGroups();
            $group = $groups->getGroupByHandle((string)$value)
                ?? $groups->getGroupByUid((string)$value);
            $this->groupId = $group?->id;
        } else {
            $this->groupId = null;
        }

        return $this;
    }

    public function groupId($value): static
    {
        $this->groupId = $value;
        return $this;
    }

    public function formStatusId($value): static
    {
        if ($value !== null && $value !== ':empty:') {
            $formStatuses = Formie::$plugin->getFormStatuses();
            $resolved = $formStatuses->resolveStatusIdParam($value);
            $this->formStatusId = $resolved ?? $value;
        } else {
            $this->formStatusId = $value;
        }

        return $this;
    }

    public function pageCount($value): static
    {
        $this->pageCount = $value;
        return $this;
    }


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        // Prevent this from running in Craft's `m250315_131608_unlimited_authors` migration before our upgrade
        if (!Craft::$app->getDb()->tableExists(Table::FORMIE_FIELD_LAYOUT_PAGES)) {
            return false;
        }
        
        $this->joinElementTable('formie_forms');

        $formColumns = [
            'formie_forms.id',
            'formie_forms.handle',
            'formie_forms.settings',
            'formie_forms.layoutId',
            'formie_forms.templateId',
            'formie_forms.submitActionEntryId',
            'formie_forms.submitActionEntrySiteId',
            'formie_forms.defaultStatusId',
            'formie_forms.dataRetention',
            'formie_forms.dataRetentionValue',
            'formie_forms.userDeletedAction',
            'formie_forms.fileUploadsAction',
        ];

        $db = Craft::$app->getDb();

        if ($db->columnExists(Table::FORMIE_FORMS, 'createdById')) {
            $formColumns[] = 'formie_forms.createdById';
        }

        if ($db->columnExists(Table::FORMIE_FORMS, 'updatedById')) {
            $formColumns[] = 'formie_forms.updatedById';
        }

        if ($db->columnExists(Table::FORMIE_FORMS, 'groupId')) {
            $formColumns[] = 'formie_forms.groupId';
        }

        if ($db->columnExists(Table::FORMIE_FORMS, 'formStatusId')) {
            $formColumns[] = 'formie_forms.formStatusId';
        }

        if ($db->columnExists(Table::FORMIE_FORMS, 'sourceSiteId')) {
            $formColumns[] = 'formie_forms.sourceSiteId';
        }

        $this->query->select($formColumns);

        $pageQuery = (new Query())
            ->select(['COUNT(*)'])
            ->from(['pages' => Table::FORMIE_FIELD_LAYOUT_PAGES])
            ->where('[[pages.layoutId]] = [[formie_forms.layoutId]]');

        $this->subQuery->addSelect(['pageCount' => $pageQuery]);

        if ($this->handle) {
            $this->subQuery->andWhere(Db::parseParam('formie_forms.handle', $this->handle));
        }

        if ($this->layoutId) {
            $this->subQuery->andWhere(Db::parseParam('formie_forms.layoutId', $this->layoutId));
        }

        if ($this->templateId) {
            $this->subQuery->andWhere(Db::parseParam('formie_forms.templateId', $this->templateId));
        }

        if ($this->groupId !== null && $db->columnExists(Table::FORMIE_FORMS, 'groupId')) {
            if ($this->groupId === ':empty:') {
                $this->subQuery->andWhere(['formie_forms.groupId' => null]);
            } else {
                $this->subQuery->andWhere(Db::parseParam('formie_forms.groupId', $this->groupId));
            }
        }

        if ($this->formStatusId !== null && $db->columnExists(Table::FORMIE_FORMS, 'formStatusId')) {
            if ($this->formStatusId === ':empty:') {
                $this->subQuery->andWhere(['formie_forms.formStatusId' => null]);
            } else {
                $this->subQuery->andWhere(Db::parseParam('formie_forms.formStatusId', $this->formStatusId));
            }
        }

        if ($this->pageCount) {
            $this->query->andWhere(Db::parseParam('pageCount', $this->pageCount));
        }

        // Scope CP form indexes to the forms the current user can view or manage.
        if (Craft::$app->getRequest()->getIsCpRequest() && Craft::$app->edition !== Craft::Solo) {
            $accessibleFormIds = Formie::$plugin->getPermissions()->getAccessibleFormIds(Craft::$app->getUser()->getIdentity());

            if ($accessibleFormIds !== null) {
                $this->subQuery->andWhere(['formie_forms.id' => $accessibleFormIds ?: false]);
            }
        }

        if (!parent::beforePrepare()) {
            return false;
        }

        if (Formie::$plugin->getFormSitePropagation()->isEnabled() && Craft::$app->getRequest()->getIsCpRequest()) {
            $siteId = $this->_resolveIndexSiteId();

            if ($siteId !== null) {
                $this->siteId = $siteId;
            }
        }

        return true;
    }

    protected function statusCondition(string $status): mixed
    {
        $formStatuses = Formie::$plugin->getFormStatuses();

        if (!$formStatuses->hasConfiguredStatuses()) {
            return parent::statusCondition($status);
        }

        $statusId = $formStatuses->resolveStatusId($status);

        if ($statusId) {
            $defaultStatusId = $formStatuses->getDefaultStatus()?->id;

            if ($defaultStatusId && (int)$defaultStatusId === (int)$statusId) {
                return [
                    'or',
                    ['formie_forms.formStatusId' => null],
                    ['formie_forms.formStatusId' => $statusId],
                ];
            }

            return ['formie_forms.formStatusId' => $statusId];
        }

        return parent::statusCondition($status);
    }

    private function _resolveIndexSiteId(): ?int
    {
        $requestedSite = Cp::requestedSite();

        if ($requestedSite) {
            return (int)$requestedSite->id;
        }

        $siteId = $this->siteId;

        if (is_array($siteId)) {
            $siteId = reset($siteId) ?: null;
        }

        if ($siteId && $siteId !== '*' && is_numeric($siteId)) {
            return (int)$siteId;
        }

        return null;
    }
}
