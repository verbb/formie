<?php
namespace verbb\formie\elements\db;

use verbb\formie\elements\Form;
use verbb\formie\elements\SentNotification;

use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class SentNotificationQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public mixed $id = null;
    public mixed $formId = null;

    protected array $defaultOrderBy = ['elements.dateCreated' => SORT_DESC];


    // Public Methods
    // =========================================================================

    /**
     * Sets the [[formId]] property.
     *
     * @param string|Form|null $value The property value
     * @return static self reference
     */
    public function form(Form|string|null $value): static
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
    public function formId($value): static
    {
        $this->formId = $value;

        return $this;
    }

    /**
     * Narrows the query results based on the notification’s statuses.
     */
    public function status(array|string|null $value): static
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
            'formie_sentnotifications.sender',
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
    protected function statusCondition(string $status): mixed
    {
        return match ($status) {
            SentNotification::STATUS_SUCCESS => [
                'formie_sentnotifications.success' => true,
            ],
            SentNotification::STATUS_FAILED => [
                'formie_sentnotifications.success' => false,
            ],
            default => parent::statusCondition($status),
        };
    }
}
