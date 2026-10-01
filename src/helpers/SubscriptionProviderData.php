<?php
namespace verbb\formie\helpers;

/** Only operational identifiers/flags cross from a provider response into the aggregate. */
final class SubscriptionProviderData
{
    // Static Methods
    // =========================================================================

    public static function project(array $data): array
    {
        $result = [];

        foreach (['id', 'status', 'collection_method', 'currency', 'formieScheduleId', 'formiePaymentLimit', 'formieSetupFee'] as $key) {
            if (isset($data[$key]) && is_scalar($data[$key])) {
                $result[$key] = $data[$key];
            }
        }
        $result['requiresAction'] = !empty($data['requiresAction'])
            || !empty($data['pending_setup_intent']['client_secret'])
            || !empty($data['latest_invoice']['payment_intent']['client_secret']);

        foreach (['customer', 'schedule', 'pending_setup_intent'] as $key) {
            $id = is_array($data[$key] ?? null) ? ($data[$key]['id'] ?? null) : ($data[$key] ?? null);

            if (is_string($id)) {
                $result[$key] = $id;
            }
        }
        return $result;
    }
}
