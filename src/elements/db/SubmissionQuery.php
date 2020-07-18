<?php
namespace verbb\formie\elements\db;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\models\Status;

use Craft;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class SubmissionQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public $id;
    public $siteId;
    public $formId;
    public $statusId;
    public $isIncomplete = false;
    public $isSpam = false;

    protected $defaultOrderBy = ['elements.dateCreated' => SORT_DESC];


    // Public Methods
    // =========================================================================

    /**
     * Sets the [[formId]] property.
     *
     * @param Form|string|null $value The property value
     *
     * @return static self reference
     */
    public function form($value)
    {
        if ($value instanceof Form) {
            $this->formId = $value->id;
        } else if ($value !== null) {
            $this->formId = (new Query())
                ->select(['id'])
                ->from(['{{%formie_forms}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->formId = null;
        }

        return $this;
    }

    /**
     * Sets the [[formId]] property.
     *
     * @param int
     *
     * @return static self reference
     */
    public function formId($value)
    {
        $this->formId = $value;

        return $this;
    }

    /**
     * @param Status|string|null $value
     * @return $this
     */
    public function status($value)
    {
        if ($value instanceof Status) {
            $this->statusId = $value->id;
        } else if ($value !== null) {
            $this->statusId = (new Query())
                ->select(['id'])
                ->from(['{{%formie_statuses}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->statusId = null;
        }

        return $this;
    }

    public function statusId($value)
    {
        $this->statusId = $value;

        return $this;
    }

    /**
     * @param bool|null $value
     * @return $this
     */
    public function isIncomplete($value)
    {
        $this->isIncomplete = $value;
        return $this;
    }

    /**
     * @param bool|null $value
     * @return $this
     */
    public function isSpam($value)
    {
        $this->isSpam = $value;
        return $this;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function beforePrepare(): bool
    {
        if ($this->formId !== null && empty($this->formId)) {
            throw new QueryAbortedException();
        }

        $this->joinElementTable('formie_submissions');

        // Figure out which content table to use
        $this->contentTable = null;

        if (!$this->formId && $this->id) {
            $formIds = (new Query())
                ->select(['formId'])
                ->distinct()
                ->from(['{{%formie_submissions}}'])
                ->where(Db::parseParam('id', $this->id))
                ->column();

            $this->formId = count($formIds) === 1 ? $formIds[0] : $formIds;
        }

        if ($this->formId && is_numeric($this->formId) && $form = Formie::$plugin->getForms()->getFormById($this->formId)) {
            $this->contentTable = $form->fieldContentTable;
        }

        $this->query->select([
            'formie_submissions.id',
            'formie_submissions.title',
            'formie_submissions.formId',
            'formie_submissions.statusId',
            'formie_submissions.isIncomplete',
            'formie_submissions.isSpam',
        ]);

        if ($this->formId) {
            $this->subQuery->andWhere(Db::parseParam('formie_submissions.formId', $this->formId));
        }

        if ($this->statusId) {
            $this->subQuery->andWhere(Db::parseParam('formie_submissions.statusId', $this->statusId));
        }

        if ($this->isIncomplete !== null) {
            $this->subQuery->andWhere(Db::parseParam('formie_submissions.isIncomplete', $this->isIncomplete));
        }

        if ($this->isSpam !== null) {
            $this->subQuery->andWhere(Db::parseParam('formie_submissions.isSpam', $this->isSpam));
        }

        return parent::beforePrepare();
    }

    /**
     * @inheritDoc
     */
    protected function customFields(): array
    {
        // This method won't get called if $this->formId isn't set to a single int
        /** @var Form $form */
        $form = Form::find()->id($this->formId)->one();

        return $form->getFields();
    }

    /**
     * @inheritDoc
     */
    protected function statusCondition(string $status)
    {
        // Could potentially use a join in the main subquery to not have another query
        // but I figure this is only called when using `status(handle)`, and we shouldn't
        // let the 'regular' query suffer for this possible querying
        $statusId = (new Query())
            ->select(['id'])
            ->from(['{{%formie_statuses}}'])
            ->where(Db::parseParam('handle', $status))
            ->scalar();

        if ($statusId) {
            return ['formie_submissions.statusId' => $statusId];
        }

        return parent::statusCondition($status);
    }
}
