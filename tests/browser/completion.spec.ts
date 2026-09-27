import { test, expect } from '@playwright/test';

for (const adapter of ['react', 'vue', 'web-components']) {
    for (const transport of ['rest', 'graphql']) {
    for (const product of ['client', 'server']) {
    for (const behavior of ['message', 'redirect', 'reload', 'reset']) {
        test(`${adapter} ${transport} ${product}: completion ${behavior}`, async ({ page }) => {
            await page.goto(`/browser-fixture?adapter=${adapter}&transport=${transport}&product=${product}&form=completion${behavior[0].toUpperCase()}${behavior.slice(1)}`);
            await page.getByRole('textbox', { name: 'Visitor name', exact: true }).fill(`${adapter} ${behavior}`);
            if (product === 'server') await page.waitForFunction(() => Date.now() - Number((document.querySelector('input[name="formStartedAt"]') as HTMLInputElement)?.value) >= 3500);
            let resolveResult!: (body: any) => void;
            const result = new Promise<any>(resolve => { resolveResult = resolve; });
            await page.route('**/actions/**', async route => {
                const req = route.request();
                if (req.url().includes('/submissions/submit') || (req.url().includes('/graphql/api') && req.postData()?.includes('SubmitFormieClientForm'))) {
                    const response = await route.fetch();
                    resolveResult(await response.json());
                    await route.fulfill({ response });
                } else await route.continue();
            });
            const navigation = ['redirect', 'reload'].includes(behavior) ? page.waitForNavigation() : null;
            await page.getByRole('button', { name: 'Submit', exact: true }).click();
            const envelope = await result;
            const body = envelope.data?.submitFormieClientForm ?? envelope;
            expect(body.outcome).toBe('completed');
            expect(body.completion.behavior).toBe(behavior);
            if (navigation) await navigation;
            if (behavior === 'redirect') await expect(page).toHaveURL(/browser-completion-done\?utm_medium=explicit/);
            if (behavior === 'reset') await expect(page.getByRole('textbox', { name: 'Visitor name', exact: true })).toHaveValue('');
            if (behavior === 'message') {
                await expect(page.getByText('Submission saved.', { exact: true })).toBeVisible();
                await expect(page.getByRole('textbox', { name: 'Visitor name', exact: true })).not.toBeVisible();
            }
        });
    }
}
}
}

test('two Twig occurrences keep forced values isolated through untrusted submission', async ({ page, request }) => {
    await page.goto('/browser-completion-instances?note=attacker');
    const forms = page.locator('form[data-formie]');
    await expect(forms).toHaveCount(2);
    for (const [i, label] of ['A', 'B'].entries()) {
        const form = forms.nth(i);
        await expect(form.getByRole('textbox', { name: 'Note', exact: true })).toHaveValue(`Server ${label}`);
        await form.getByRole('textbox', { name: `Visitor ${label}`, exact: true }).fill(`Instance ${label}`);
        await form.getByRole('textbox', { name: 'Note', exact: true }).fill('attacker');
        await page.waitForFunction(() => Date.now() - Number((document.querySelector('input[name="formStartedAt"]') as HTMLInputElement)?.value) >= 3500);
        const result = page.waitForResponse(r => r.request().method() === 'POST');
        await form.getByRole('button', { name: 'Submit', exact: true }).click();
        expect((await (await result).json()).completion.behavior).toBe('message');
    }
    const saved = await (await request.get('/browser-completion-saved')).json();
    for (const label of ['A', 'B']) expect(saved.find(row => row.name === `Instance ${label}`).note).toBe(`Server ${label}`);
});

for (const transport of ['rest', 'graphql']) {
    test(`${transport}: query capture, explicit empty, authoritative Hidden and settings injection`, async ({ request }) => {
        const query = { note: '{{ literal }}', utm_source: 'journey', utm_medium: 'captured', token: 'secret', arbitrary: 'bad' };
        let loaded: any;
        if (transport === 'rest') loaded = await (await request.post('/actions/formie/client/forms/load', { data: { handle: 'completionRedirect', query, settings: { redirectUrl: 'https://evil.test' } } })).json();
        else {
            const data = await (await request.post('/actions/graphql/api', { data: { query: 'query($query: Json) { formieClientForm(handle: "completionRedirect", query: $query) { definition session { id version currentPageId tokens continuation } } }', variables: { query } } })).json();
            expect(data.errors).toBeUndefined(); loaded = data.data.formieClientForm;
        }
        const session = loaded.session;
        const values = { visitorName: `Query ${transport}`, note: '', serverDate: 'attacker' };
        const csrf = session.tokens.csrf;
        let result: any;
        if (transport === 'rest') result = await (await request.post('/actions/formie/client/submissions/submit?utm_source=replaced', { data: { handle: 'completionRedirect', session, values, settings: { redirectUrl: 'https://evil.test' }, query: { utm_source: 'replaced' }, [csrf.name]: csrf.value } })).json();
        else {
            const data = await (await request.post('/actions/graphql/api?utm_source=replaced', { data: { query: 'mutation($input: FormieClientSubmitInput!) { submitFormieClientForm(input: $input) { outcome completion } }', variables: { input: { handle: 'completionRedirect', session, values, browserData: { settings: { redirectUrl: 'https://evil.test' } } } }, [csrf.name]: csrf.value } })).json();
            expect(data.errors).toBeUndefined(); result = data.data.submitFormieClientForm;
        }
        expect(result.outcome).toBe('completed');
        expect(result.completion.url).toBe('/browser-completion-done?utm_source=journey&utm_medium=explicit');
        const saved = (await (await request.get('/browser-completion-saved')).json()).find(row => row.name === `Query ${transport}`);
        expect(saved.note).toBe(''); expect(saved.date).not.toBe('attacker');
    });
}

