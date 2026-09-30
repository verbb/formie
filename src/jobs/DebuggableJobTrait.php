<?php
namespace verbb\formie\jobs;

use verbb\formie\Formie;
use verbb\formie\helpers\DeliveryDiagnostics;

use yii\queue\ExecEvent;

use Throwable;

trait DebuggableJobTrait
{
    // Public Methods
    // =========================================================================

    public function onError(ExecEvent $event): void
    {
        try {
            Formie::$plugin->getDeliveryAttempts()->checkpoint($this->getDeliveryAttemptUid(), 'queue-error', [
                'queueId' => $event->id,
                'errorType' => $event->error ? get_class($event->error) : null,
                'exception' => $event->error ? DeliveryDiagnostics::exception($event->error) : null,
            ]);
        } catch (Throwable) {
            // The pre-execution checkpoint remains when storage or the worker fails.
            Formie::error('Unable to append delivery queue diagnostics.');
        }
    }
}
