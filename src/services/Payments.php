<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\events\PaymentEvent;
use verbb\formie\events\PaymentSuccessRedirectEvent;
use verbb\formie\helpers\ArrayHelper;
use verbb\formie\helpers\DbSchema;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\Table;
use verbb\formie\models\Payment;
use verbb\formie\records\Payment as PaymentRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\Json;
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
    public const EVENT_DEFINE_PAYMENT_SUCCESS_REDIRECT_URL = 'definePaymentSuccessRedirectUrl';


    // Properties
    // =========================================================================

    private array $_paymentByKey = [];


    // Public Methods
    // =========================================================================

    public function getAllPayments(): array
    {
        return array_map(static function(array $result): Payment {
            return new Payment($result);
        }, $this->_createPaymentsQuery()->all());
    }

    public function getPaymentById(int $id): ?Payment
    {
        return $this->_findPayment(['id' => $id]);
    }

    public function getPaymentByReference(string $reference): ?Payment
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        return $this->_findPayment(['reference' => $reference]);
    }

    public function getSubmissionPayments(Submission $submission): array
    {
        if (!$submission->id) {
            return [];
        }

        return array_map(static function(array $result): Payment {
            return new Payment($result);
        }, $this->_createPaymentsQuery()->where(['submissionId' => (int)$submission->id])->all());
    }

    public function getPaymentByUid(string $uid): ?Payment
    {
        $uid = trim($uid);

        if ($uid === '') {
            return null;
        }

        return $this->_findPayment(['uid' => $uid]);
    }

    public function resolvePaymentSuccessRedirectUrl(Payment $payment, Submission $submission, Form $form, ?string $url): string
    {
        $url = (string)($url ?? '');

        if ($url !== '') {
            $url = Formie::$plugin->getTemplates()->renderObjectTemplate($url, $submission);
        }

        $event = new PaymentSuccessRedirectEvent([
            'payment' => $payment,
            'submission' => $submission,
            'form' => $form,
            'redirectUrl' => $url,
        ]);

        $this->trigger(self::EVENT_DEFINE_PAYMENT_SUCCESS_REDIRECT_URL, $event);

        return StringHelper::sanitizeRedirectUrl($event->redirectUrl);
    }

    public function resolvePaymentFailureRedirectUrl(Payment $payment, Submission $submission, Form $form): string
    {
        $url = StringHelper::sanitizeRedirectUrl((string)($payment->redirectUrl ?? ''));

        if ($url === '') {
            $request = Craft::$app->getRequest();

            if ($request->getIsWebRequest()) {
                $url = StringHelper::sanitizeRedirectUrl((string)$request->getReferrer());
            }
        }

        if ($url === '') {
            $url = StringHelper::sanitizeRedirectUrl($form->getRedirectUrl(false, false));
        }

        return $url;
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
        $this->_paymentByKey = [];

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
        $this->_paymentByKey = [];

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

    private function _findPayment(array $where): ?Payment
    {
        $cacheKey = Json::encode($where);

        if (array_key_exists($cacheKey, $this->_paymentByKey)) {
            return $this->_paymentByKey[$cacheKey];
        }

        $result = $this->_createPaymentsQuery()->where($where)->one();
        $payment = $result ? new Payment($result) : null;
        $this->_paymentByKey[$cacheKey] = $payment;

        return $payment;
    }

    private function _createPaymentsQuery(): Query
    {
        $select = [
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
        ];

        if (DbSchema::columnExists(Table::FORMIE_PAYMENTS, 'redirectUrl')) {
            array_splice($select, 11, 0, ['redirectUrl']);
        }

        return (new Query())
            ->select($select)
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
