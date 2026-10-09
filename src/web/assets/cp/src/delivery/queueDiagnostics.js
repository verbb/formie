const deliveryAttemptPattern = /"deliveryAttemptUid"\s*:\s*"([a-f0-9-]{36})"/i;

export function failedDeliveryAttemptUid(detail) {
    // Craft applies this class to the status and error cells for failed jobs,
    // which avoids coupling the bridge to a translated status label.
    if (!detail?.querySelector('td.error')) {
        return null;
    }

    const code = detail.querySelector('pre code');

    return code?.textContent.match(deliveryAttemptPattern)?.[1] ?? null;
}
