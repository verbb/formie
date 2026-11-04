import { describe, expect, it } from 'vitest';

import {
    deliveryCheckpoints,
    diagnoseDelivery,
    evidenceMessage,
} from './deliveryDiagnosis';

describe('delivery diagnosis', () => {
    it('treats an unknown outcome from an earlier release as a failure', () => {
        expect(
            diagnoseDelivery({ status: 'unknown', checkpoints: [] }).kind,
        ).toBe('failed');
    });
    it('treats a rejection from an earlier release as a failure', () => {
        expect(
            diagnoseDelivery({ status: 'rejected', checkpoints: [] }).kind,
        ).toBe('failed');
    });
    it('surfaces an integration error instead of the queue wrapper', () => {
        expect(
            diagnoseDelivery({
                status: 'failed',
                checkpoints: [
                    {
                        checkpoint: 'exception',
                        data: {
                            exceptions: [{ message: 'Invalid field mapping' }],
                        },
                    },
                    {
                        checkpoint: 'queue-error',
                        data: { message: 'Integration delivery failed' },
                    },
                ],
            }),
        ).toEqual({
            kind: 'integration',
            detail: 'Invalid field mapping',
            tab: 'errors',
        });
    });
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
