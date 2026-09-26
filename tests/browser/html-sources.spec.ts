import { test, expect } from '@playwright/test';

test.use({ permissions: ['local-network-access'] });
const endpoint = 'https://formie-react-tests.ddev.site';
for (const adapter of ['react', 'vue', 'web-components']) {
    for (const transport of ['rest', 'graphql']) {
        for (const profile of ['same-origin-browser', 'cross-origin-public']) {
            test(`${adapter}/${transport}/${profile}: sourced HTML uses the normal submit endpoint`, async({ page }) => {
                const query = `adapter=${adapter}&product=server&form=renderedContract&transport=${transport}&profile=${profile}&endpoint=${encodeURIComponent(endpoint)}`;
                if (profile === 'cross-origin-public') {
                    await page.route('http://localhost:4179/**', (route) => route.fulfill({ contentType: 'text/html', body: `<button id="unmount">Unmount</button><button id="mount">Mount</button><main id="host"></main><script src="${endpoint}/browser-bundle"></script>` }));
                }
                await page.goto(profile === 'cross-origin-public' ? `http://localhost:4179/?${query}` : `/browser-fixture?${query}`);
                await page.getByLabel('Visitor name', { exact: false }).fill(`HTML ${adapter}`);
                await page.getByLabel('Quantity', { exact: false }).fill('2');
                await page.getByLabel('Price', { exact: false }).fill('3');
                await expect(page.locator('[name="fields[total]"]')).toHaveValue('6');
                const submit = page.waitForResponse((response) => response.request().method() === 'POST' && response.url().includes('/submissions/submit'));
                await page.locator('#host button[type="submit"]').click();
                const response = await submit;
                expect(response.url()).not.toContain('graphql');
                expect((await response.json()).success).toBe(true);
            });
        }
    }
}

test('GraphQL bootstrap bypasses shared query caching for separate public visitors', async({ request }) => {
    const options = { headers: { Origin: 'http://localhost:4179', 'X-Formie-Profile': 'cross-origin-public' }, data: { query: '{ formieClientForm(handle: "browserContract") { contractVersion session { tokens } } }' } };
    const first = await request.post('/actions/graphql/api', options);
    const second = await request.post('/actions/graphql/api', options);
    expect(first.headers()['x-formie-session']).toBeTruthy();
    expect(second.headers()['x-formie-session']).toBeTruthy();
    expect(first.headers()['x-formie-session']).not.toBe(second.headers()['x-formie-session']);
    expect(first.headers()['cache-control']).toContain('no-store');
    expect((await first.json()).errors).toBeUndefined();
    expect((await second.json()).errors).toBeUndefined();
});

test('stable Formie 3 event adapters clean up and the browser rejects client-rendered mode', async({ page }) => {
    await page.goto('/browser-fixture');
    await page.waitForFunction(() => !!(window as any).legacyModules);
    const result = await page.evaluate(async() => {
        const { bindLegacyDomEventCompatibility, resolveLegacyCompatibilityOptions, createFormieClient } = (window as any).legacyModules;
        const form = document.createElement('form'); document.body.append(form);
        const unbinds: Array<() => void> = []; let success = 0, error = 0;
        form.addEventListener('onAfterFormieSubmit', () => success++);
        form.addEventListener('onFormieSubmitError', () => error++);
        bindLegacyDomEventCompatibility({ target: form, form, instance: {}, options: resolveLegacyCompatibilityOptions(true), unbinds });
        form.dispatchEvent(new CustomEvent('formie:submit:result', { detail: { ok: true } }));
        form.dispatchEvent(new CustomEvent('formie:submit:result', { detail: { ok: false } }));
        unbinds.forEach((unbind) => unbind());
        form.dispatchEvent(new CustomEvent('formie:submit:result', { detail: { ok: true } }));
        let rejected = false;
        try { await createFormieClient().mount(form, { mode: 'client-rendered' }); } catch (error) { rejected = String(error).includes('server-rendered HTML only'); }
        form.remove(); return { success, error, rejected };
    });
    expect(result).toEqual({ success: 1, error: 1, rejected: true });
});
