<?php
namespace verbb\formie\elements\db;

use verbb\formie\helpers\Table;
use verbb\formie\models\FormTemplate;

use Craft;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class FormQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public mixed $handle = null;
    public mixed $layoutId = null;
    public mixed $templateId = null;
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

        $this->query->select([
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
        ]);

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

        if ($this->pageCount) {
            $this->query->andWhere(Db::parseParam('pageCount', $this->pageCount));
        }

        return parent::beforePrepare();
    }
}
