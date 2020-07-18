<?php
namespace verbb\formie\elements\db;

use Craft;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use verbb\formie\elements\Form;
use verbb\formie\models\FormTemplate;

class FormQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public $handle;
    public $templateId;

    protected $defaultOrderBy = ['elements.dateCreated' => SORT_DESC];


    // Public Methods
    // =========================================================================

    public function handle($value): FormQuery
    {
        $this->handle = $value;
        return $this;
    }

    public function template($value): FormQuery
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

    public function templateId($value): FormQuery
    {
        $this->templateId = $value;
        return $this;
    }


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('formie_forms');

        $this->query->select([
            'formie_forms.id',
            'formie_forms.handle',
            'formie_forms.fieldContentTable',
            'formie_forms.settings',
            'formie_forms.templateId',
            'formie_forms.submitActionEntryId',
            'formie_forms.requireUser',
            'formie_forms.availability',
            'formie_forms.availabilityFrom',
            'formie_forms.availabilityTo',
            'formie_forms.availabilitySubmissions',
            'formie_forms.defaultStatusId',
            'formie_forms.dataRetention',
            'formie_forms.dataRetentionValue',
            'formie_forms.userDeletedAction',
            'formie_forms.fieldLayoutId',
            'formie_forms.uid',
        ]);

        if ($this->handle) {
            $this->subQuery->andWhere(Db::parseParam('formie_forms.handle', $this->handle));
        }

        if ($this->templateId) {
            $this->subQuery->andWhere(Db::parseParam('formie_forms.templateId', $this->templateId));
        }

        return parent::beforePrepare();
    }
}
