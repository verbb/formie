<?php
namespace verbb\formie\elements\db;

use craft\elements\User;
use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\helpers\Table;
use verbb\formie\models\FieldLayout;
use verbb\formie\models\Status;
use verbb\formie\services\Fields;

use Craft;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\elements\db\ElementQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;

use yii\base\UnknownMethodException;

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
    public mixed $before = null;
    public mixed $after = null;

    protected array $defaultOrderBy = ['elements.dateCreated' => SORT_DESC];

    private array $_customFieldParams = [];


    // Public Methods
    // =========================================================================

    public function __call($name, $params)
    {
        // Add support for `.fieldHandle()` calls, as we're rolling our own fields, we don't get it automatically
        try {
            return parent::__call($name, $params);
        } catch (UnknownMethodException $e) {
            if (in_array($name, Fields::getFieldHandles())) {
                $this->_customFieldParams[$name] = $params[0];
            } else {
                throw $e;
            }

            return $this;
        }
    }

    public function form(Form|array|string|null $value): static
    {
        if ($value instanceof Form) {
            $this->formId = $value->id;
        } else if ($value !== null) {
            $this->formId = (new Query())
                ->select(['forms.id'])
                ->from(['forms' => Table::FORMIE_FORMS])
                ->where(Db::parseParam('handle', $value))
                ->leftJoin(['elements' => Table::ELEMENTS], '[[forms.id]] = [[elements.id]]')
                ->andWhere(['dateDeleted' => null])
                ->scalar();
        } else {
            $this->formId = null;
        }

        return $this;
    }

    public function formId($value): static
    {
        $this->formId = $value;

        return $this;
    }

    public function status(array|string|null $value): static
    {
        if ($value instanceof Status) {
            $this->statusId = $value->id;
        } else if ($value !== null) {
            $this->statusId = (new Query())
                ->select(['id'])
                ->from([Table::FORMIE_STATUSES])
                ->where(Db::parseParam('handle', $value))
                ->scalar();
        } else {
            $this->statusId = null;
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
        parent::status(null);

        $this->isIncomplete = null;
        $this->isSpam = null;
        return $this;
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

    public function field(array $values): static
    {
        // Allows querying on custom fields with key/values, and supports dot-notation for complex fields like Group/Repeater
        foreach ($values as $fieldKey => $value) {
            $fieldKey = explode('.', $fieldKey);
            $handle = array_shift($fieldKey);
            $fieldKey = implode('.', $fieldKey);

            if ($fieldKey) {
                $this->$handle([$fieldKey => $value]);
            } else {
                $this->$handle($value);
            }
        }

        return $this;
    }


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('formie_submissions');

        $this->query->select([
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

            // Should always be at the end, due to `setFieldContent` triggering order, so that `formId` (and other props) are set first
            'formie_submissions.content as fieldContent',
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

        if ($this->before) {
            $this->subQuery->andWhere(Db::parseDateParam('formie_submissions.dateCreated', $this->before, '<'));
        }

        if ($this->after) {
            $this->subQuery->andWhere(Db::parseDateParam('formie_submissions.dateCreated', $this->after, '>='));
        }

        // Check if we're querying custom fields, we're rolling our own fields
        if ($this->_customFieldParams) {
            $query = Craft::$app->getDb()->getQueryBuilder()->jsonContains('formie_submissions.content', $this->_customFieldParams);

            $this->subQuery->andWhere($query);
        }

        return parent::beforePrepare();
    }

    protected function statusCondition(string $status): mixed
    {
        // Could potentially use a join in the main sub-query to not have another query,
        // but I figure this is only called when using `status(handle)`, and we shouldn't
        // let the 'regular' query suffer for this possible querying
        $statusId = (new Query())
            ->select(['id'])
            ->from([Table::FORMIE_STATUSES])
            ->where(Db::parseParam('handle', $status))
            ->scalar();

        if ($statusId) {
            return ['formie_submissions.statusId' => $statusId];
        }

        return parent::statusCondition($status);
    }
}
