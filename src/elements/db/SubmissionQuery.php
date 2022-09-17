<?php
namespace verbb\formie\elements\db;

use craft\elements\User;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\models\Status;

use Craft;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

use Throwable;

class SubmissionQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public mixed $id = null;
    public mixed $siteId = '*';
    public mixed $formId = null;
    public mixed $statusId = null;
    public mixed $userId = null;
    public ?bool $isIncomplete = false;
    public ?bool $isSpam = false;

    protected array $defaultOrderBy = ['elements.dateCreated' => SORT_DESC];


    // Public Methods
    // =========================================================================

    /**
     * Sets the [[formId]] property.
     *
     * @param string|Form|null $value The property value
     * @return static self reference
     */
    public function form(Form|array|string|null $value): static
    {
        if ($value instanceof Form) {
            $this->formId = $value->id;
        } else if ($value !== null) {
            $this->formId = (new Query())
                ->select(['forms.id'])
                ->from(['{{%formie_forms}} forms'])
                ->where(Db::parseParam('handle', $value))
                ->leftJoin(['{{%elements}} elements'], '[[forms.id]] = [[elements.id]]')
                ->andWhere(['dateDeleted' => null])
                ->scalar();
        } else {
            $this->formId = null;
        }

        return $this;
    }

    /**
     * Sets the [[formId]] property.
     *
     * @param int
     * @return static self reference
     */
    public function formId($value): static
    {
        $this->formId = $value;

        return $this;
    }

    /**
     * Sets the [[statusId]] property.
     *
     * @param array|string|null $value
     * @return static self reference
     */
    public function status(array|string|null $value): static
    {
        if ($value instanceof Status) {
            $this->statusId = $value->id;
        } else if ($value !== null) {
            $this->statusId = (new Query())
                ->select(['id'])
                ->from(['{{%formie_statuses}}'])
                ->where(Db::parseParam('handle', $value))
                ->scalar();
        } else {
            $this->statusId = null;
        }

        return $this;
    }

    /**
     * Sets the [[statusId]] property.
     *
     * @param int
     * @return static self reference
     */
    public function statusId($value): static
    {
        $this->statusId = $value;

        return $this;
    }

    /**
     * Sets the [[userId]] property.
     *
     * @param string|User|null $value
     * @return static self reference
     */
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

    /**
     * Sets the [[userId]] property.
     *
     * @param int
     * @return static self reference
     */
    public function userId($value): static
    {
        $this->userId = $value;

        return $this;
    }

    /**
     * Sets the [[isIncomplete]] property.
     *
     * @param bool|null $value
     * @return static self reference
     */
    public function isIncomplete(?bool $value): static
    {
        $this->isIncomplete = $value;
        return $this;
    }

    /**
     * Sets the [[isSpam]] property.
     *
     * @param bool|null $value
     * @return static self reference
     */
    public function isSpam(?bool $value): static
    {
        $this->isSpam = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function anyStatus(): static
    {
        parent::status(null);

        $this->isIncomplete = null;
        $this->isSpam = null;
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

        if (!$this->formId) {
            $formIds = [];
            $subQuery = null;

            // Get the formIds for all submissions in thie query
            if ($this->id) {
                $subQuery = (new Query())
                    ->select(['formId'])
                    ->distinct()
                    ->from(['{{%formie_submissions}} submissions'])
                    ->where(Db::parseParam('id', $this->id));
            } else if ($this->uid) {
                // Note we're using the element table's UID
                $subQuery = (new Query())
                    ->select(['formId'])
                    ->distinct()
                    ->from(['{{%formie_submissions}} submissions'])
                    ->leftJoin(['{{%elements}} elements'], '[[submissions.id]] = [[elements.id]]')
                    ->where(Db::parseParam('elements.uid', $this->uid));
            }

            // We also need to do another query to ensure the forms haven't been deleted
            if ($subQuery) {
                $formIds = (new Query())
                    ->select(['formId'])
                    ->from(['{{%elements}} elements'])
                    ->where(['dateDeleted' => null])
                    ->andWhere(['not', ['formId' => null]])
                    ->leftJoin(['forms' => $subQuery], '[[forms.formId]] = [[elements.id]]')
                    ->andWhere(['dateDeleted' => null])
                    ->column();
            }

            if ($formIds) {
                $this->formId = count($formIds) === 1 ? $formIds[0] : $formIds;
            }
        }

        if ($this->formId && is_numeric($this->formId) && $form = Formie::$plugin->getForms()->getFormById($this->formId)) {
            $this->contentTable = $form->fieldContentTable;
        }

        $this->query->select([
            'formie_submissions.id',
            'formie_submissions.title',
            'formie_submissions.formId',
            'formie_submissions.statusId',
            'formie_submissions.userId',
            'formie_submissions.isIncomplete',
            'formie_submissions.isSpam',
            'formie_submissions.spamReason',
            'formie_submissions.spamClass',
            'formie_submissions.snapshot',
            'formie_submissions.ipAddress',
        ]);

        if ($this->formId) {
            $this->subQuery->andWhere(Db::parseParam('formie_submissions.formId', $this->formId));
        }

        if ($this->statusId) {
            $this->subQuery->andWhere(Db::parseParam('formie_submissions.statusId', $this->statusId));
        }

        if ($this->userId !== null) {
            if (is_numeric($this->userId)) {
                $this->subQuery->andWhere(Db::parseParam('formie_submissions.userId', $this->userId));
            } else {
                return false;
            }
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
        try {
            $form = Form::find()->id($this->formId)->one();

            if ($form) {
                return $form->getCustomFields();
            }
        } catch (Throwable) {
            // This will throw an error when restoring a form - but that's okay
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    protected function statusCondition(string $status): mixed
    {
        // Could potentially use a join in the main sub-query to not have another query,
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
