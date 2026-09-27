<?php
namespace verbb\formie\console\controllers;

use verbb\formie\Formie;
use verbb\formie\helpers\PaymentRecovery;
use verbb\formie\helpers\Table;
use verbb\formie\models\Payment;

use craft\console\Controller;
use craft\db\Query;
use craft\helpers\Json;

use yii\console\ExitCode;

class PaymentsController extends Controller
{
    // Properties
    // =========================================================================

    public bool $confirmed = false;


    // Public Methods
    // =========================================================================

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), $actionID === 'resolve' ? ['confirmed'] : []);
    }

    /**
     * Lists unresolved payments without exposing card data or gateway credentials.
     */
    public function actionIndex(int $afterId = 0, int $limit = 100): int
    {
        $ids = (new Query())->select('id')->from(Table::FORMIE_PAYMENTS)
            ->where(['status' => [Payment::STATUS_UNKNOWN, Payment::STATUS_PENDING, Payment::STATUS_PROCESSING, Payment::STATUS_REDIRECT]])
            ->andWhere(['>', 'id', $afterId])->orderBy(['id' => SORT_ASC])->limit(max(1, min(500, $limit)))->column();

        foreach ($ids as $id) {
            $this->stdout(Json::encode(PaymentRecovery::inspect((int)$id)) . PHP_EOL);
        }

        return ExitCode::OK;
    }

    /**
     * Shows the persisted owner, amount and references for one payment.
     */
    public function actionReceipts(int $afterId = 0, int $limit = 100): int
    {
        $rows = (new Query())->select([
            'id',
            'integrationId',
            'integrationUid',
            'environment',
            'eventId',
            'eventType',
            'resourceType',
            'resourceReference',
            'providerCreatedAt',
            'status',
            'attempts',
            'receivedAt',
            'verifiedAt',
            'scheduledAt',
            'startedAt',
            'nextAttemptAt',
            'processedAt',
            'error',
            'display',
            'history',
        ])
            ->from(Table::FORMIE_WEBHOOK_RECEIPTS)->where(['>', 'id', $afterId])->orderBy(['id' => SORT_ASC])->limit(max(1, min(500, $limit)))->all();
        foreach ($rows as $row) {
            $this->stdout(Json::encode($row) . PHP_EOL);
        }
        return ExitCode::OK;
    }

    public function actionEvidence(int $receiptId): int
    {
        // Explicit privileged export; ordinary receipt listings never decrypt evidence.
        $this->stdout(Json::encode(Formie::$plugin->getPaymentWebhooks()->evidence($receiptId), JSON_PRETTY_PRINT) . PHP_EOL);
        return ExitCode::OK;
    }

    public function actionRecoverWebhooks(int $limit = 100): int
    {
        $count = Formie::$plugin->getPaymentWebhooks()->recover($limit);
        $this->stdout("Scheduled {$count} verified payment webhook receipts.\n");

        return ExitCode::OK;
    }

    public function actionInspect(int $paymentId): int
    {
        $this->stdout(Json::encode(PaymentRecovery::inspect($paymentId), JSON_PRETTY_PRINT) . PHP_EOL);
        return ExitCode::OK;
    }

    /**
     * Refreshes a saved reference using the gateway's transaction status API.
     */
    public function actionReconcile(int $paymentId): int
    {
        $payment = PaymentRecovery::reconcile($paymentId);
        $this->stdout('Payment ' . $payment->id . ': ' . $payment->status . PHP_EOL);
        return ExitCode::OK;
    }

    /**
     * Records an outcome independently verified in the gateway dashboard.
     */
    public function actionResolve(int $paymentId, string $outcome, string $amount, string $currency, string $reference, string $note): int
    {
        $this->actionInspect($paymentId);
        $this->stdout('Requested outcome: ' . $outcome . ', ' . $amount . ' ' . $currency . ', reference ' . $reference . PHP_EOL);
        $this->stdout("Confirm success only after verifying this payment in the gateway. Confirm failure only after verifying no charge occurred and any open checkout has been canceled.\n");

        if (!$this->confirmed && (!$this->interactive || !$this->confirm('Have you independently verified this outcome?', false))) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        PaymentRecovery::resolve($paymentId, $outcome, $amount, $currency, $reference, $note);
        $this->stdout("Payment outcome saved. Use formie/payments/resume to continue a successful submission.\n");
        return ExitCode::OK;
    }

    /**
     * Resumes submission processing after the payment is verified successful.
     */
    public function actionResume(int $paymentId): int
    {
        PaymentRecovery::resume($paymentId);
        $this->stdout("Submission processing resumed.\n");
        return ExitCode::OK;
    }
}
