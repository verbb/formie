import { expect, it } from 'vitest';

import { diagnosticSummary } from './deliveryDiagnostics';

it('keeps submission and provider values out of the copied diagnostic summary', () => {
    const summary = diagnosticSummary({
        uid: 'attempt-uid',
        submissionId: 42,
        binding: 'crm',
        step: 'integration',
        execution: 'queued',
        status: 'failed',
        result: { status: 'failed', code: 'provider_rejected', message: 'person@example.test failed', diagnostics: { input: 'private-value' } },
        checkpoints: [{ checkpoint: 'submission-projection', dateCreated: '2026-09-29', data: { email: 'person@example.test' } }],
        operations: [{
            uid: 'operation-uid',
            binding: 'crm',
            step: 'create-contact',
            status: 'failed',
            result: { status: 'failed', code: 'provider_rejected' },
            checkpoints: [{ checkpoint: 'request', dateCreated: '2026-09-29', data: { apiPayload: 'private-value' } }],
        }],
    });
    const encoded = JSON.stringify(summary);

    expect(encoded).toContain('provider_rejected', 'submission-projection', 'request')
        .not.toContain('person@example.test', 'private-value', 'data');
});
