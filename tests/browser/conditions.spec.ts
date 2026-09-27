import { test, expect } from '@playwright/test';
import fixtures from '../fixtures/conditions.json';
for (const adapter of ['react', 'vue', 'web-components']) {
    for (const transport of ['rest', 'graphql']) {
        test(`${adapter}/${transport}: shared parity, exact repeater server errors and authoritative clearing`, async ({ page, request }) => {
            await page.goto(`/browser-fixture?adapter=${adapter}&transport=${transport}&form=conditionContract`);
            await expect(page.getByLabel('Marker', { exact: true })).toBeVisible();
            const results = await page.evaluate((cases) => cases.map((row) => (globalThis as any).conditionBoundary.evaluateCondition(row.operator, row.actual, row.expected, row.type).value), fixtures);
            expect(results).toEqual(fixtures.map((row) => row.result));
            const marker = `${adapter}-${transport}-${Date.now()}`;
            await page.getByLabel('Marker', { exact: true }).fill(marker);
            await page.getByLabel('Allow details', { exact: true }).selectOption('yes');
            await page.getByLabel('Conditional details', { exact: true }).fill('Discarded');
            await page.getByLabel('Allow details', { exact: true }).selectOption('no');
            await expect(page.getByLabel('Conditional details', { exact: true })).not.toBeVisible();
            await page.getByRole('button', { name: 'Add another row', exact: true }).click();
            await page.getByRole('button', { name: 'Add another row', exact: true }).click();
            const emails = page.getByLabel('Row email', { exact: false });
            await emails.nth(0).fill('first@example.com');
            await emails.nth(1).fill('second@example.com');
            let tamper = true;
            // Change the outgoing value after browser validation to require a real server error.
            await page.route(transport === 'rest' ? '**/client/submissions/submit' : '**/graphql/api', async (route) => {
                const body = route.request().postDataJSON();
                const input = transport === 'graphql' ? body.variables?.input : body;
                if (input?.values?.people && tamper) {
                    input.values.people[1].email = 'invalid-email';
                    input.values.secret = 'Attacker posted hidden content';
                    tamper = false;
                    await route.continue({ postData: JSON.stringify(body) });
                } else await route.continue();
            });
            const response = page.waitForResponse((r) => transport === 'rest' ? r.url().includes('/client/submissions/submit') : r.url().includes('/graphql/api') && r.request().postData()?.includes('submitFormieClientForm') === true);
            await page.locator('#host button[type="submit"]').click();
            const rejected = await response;
            expect(rejected.status()).toBe(transport === 'rest' ? 422 : 200);
            const payload = await rejected.json();
            const result = transport === 'graphql' ? payload.data.submitFormieClientForm : payload;
            expect(result.success).toBe(false);
            expect(Object.keys(result.errors.fields)).toEqual([expect.stringMatching(/^\d+\.1\.email$/)]);
            expect(Object.values(result.errors.fields)).toEqual([['Row email is not a valid email address.']]);
            await expect(emails.nth(1)).toHaveAttribute('aria-invalid', 'true');
            await expect(emails.nth(0)).not.toHaveAttribute('aria-invalid', 'true');
            await expect(emails.nth(1)).toBeFocused();
            const errorId = await emails.nth(1).getAttribute('aria-errormessage');
            await expect(page.locator(`[id=${JSON.stringify(errorId)}]`)).toHaveText('Row email is not a valid email address.');
            if (adapter === 'react' && transport === 'rest') await page.screenshot({ path: '../context/tasks/10-validation/nested-client-error.png', fullPage: true });
            expect((await (await request.get('/browser-conditions-saved')).json()).filter((row) => row.marker === marker)).toEqual([]);
            await emails.nth(1).fill('fixed@example.com');
            await page.locator('#host button[type="submit"]').click();
            await expect(page.locator('#host')).toContainText('Submission saved.');
            await expect.poll(async () => (await (await request.get('/browser-conditions-saved')).json()).filter((row) => row.marker === marker)).toEqual([{ marker, secret: '', people: [{ email: 'first@example.com' }, { email: 'fixed@example.com' }] }]);
        });
    }
}

test('posted current-page tampering cannot manufacture progression', async ({ request }) => {
    const bootstrap = await (await request.post('/actions/formie/client/forms/load', { data: { handle: 'browserJourney' } })).json();
    const session = { ...bootstrap.session, currentPageId: bootstrap.definition.pages[1].id };
    const csrf = session.tokens.csrf;
    const response = await request.post('/actions/formie/client/submissions/submit', { data: { handle: 'browserJourney', action: 'submit', operationId: `tamper-${Date.now()}`, session, values: { visitorName: 'Tampered page' }, ...(csrf ? { [csrf.name]: csrf.value } : {}) } });
    const result = await response.json();
    expect(response.status()).toBe(422);
    expect(result.success).toBe(false);
    expect(result.currentPageId).toBe(bootstrap.definition.pages[0].id);
    expect(result.errors.form).not.toEqual([]);
});

