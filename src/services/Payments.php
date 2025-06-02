<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\PaymentEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Payment;
use verbb\formie\records\Payment as PaymentRecord;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
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


    // Properties
    // =========================================================================

    private ?MemoizableArray $_payments = null;


    // Public Methods
    // =========================================================================

    public function getAllPayments(): array
    {
        return $this->_payments()->all();
    }

    public function getPaymentById(int $id): ?Payment
    {
        return $this->_payments()->firstWhere('id', $id);
    }

    public function getPaymentByReference(string $reference): ?Payment
    {
        return $this->_payments()->firstWhere('reference', $reference);
    }

    public function getSubmissionPayments(Submission $submission): array
    {
        return $this->_payments()->where('submissionId', $submission->id)->all();
    }

    public function getPaymentByUid(string $uid): ?Payment
    {
        return $this->_payments()->firstWhere('uid', $uid, true);
    }

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
            Formie::info('Payment not saved due to validation error.');

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
            $paymentRecord->redirectUrl = $payment->redirectUrl;
            $paymentRecord->note = $payment->note;
            $paymentRecord->response = $payment->response;

            $paymentRecord->save(false);

            $payment->id = $paymentRecord->id;
            $payment->uid = $paymentRecord->uid;

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

    public function deletePaymentById(int $id): bool
    {
        $payment = $this->getPaymentById($id);

        if (!$payment) {
            return false;
        }

        return $this->deletePayment($payment);
    }

    public function deletePayment(Payment $payment): bool
    {
        // Fire a 'beforeDeletePayment' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_PAYMENT)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_PAYMENT, new PaymentEvent([
                'payment' => $payment,
            ]));
        }

        Db::delete(Table::FORMIE_PAYMENTS, [
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
                'redirectUrl',
                'note',
                'response',
                'dateCreated',
                'dateUpdated',
                'uid',
            ])
            ->orderBy('dateCreated')
            ->from([Table::FORMIE_PAYMENTS]);
    }

    private function _getPaymentRecord(int|string|null $id): PaymentRecord
    {
        /** @var PaymentRecord $payment */
        if ($id && $payment = PaymentRecord::find()->where(['id' => $id])->one()) {
            return $payment;
        }

        return new PaymentRecord();
    }
}
