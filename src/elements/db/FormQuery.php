<?php

namespace verbb\formie\elements\db;

use verbb\formie\models\FormTemplate;
use verbb\formie\elements\Form;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class FormQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public mixed $handle = null;
    public mixed $templateId = null;
    public mixed $formStatus = null;

    protected array $defaultOrderBy = ['elements.dateCreated' => SORT_DESC];


    // Public Methods
    // =========================================================================

    public function handle($value): static
    {
        $this->handle = $value;
        return $this;
    }

    public function template($value): static
    {
        if ($value instanceof FormTemplate) {
            $this->templateId = $value->id;
        } else if ($value !== null) {
            $this->templateId = (new Query())
                ->select(['id'])
                ->from(['{{%formie_formtemplates}}'])
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

    public function status(array|string|null $value): static
    {
        $this->formStatus = $value;

        return $this;
    }


    protected function statusCondition(string $status): mixed
    {
        if (in_array($status, FORM::STATUSES, true)) {
            return ['formie_forms.formStatus' => $status];
        }

        return [];
    }


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('formie_forms');

        $this->query->select([
            'formie_forms.id',
            'formie_forms.formStatus',
            'formie_forms.handle',
            'formie_forms.fieldContentTable',
            'formie_forms.settings',
            'formie_forms.templateId',
            'formie_forms.submitActionEntryId',
            'formie_forms.submitActionEntrySiteId',
            'formie_forms.defaultStatusId',
            'formie_forms.dataRetention',
            'formie_forms.dataRetentionValue',
            'formie_forms.userDeletedAction',
            'formie_forms.fileUploadsAction',
            'formie_forms.fieldLayoutId',
            'formie_forms.uid',
        ]);

        if ($this->handle) {
            $this->subQuery->andWhere(Db::parseParam('formie_forms.handle', $this->handle));
        }

        if ($this->templateId) {
            $this->subQuery->andWhere(Db::parseParam('formie_forms.templateId', $this->templateId));
        }

        if ($this->formStatus) {
            $this->subQuery->andWhere(Db::parseParam('formie_forms.formStatus', $this->formStatus));
        }

        return parent::beforePrepare();
    }
}