for (const adapter of ['react', 'vue', 'web-components']) {
    for (const legacy of [false, true]) {
    test(`${adapter}/${legacy ? 'legacy' : 'server'}: sourced HTML places server repeater errors on the exact input`, async ({ page, request }) => {
        await page.goto(`/browser-fixture?adapter=${adapter}&product=server&form=conditionContract`);
        const marker = `html-nested-${adapter}-${Date.now()}`;
        await page.getByLabel('Marker', { exact: true }).fill(marker);
        await page.getByRole('button', { name: 'Add another row', exact: true }).click();
        await page.getByRole('button', { name: 'Add another row', exact: true }).click();
        const emails = page.getByLabel('Row email', { exact: false });
        await emails.nth(0).fill('first@example.com');
        await emails.nth(1).fill('second@example.com');
        // Honour the real outer request guard before exercising field validation.
        await page.waitForFunction(() => Date.now() - Number((document.querySelector('input[name="formStartedAt"]') as HTMLInputElement)?.value) >= 3500);
        await page.route('**/submissions/submit', async (route) => {
            const body = route.request().postData()!;
            expect(body).toContain('second@example.com');
            await route.continue({ url: legacy ? route.request().url().replace('/server/submissions/', '/submissions/') : route.request().url(), postData: body.replace('second@example.com', 'invalid-email').replace(legacy ? 'formie/server/submissions/submit' : '__unused__', 'formie/submissions/submit') });
        });
        const response = page.waitForResponse((r) => r.url().includes('/submissions/submit') && r.request().method() === 'POST');
        await page.locator('#host button[type="submit"]').click();
        const rejected = await response;
        const result = await rejected.json();
        expect(rejected.status()).toBe(legacy ? 200 : 422);
        expect(result.success).toBe(false);
        expect(result.errors['people.1.email']).toEqual(['Row email is not a valid email address.']);
        await expect(emails.nth(1)).toHaveAttribute('aria-invalid', 'true');
        await expect(emails.nth(0)).not.toHaveAttribute('aria-invalid', 'true');
        await expect(emails.nth(1)).toBeFocused();
        const errorId = await emails.nth(1).getAttribute('aria-errormessage');
        await expect(page.locator(`[id=${JSON.stringify(errorId)}]`)).toHaveText('Row email is not a valid email address.');
        if (adapter === 'react' && !legacy) await page.screenshot({ path: '../context/tasks/10-validation/nested-server-error.png', fullPage: true });
        expect((await (await request.get('/browser-conditions-saved')).json()).filter((row) => row.marker === marker)).toEqual([]);
    });
    }
}

for (const legacy of [false, true]) {
    test(`${legacy ? 'legacy' : 'server'} page endpoint keeps failed navigation on the authoritative page`, async ({ request }) => {
        const bootstrap = await (await request.post('/actions/formie/client/forms/load', { data: { handle: 'browserJourney' } })).json();
        const { session, definition } = bootstrap;
        const csrf = session.tokens.csrf;
        const payload = { handle: 'browserJourney', pageId: definition.pages[1].id, renderId: session.tokens.render, requestToken: session.tokens.request, expectedVersion: String(session.version), ...(csrf ? { [csrf.name]: csrf.value } : {}) };
        const endpoint = `/actions/formie/${legacy ? '' : 'server/'}submissions/set-page`;
        const response = await request.post(endpoint, { form: payload, headers: { Accept: 'application/json' } });
        const result = await response.json();
        expect(response.status()).toBe(legacy ? 200 : 422);
        expect(result.success).toBe(false);
        expect(result.pageId).toBe(definition.pages[0].id);
        expect(result.errors.visitorName).toEqual(['Visitor name cannot be blank.']);
        const allowed = await request.post(endpoint, { form: { ...payload, 'fields[visitorName]': 'Valid navigation' }, headers: { Accept: 'application/json' } });
        expect(allowed.status()).toBe(200);
        const next = await allowed.json();
        expect(next.success).toBe(true);
        expect(next.pageId).toBe(definition.pages[1].id);
        expect(next.session.currentPageId).toBe(definition.pages[1].id);
    });
}

