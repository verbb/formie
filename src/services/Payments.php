<?php
namespace verbb\formie\services;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\PaymentEvent;
use verbb\formie\models\Payment;
use verbb\formie\records\Payment as PaymentRecord;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;

use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

use Throwable;

class Payments extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_PAYMENT = 'beforeSavePayment';
    public const EVENT_AFTER_SAVE_PAYMENT = 'afterSavePayment';
    public const EVENT_BEFORE_DELETE_PAYMENT = 'beforeDeletePayment';
    public const EVENT_AFTER_DELETE_PAYMENT = 'afterDeletePayment';


    // Private Properties
    // =========================================================================

    private ?MemoizableArray $_payments = null;


    // Public Methods
    // =========================================================================

    /**
     * Returns all payments.
     *
     * @return Payment[]
     */
    public function getAllPayments(): array
    {
        return $this->_payments()->all();
    }

    /**
     * Returns a payment identified by its ID.
     *
     * @param int $id
     * @return Payment|null
     */
    public function getPaymentById(int $id): ?Payment
    {
        return $this->_payments()->firstWhere('id', $id);
    }

    /**
     * Returns a payment identified by its reference.
     *
     * @param string $reference
     * @return Payment|null
     */
    public function getPaymentByReference(string $reference): ?Payment
    {
        return $this->_payments()->firstWhere('reference', $reference);
    }

    /**
     * Returns a payment identified by its submission ID.
     *
     * @param Submission $submission
     * @return Payment|null
     */
    public function getSubmissionPayments(Submission $submission): array
    {
        return $this->_payments()->where('submissionId', $submission->id)->all();
    }

    /**
     * Returns a payment identified by its UID.
     *
     * @param string $uid
     * @return Payment|null
     */
    public function getPaymentByUid(string $uid): ?Payment
    {
        return $this->_payments()->firstWhere('uid', $uid, true);
    }

    /**
     * Saves the payment.
     *
     * @param Payment $payment
     * @param bool $runValidation
     * @return bool
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function savePayment(Payment $payment, bool $runValidation = true): bool
    {
        $isNewPayment = !(bool)$payment->id;

        // Fire a 'beforeSavePayment' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_PAYMENT)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_PAYMENT, new PaymentEvent([
                'payment' => $payment,
                'isNew' => $isNewPayment,
            ]));
        }

        if ($runValidation && !$payment->validate()) {
            Formie::log('Payment not saved due to validation error.');

            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $paymentRecord = $this->_getPaymentRecord($payment->id);
            $paymentRecord->integrationId = $payment->integrationId;
            $paymentRecord->submissionId = $payment->submissionId;
            $paymentRecord->fieldId = $payment->fieldId;
            $paymentRecord->subscriptionId = $payment->subscriptionId;
            $paymentRecord->amount = $payment->amount;
            $paymentRecord->currency = $payment->currency;
            $paymentRecord->status = $payment->status;
            $paymentRecord->reference = $payment->reference;
            $paymentRecord->code = $payment->code;
            $paymentRecord->message = $payment->message;
            $paymentRecord->note = $payment->note;
            $paymentRecord->response = $payment->response;

            $paymentRecord->save(false);

            $payment->id = $paymentRecord->id;

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_payments = null;

        // Fire an 'afterSavePayment' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_PAYMENT)) {
            $this->trigger(self::EVENT_AFTER_SAVE_PAYMENT, new PaymentEvent([
                'payment' => $this->getPaymentById($paymentRecord->id),
                'isNew' => $isNewPayment,
            ]));
        }

        return true;
    }

    /**
     * Delete a payment by its id.
     *
     * @param int $id
     * @return bool
     * @throws Throwable
     */
    public function deletePaymentById(int $id): bool
    {
        $payment = $this->getPaymentById($id);

        if (!$payment) {
            return false;
        }

        return $this->deletePayment($payment);
    }

    /**
     * Deletes a payment.
     *
     * @param Payment $payment The payment
     * @return bool Whether the payment was deleted successfully
     */
    public function deletePayment(Payment $payment): bool
    {
        // Fire a 'beforeDeletePayment' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_PAYMENT)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_PAYMENT, new PaymentEvent([
                'payment' => $payment,
            ]));
        }

        Db::delete('{{%formie_payments}}', [
            'uid' => $payment->uid,
        ]);

        // Clear caches
        $this->_payments = null;

        // Fire an 'afterDeletePayment' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_PAYMENT)) {
            $this->trigger(self::EVENT_AFTER_DELETE_PAYMENT, new PaymentEvent([
                'payment' => $payment,
            ]));
        }

        return true;
    }


    // Private Methods
    // =========================================================================
    
    /**
     * Returns a memoizable array of all payments.
     *
     * @return MemoizableArray<Payment>
     */
    private function _payments(): MemoizableArray
    {
        if (!isset($this->_payments)) {
            $payments = [];

            foreach ($this->_createPaymentsQuery()->all() as $result) {
                $payments[] = new Payment($result);
            }

            $this->_payments = new MemoizableArray($payments);
        }

        return $this->_payments;
    }

    /**
     * Returns a Query object prepped for retrieving payments.
     *
     * @param bool $withTrashed
     * @return Query
     */
    private function _createPaymentsQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'integrationId',
                'submissionId',
                'fieldId',
                'subscriptionId',
                'amount',
                'currency',
                'status',
                'reference',
                'code',
                'message',
                'note',
                'response',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->orderBy('dateCreated')
            ->from(['{{%formie_payments}}']);
    }

    /**
     * Gets a payment record by its ID, or a new payment record
     * if it wasn't provided or was not found.
     *
     * @param int|string|null $id
     * @return PaymentRecord
     */
    private function _getPaymentRecord(int|string|null $id): PaymentRecord
    {
        /** @var PaymentRecord $payment */
        if ($id && $payment = PaymentRecord::find()->where(['id' => $id])->one()) {
            return $payment;
        }

        return new PaymentRecord();
    }
}
