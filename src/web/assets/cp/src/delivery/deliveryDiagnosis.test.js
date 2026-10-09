import { describe, expect, it } from 'vitest';

import {
    deliveryCheckpoints,
    diagnoseDelivery,
    evidenceMessage,
} from './deliveryDiagnosis';

describe('delivery diagnosis', () => {
    it('uses the render exception as the root cause instead of wrapper queue errors', () => {
        const diagnosis = diagnoseDelivery({
            status: 'failed',
            checkpoints: [
                {
                    checkpoint: 'email-render-exception',
                    data: {
                        exceptions: [{ message: 'Unknown field reference.' }],
                    },
                },
                {
                    checkpoint: 'email-response',
                    data: { error: 'Notification email parse error.' },
                },
                {
                    checkpoint: 'queue-error',
                    data: {
                        exception: {
                            exceptions: [
                                { message: 'Notification delivery failed.' },
                            ],
                        },
                    },
                },
            ],
        });

        expect(diagnosis).toEqual({
            kind: 'email-render',
            detail: 'Notification email parse error.',
            tab: 'responses',
        });
    });

    it('surfaces a provider error before the generic queue failure', () => {
        const diagnosis = diagnoseDelivery({
            status: 'unknown',
            checkpoints: [
                {
                    checkpoint: 'provider-error',
                    data: { status: 504, message: 'Gateway response lost' },
                },
                {
                    checkpoint: 'queue-error',
                    data: { errorType: 'RuntimeException' },
                },
            ],
        });

        expect(diagnosis).toEqual({
            kind: 'provider',
            detail: 'Gateway response lost',
            tab: 'errors',
        });
    });

    it('uses provider evidence recorded by a child integration attempt', () => {
        const bundle = {
            status: 'failed',
            checkpoints: [
                {
                    checkpoint: 'queue-error',
                    data: { errorType: 'RuntimeException' },
                },
            ],
            operations: [
                {
                    uid: 'child-attempt',
                    binding: 'crm',
                    step: 'integration',
                    status: 'failed',
                    checkpoints: [
                        {
                            checkpoint: 'response-error',
                            data: { message: 'The CRM rejected the payload.' },
                        },
                    ],
                },
            ],
        };

        expect(diagnoseDelivery(bundle)).toEqual({
            kind: 'provider',
            detail: 'The CRM rejected the payload.',
            tab: 'errors',
        });
        expect(deliveryCheckpoints(bundle)[1].operation).toEqual({
            uid: 'child-attempt',
            binding: 'crm',
            step: 'integration',
            status: 'failed',
        });
    });

    it('finds useful messages in nested evidence', () => {
        expect(evidenceMessage({ response: { body: 'Invalid API key' } })).toBe(
            'Invalid API key',
        );
    });
});
