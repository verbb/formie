import { describe, expect, it } from 'vitest';

import { failedDeliveryAttemptUid } from './queueDiagnostics';

const uid = '827ea7aa-db0c-4843-a952-642cd00bbc3b';

function detail({ failed = false, job = '' } = {}) {
    return {
        querySelector(selector) {
            if (selector === 'td.error') {
                return failed ? {} : null;
            }

            if (selector === 'pre code') {
                return { textContent: job };
            }

            return null;
        },
    };
}

describe('failedDeliveryAttemptUid', () => {
    it('returns the delivery locator only for failed Formie jobs', () => {
        const job = JSON.stringify({ deliveryAttemptUid: uid });

        expect(failedDeliveryAttemptUid(detail({ failed: true, job }))).toBe(uid);
        expect(failedDeliveryAttemptUid(detail({ failed: false, job }))).toBeNull();
        expect(failedDeliveryAttemptUid(detail({ failed: true, job: '{}' }))).toBeNull();
    });
});
