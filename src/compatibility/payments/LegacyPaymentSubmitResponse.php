<?php
namespace verbb\formie\compatibility\payments;

/** The Formie 3 submission endpoint alone projects the old browser-event response. */
trait LegacyPaymentSubmitResponse
{
    // Private Methods
    // =========================================================================

    private function _appendLegacyPaymentSubmitResponse(array &$payload): void
    {
        $payment = $payload['payment'] ?? null;

        if (!$payment) {
            return;
        }
        $payload['keepSubmitLoading'] = in_array($payment['status'], ['requiresAction', 'pending', 'unknown'], true);
        $action = $payment['action'] ?? null;

        if ($action && !empty($action['event'])) {
            $payload['submitData'][] = ['event' => $action['event'], 'data' => $action['payload'] ?? []];
        }

        if (($action['type'] ?? null) === 'redirect' && !empty($action['url'])) {
            $payload['redirectUrl'] = $action['url'];
        }
    }
}
