import { test, expect, type APIRequestContext } from '@playwright/test';

test('portable save associates two browsers with one progress row and rejects the stale writer', async ({ browser, baseURL }) => {
    const first = await browser.newContext({ baseURL, ignoreHTTPSErrors: true });
    const second = await browser.newContext({ baseURL, ignoreHTTPSErrors: true });
    const load = async (request: APIRequestContext, extra = {}) => {
        const response = await request.post('/actions/formie/client/forms/load', { data: { handle: 'browserJourney', ...extra } });
        expect(response.ok()).toBeTruthy();
        return response.json();
    };
    const save = async (request: APIRequestContext, session: any, operationId: string, name: string) => {
        const csrf = session.tokens.csrf;
        return request.post('/actions/formie/client/submissions/submit', {
            data: { handle: 'browserJourney', action: 'save', operationId, session, values: { visitorName: name }, ...(csrf ? { [csrf.name]: csrf.value } : {}) },
        });
    };
    try {
        const initial = await load(first.request);
        expect(initial.session.continuation?.resumeToken).toBeUndefined();
        const saved = await (await save(first.request, initial.session, 'portable-first', 'Original browser')).json();
        expect(saved.outcome).toBe('draftSaved');
        expect(saved.resumeToken).toBeTruthy();
        const resumed = await load(second.request, { grantToken: saved.resumeToken, grantPurpose: 'continue-incomplete' });
        expect(resumed.session.continuation.progressId).toBe(saved.session.continuation.progressId);
        const changed = await (await save(first.request, saved.session, 'portable-next', 'First browser still authorised')).json();
        expect(changed.outcome).toBe('draftSaved');
        const staleResponse = await save(second.request, resumed.session, 'portable-stale', 'Stale second browser');
        const stale = await staleResponse.json();
        expect(staleResponse.status()).toBe(409);
        expect(stale.outcome).toBe('stateConflict');
        expect(stale.version).toBe(changed.version);
    } finally {
        await first.close();
        await second.close();
    }
});
