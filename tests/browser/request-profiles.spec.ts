import { test, expect } from '@playwright/test';

// The owned DDEV server resolves to loopback; grant Chromium's local-network prompt.
// Formie origin authorization and browser CORS remain enabled.
test.use({ permissions: ['local-network-access'] });

const endpoint = 'https://formie-react-tests.ddev.site';
for (const adapter of ['react', 'vue', 'web-components']) {
    for (const transport of ['rest', 'graphql']) {
        test(`${adapter}/${transport}: cross-origin public session submits without cookies`, async({ page }) => {
            await page.route('http://localhost:4179/**', (route) => route.fulfill({ contentType: 'text/html', body: `<button id="unmount">Unmount</button><button id="mount">Mount</button><main id="host"></main><script src="${endpoint}/browser-bundle"></script>` }));
            const requests: Array<{ profile: string; session?: string; cookie?: string }> = [];
            page.on('request', (request) => {
                if (request.method() !== 'POST' || !request.url().startsWith(endpoint)) return;
                const headers = request.headers(); requests.push({ profile: headers['x-formie-profile'], session: headers['x-formie-session'], cookie: headers.cookie });
            });
            await page.goto(`http://localhost:4179/?adapter=${adapter}&transport=${transport}&profile=cross-origin-public&endpoint=${encodeURIComponent(endpoint)}`);
            await page.getByLabel('Visitor name', { exact: false }).fill('Public visitor');
            await expect(page.locator('#host form')).toHaveAttribute('data-formie-request-profile', 'cross-origin-public');
            await expect(page.locator('#host input[type="hidden"][name="handle"]')).toHaveValue('browserContract');
            await expect(page.locator('#host input[type="hidden"][name="CRAFT_CSRF_TOKEN"]')).not.toHaveValue('');
            await page.getByLabel('Visitor email', { exact: false }).fill('public@example.test');
            await page.locator('#host button[type="submit"]').click();
            await expect(page.locator('#host')).toContainText('Submission saved.');
            expect(requests.length).toBeGreaterThanOrEqual(2);
            expect(requests.every((request) => request.profile === 'cross-origin-public' && !request.cookie)).toBe(true);
            expect(requests.at(-1)?.session).toBeTruthy();
        });
    }
}

test('disallowed origins and missing public sessions cannot mutate', async({ request }) => {
    const before = await (await request.get('/browser-saved')).json();
    for (const url of ['/actions/formie/client/submissions/submit', '/actions/formie/server/submissions/submit', '/actions/formie/submissions/submit', '/actions/formie/payment-sessions/initialize', '/actions/formie/file-upload/upload', '/actions/graphql/api']) {
        const response = await request.post(url, { headers: { Origin: 'https://disallowed.example.test', 'X-Formie-Profile': 'cross-origin-public', Accept: 'application/json' }, data: { handle: 'browserContract', values: { visitorName: 'Rejected' }, query: 'mutation { __typename }' } });
        expect(response.status()).toBe(403);
    }
    const missing = await request.post('/actions/formie/client/submissions/submit', { headers: { Origin: 'http://localhost:4179', 'X-Formie-Profile': 'cross-origin-public', Accept: 'application/json' }, data: { handle: 'browserContract' } });
    expect(missing.status()).toBe(403);
    expect(await (await request.get('/browser-saved')).json()).toEqual(before);
});


test('address data endpoints honor the explicit public session and origin policy', async({ request }) => {
    const headers = { Origin: 'http://localhost:4179', 'X-Formie-Profile': 'cross-origin-public', Accept: 'application/json' };
    const bootstrap = await request.post('/actions/formie/client/forms/load', { headers, data: { handle: 'browserContract' } });
    const token = bootstrap.headers()['x-formie-session']; expect(token).toBeTruthy();
    const response = await request.get('/actions/formie/address/subdivisions?country=AU', { headers: { ...headers, 'X-Formie-Session': token } });
    expect(response.status()).toBe(200); expect((await response.json()).subdivisions.length).toBeGreaterThan(0);
    const denied = await request.get('/actions/formie/address/subdivisions?country=AU', { headers: { ...headers, Origin: 'https://disallowed.example.test', 'X-Formie-Session': token } });
    expect(denied.status()).toBe(403);
});