test('DOM projections preserve enable effects, date parts and explicit row scopes', async ({ page }) => {
    await page.goto('/browser-fixture');
    await expect(page.getByLabel('Visitor name', { exact: false })).toBeVisible();
    const results = await page.evaluate(() => {
        const root = document.createElement('div');
        root.innerHTML = '<div data-formie-field-handle="allow"><input name="fields[allow]" value="no"></div><div id="target"><input name="fields[answer]" value="Discard this"><select name="fields[choice]"><option value="retained">Retained first option</option></select></div><div data-formie-field-handle="when"><input name="fields[when][year]" value="2026"><input name="fields[when][month]" value="09"><input name="fields[when][day]" value="27"></div><div data-formie-repeater-item><input name="fields[people][0][email]" value="first@example.com"></div><div data-formie-repeater-item><input name="fields[people][1][email]" value="second@example.com"></div>';
        document.body.append(root);
        const target = root.querySelector('#target')!;
        const configure = (effect: string, handle: string, valueType: string, operator: string, value: unknown, selector = '', transformerParams = {}) => target.setAttribute('data-formie-conditions', JSON.stringify({ version: 1, effect, mode: 'all', rules: [{ source: { target: 'field', handle, selector, transformerParams, isValid: true }, browserSafe: true, valueType, operator, value }] }));
        configure('enable', 'allow', 'text', '=', 'yes');
        (globalThis as any).conditionDomBoundary(root, target);
        const input = target.querySelector('input')!;
        const choice = target.querySelector('select')!;
        const disabled = input.disabled && input.value === '' && choice.disabled && choice.value === '' && choice.selectedIndex === -1 && !target.hasAttribute('data-formie-conditionally-hidden');
        (root.querySelector('[name="fields[allow]"]') as HTMLInputElement).value = 'yes';
        (globalThis as any).conditionDomBoundary(root, target);
        const enabled = !input.disabled;
        configure('show', 'when', 'date', '=', '2026-09-27', 'date');
        const date = (globalThis as any).conditionDomBoundary(root, target).finalResult;
        configure('show', 'people.__ROW__.email', 'collection', 'contains', 'second@example.com', '', { scope: 'all' });
        const rows = (globalThis as any).conditionDomBoundary(root, target).finalResult;
        configure('show', 'people.__ROW__.email', 'text', '=', 'first@example.com');
        const unscoped = (globalThis as any).conditionDomBoundary(root, target).evaluation.value;
        target.setAttribute('data-formie-conditions', JSON.stringify({ version: 1, effect: 'show', mode: 'any', rules: [] }));
        const emptyAny = (globalThis as any).conditionDomBoundary(root, target).shouldHide;
        root.remove();
        return { disabled, enabled, date, rows, unscoped, emptyAny };
    });
    expect(results).toEqual({ disabled: true, enabled: true, date: true, rows: true, unscoped: null, emptyAny: true });
});

test('DOM dependency order clears long legacy chains and reports cycles without looping', async ({ page }) => {
    await page.goto('/browser-fixture');
    await expect(page.getByLabel('Visitor name', { exact: false })).toBeVisible();
    await page.evaluate(async () => {
        const root = document.createElement('div');
        root.id = 'legacy-condition-chain';
        for (let index = 0; index < 7; index++) {
            const node = document.createElement('div');
            node.setAttribute('data-formie-field-handle', `chain${index}`);
            node.innerHTML = `<input name="fields[chain${index}]" value="${index === 6 ? 'no' : 'yes'}">`;
            if (index < 6) node.setAttribute('data-formie-conditions', JSON.stringify({ version: 1, mode: 'all', effect: 'show', rules: [{ source: { target: 'field', handle: `chain${index + 1}`, isValid: true }, valueType: 'text', operator: '=', value: 'yes' }] }));
            root.append(node);
        }
        document.body.append(root);
        (globalThis as any).conditionsGraphCleanup = await (globalThis as any).mountConditionsBoundary(root);
    });
    await expect(page.locator('#legacy-condition-chain [data-formie-conditionally-hidden]')).toHaveCount(6);
    expect(await page.locator('#legacy-condition-chain input').evaluateAll((inputs) => inputs.slice(0, 6).map((input) => (input as HTMLInputElement).value))).toEqual(Array(6).fill(''));
    await page.evaluate(async () => {
        await (globalThis as any).conditionsGraphCleanup?.destroy();
        const root = document.querySelector('#legacy-condition-chain')!;
        const last = root.querySelector('[data-formie-field-handle="chain6"]')!;
        last.setAttribute('data-formie-conditions', JSON.stringify({ version: 1, mode: 'all', effect: 'show', rules: [{ source: { target: 'field', handle: 'chain0', isValid: true }, valueType: 'text', operator: '=', value: 'yes' }] }));
        (globalThis as any).conditionsGraphCleanup = await (globalThis as any).mountConditionsBoundary(root);
    });
    await expect(page.locator('#legacy-condition-chain [data-formie-conditionally-hidden]')).toHaveCount(7);
    await page.evaluate(() => (globalThis as any).conditionsGraphCleanup?.destroy());
});