for (const behavior of ['message', 'redirect', 'reload', 'reset', 'newtab', 'malicious']) {
    test(`native HTML: ${behavior}`, async ({ page }) => {
        await page.goto(`/browser-completion-native-${behavior}?utm_source=native&arbitrary=never`);
        await page.getByRole('textbox', { name: 'Visitor name', exact: true }).fill(`Native ${behavior}`);
        await page.waitForFunction(() => Date.now() - Number((document.querySelector('input[name="formStartedAt"]') as HTMLInputElement)?.value) >= 3500);
        await Promise.all([page.waitForNavigation(), page.getByRole('button', { name: 'Submit', exact: true }).click()]);
        if (behavior === 'redirect') await expect(page).toHaveURL(/browser-completion-done\?utm_source=native$/);
        else if (behavior === 'newtab') {
            const link = page.getByRole('link', { name: 'Continue', exact: true });
            await expect(link).toHaveAttribute('rel', 'noopener noreferrer');
            const popup = page.waitForEvent('popup'); await link.click();
            const opened = await popup; await opened.waitForLoadState();
            expect(await opened.evaluate(() => window.opener === null)).toBe(true);
            await opened.close();
        } else {
            await expect(page).toHaveURL(/browser-completion-native-/);
            if (behavior === 'message' || behavior === 'malicious') await expect(page.getByText('Submission saved.', { exact: true })).toBeVisible();
            if (behavior === 'reset') await expect(page.getByRole('textbox', { name: 'Visitor name', exact: true })).toHaveValue('');
        }
    });
}
for (const adapter of ['react', 'vue', 'web-components']) {
    test(`${adapter}: explicit host query prefill stays literal`, async ({ page }) => {
        await page.goto(`/browser-fixture?adapter=${adapter}&form=completionMessage&note=${encodeURIComponent('{{ literal }}')}`);
        await expect(page.getByRole('textbox', { name: 'Note', exact: true })).toHaveValue('{{ literal }}');
    });
}

test('forced values survive a portable save and resume without the original render', async ({ page, browser, baseURL, request }) => {
    await page.goto('/browser-completion-instances?utm_source=original');
    const form = page.locator('form[data-formie]').first();
    await form.getByRole('textbox', { name: 'Visitor A', exact: true }).fill('Portable forced');
    await form.getByRole('textbox', { name: 'Note', exact: true }).fill('attacker');
    const response = page.waitForResponse(r => r.request().method() === 'POST');
    await form.getByRole('button', { name: /Save/ }).click();
    const saved = await (await response).json();
    expect(saved.outcome).toBe('draftSaved'); expect(saved.completion).toBeFalsy();
    const context = await browser.newContext({ baseURL, ignoreHTTPSErrors: true });
    try {
        const loaded = await (await context.request.post('/actions/formie/client/forms/load', { data: { handle: 'completionMessage', grantToken: saved.resumeToken, grantPurpose: 'continue-incomplete', query: { note: 'replacement', utm_source: 'replacement' } } })).json();
        const csrf = loaded.session.tokens.csrf;
        const completed = await (await context.request.post('/actions/formie/client/submissions/submit', { data: { handle: 'completionMessage', session: loaded.session, values: { visitorName: 'Portable forced', note: 'tampered after resume' }, [csrf.name]: csrf.value } })).json();
        expect(completed.outcome).toBe('completed'); expect(completed.completion.behavior).toBe('message');
        const rows = await (await request.get('/browser-completion-saved')).json();
        expect(rows.find(row => row.name === 'Portable forced').note).toBe('Server A');
    } finally { await context.close(); }
});

for (const behavior of ['message', 'redirect', 'reload', 'reset']) {
    test(`payment return consumes completion ${behavior}`, async ({ page, request }) => {
        const tokens = await (await request.get('/browser-completion-payments')).json();
        const statusToken = tokens[behavior];
        const status = await (await request.get(`/actions/formie/payment-status/poll-status?statusToken=${encodeURIComponent(statusToken)}&redirect=https://evil.test&checkGateway=1`)).json();
        expect(status.status).toBe('success'); expect(status.completion.behavior).toBe(behavior);
        await page.route('https://cdn.tailwindcss.com/**', route => route.fulfill({ body: '' }));
        await page.goto(`/actions/formie/payment-status/status?statusToken=${encodeURIComponent(statusToken)}`);
        if (behavior === 'redirect') await expect(page).toHaveURL(/browser-completion-done/, { timeout: 10000 });
        else if (behavior === 'reload' || behavior === 'reset') await expect(page).toHaveURL(/browser-fixture/, { timeout: 10000 });
        else await expect(page.getByRole('status')).toHaveText('Submission saved.', { timeout: 10000 });
    });
}
