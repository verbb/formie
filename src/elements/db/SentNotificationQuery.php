<?php
namespace verbb\formie\elements\db;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\SentNotification;
use verbb\formie\models\Status;

use Craft;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\elements\db\ElementQuery;
use craft\elements\User;
use craft\helpers\Db;

class SentNotificationQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public $id;
    public $formId;

    protected $defaultOrderBy = ['elements.dateCreated' => SORT_DESC];


    // Public Methods
    // =========================================================================

    /**
     * Sets the [[formId]] property.
     *
     * @param Form|string|null $value The property value
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
    public function formId($value)
    {
        $this->formId = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the notification’s statuses.
     */
    public function status($value)
    {
        return parent::status($value);
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('formie_sentnotifications');

        $this->query->select([
            'formie_sentnotifications.id',
            'formie_sentnotifications.title',
            'formie_sentnotifications.formId',
            'formie_sentnotifications.submissionId',
            'formie_sentnotifications.notificationId',
            'formie_sentnotifications.subject',
            'formie_sentnotifications.to',
            'formie_sentnotifications.cc',
            'formie_sentnotifications.bcc',
            'formie_sentnotifications.replyTo',
            'formie_sentnotifications.replyToName',
            'formie_sentnotifications.from',
            'formie_sentnotifications.fromName',
            'formie_sentnotifications.body',
            'formie_sentnotifications.htmlBody',
            'formie_sentnotifications.info',
            'formie_sentnotifications.success',
            'formie_sentnotifications.message',
            'formie_sentnotifications.dateCreated',
            'formie_sentnotifications.dateUpdated',
        ]);

        if ($this->formId) {
            $this->subQuery->andWhere(Db::parseParam('formie_sentnotifications.formId', $this->formId));
        }

        return parent::beforePrepare();
    }

    /**
     * @inheritdoc
     */
    protected function statusCondition(string $status)
    {
        switch ($status) {
            case SentNotification::STATUS_SUCCESS:
                return [
                    'formie_sentnotifications.success' => true,
                ];
            case SentNotification::STATUS_FAILED:
                return [
                    'formie_sentnotifications.success' => false,
                ];
            default:
                return parent::statusCondition($status);
        }
    }
}
