import { test, expect } from '@playwright/test';
import { build } from 'esbuild';
import path from 'node:path';

let script: string;

test.beforeAll(async () => {
    const result = await build({
        stdin: {
            contents: `export { loadRecaptchaGlobal, whenRecaptchaReady } from './packages/formie-browser/src/js/modules/captchas/recaptcha-shared';`,
            resolveDir: process.cwd(),
        },
        bundle: true,
        format: 'iife',
        globalName: 'recaptchaBoundary',
        alias: {
            '#utils': path.resolve('packages/formie-browser/src/js/utils'),
            '#modules': path.resolve('packages/formie-browser/src/js/modules'),
        },
        write: false,
    });
    script = result.outputFiles[0].text;
});

for (const enterprise of [false, true]) {
    test(`loads the ${enterprise ? 'Enterprise' : 'standard'} SDK when only the other API exists`, async ({ page }) => {
        const requests: string[] = [];
        await page.route(/https:\/\/www\.(google\.com|recaptcha\.net)\/recaptcha\//, async (route) => {
            requests.push(route.request().url());
            await route.fulfill({ contentType: 'application/javascript', body: enterprise
                ? 'window.grecaptcha.enterprise = { ready: cb => cb(), execute: async () => "enterprise-token", render: () => 0, reset: () => {} };'
                : 'Object.assign(window.grecaptcha, { ready: cb => cb(), execute: async () => "standard-token", render: () => 0, reset: () => {} });',
            });
        });
        await page.setContent('<form></form>');
        await page.addScriptTag({ content: script });
        const token = await page.evaluate(async (enterprise) => {
            const api = { ready: (cb: () => void) => cb(), execute: async () => 'wrong-api-token', render: () => 0, reset: () => {} };
            (window as any).grecaptcha = enterprise ? api : { enterprise: api };
            const loaded = await (window as any).recaptchaBoundary.loadRecaptchaGlobal({ siteKey: 'test-key' }, enterprise, 'test-key');
            return (enterprise ? loaded.enterprise : loaded)?.execute('test-key');
        }, enterprise);
        expect(token).toBe(enterprise ? 'enterprise-token' : 'standard-token');
        expect(requests).toHaveLength(1);
    });
}

test('standard reCAPTCHA uses its own ready queue when Enterprise also exists', async ({ page }) => {
    await page.setContent('<form></form>');
    await page.addScriptTag({ content: script });
    const calls = await page.evaluate(async () => {
        const calls: string[] = [];
        const api = {
            ready: (callback: () => void) => { calls.push('standard'); callback(); },
            enterprise: { ready: (callback: () => void) => { calls.push('enterprise'); callback(); } },
        };
        await (window as any).recaptchaBoundary.whenRecaptchaReady(api, () => { calls.push('execute'); });
        return calls;
    });
    expect(calls).toEqual(['standard', 'execute']);
});

for (const initialApi of ['absent', 'ready-stub', 'enterprise', 'blocked', 'layout-generic', 'layout-other-key', 'layout-matching-key', 'invalid-site-key']) {
    test(`production v3 module handles submission with ${initialApi} SDK state`, async ({ page }) => {
        const requestedAssets: string[] = [];
        const requestedScripts: string[] = [];
        await page.route('https://formie.example.test/**', async (route) => {
            const url = new URL(route.request().url());
            if (url.pathname.startsWith('/browser/')) {
                const asset = url.pathname.slice('/browser/'.length);
                requestedAssets.push(asset);
                await route.fulfill({ path: path.resolve('packages/formie-browser/dist', asset), contentType: 'application/javascript' });
                return;
            }
            await route.fulfill({ contentType: 'text/html', body: '<form><label>Message<input name="message" value="Hello"></label><div data-recaptcha-placeholder></div><button>Send</button><output></output></form>' });
        });
        await page.route(/https:\/\/www\.(google\.com|recaptcha\.net)\/recaptcha\/api\.js/, async (route) => {
            requestedScripts.push(route.request().url());
            if (initialApi === 'blocked') {
                await route.abort('failed');
                return;
            }
            // Only keys registered by render= or an explicit widget can execute.
            // A usable API alone must not imply that Formie's site key is loaded.
            const autoKey = new URL(route.request().url()).searchParams.get('render');
            await route.fulfill({ contentType: 'application/javascript', body: `
                window.grecaptcha ||= {};
                window.recaptchaRenders = [];
                window.recaptchaResets = [];
                const clients = new Map();
                const autoKey = ${JSON.stringify(autoKey)};
                const renderBadge = (container) => {
                    const badge = document.createElement('div');
                    badge.className = 'grecaptcha-badge';
                    badge.textContent = 'reCAPTCHA';
                    container.appendChild(badge);
                };
                // Automatic site-key loading owns a body-level badge; explicit
                // rendering must instead receive this form's placeholder.
                if (autoKey && autoKey !== 'explicit') {
                    clients.set(autoKey, autoKey);
                    renderBadge(document.body);
                }
                setTimeout(() => Object.assign(window.grecaptcha, {
                    ready: cb => cb(),
                    render: (container, options) => {
                        if (options.sitekey === 'invalid') throw new Error('Invalid site key');
                        const id = window.recaptchaRenders.length;
                        window.recaptchaRenders.push(options);
                        clients.set(id, options.sitekey);
                        renderBadge(container);
                        return id;
                    },
                    reset: id => window.recaptchaResets.push(id),
                    execute: async (id, options) => {
                        if (!clients.has(id)) throw new Error('Invalid site key or not loaded in api.js: ' + id);
                        return clients.get(id) + ':' + options.action;
                    },
                }), 30);
            ` });
        });
        await page.goto('https://formie.example.test/');
        if (initialApi.startsWith('layout-')) {
            const query = initialApi === 'layout-generic' ? '' : `?render=${initialApi === 'layout-other-key' ? 'other-key' : 'v3-test-key'}`;
            await page.addScriptTag({ url: `https://www.google.com/recaptcha/api.js${query}` });
            await page.waitForFunction(() => typeof (window as any).grecaptcha?.execute === 'function');
        }
        await page.evaluate(async (initialApi) => {
            if (initialApi === 'ready-stub') (window as any).grecaptcha = { ready: () => {} };
            if (initialApi === 'enterprise') (window as any).grecaptcha = { enterprise: { ready: (cb: () => void) => cb() } };
            const { hydrateFormieModules } = await import('/browser/index.js');
            const form = document.querySelector('form')!;
            const host = await hydrateFormieModules({
                root: form,
                surface: 'server-rendered',
                modules: { contractVersion: 2, surface: 'server-rendered', entries: [{
                    key: 'captcha', moduleId: 'formie:recaptcha-v3', kind: 'captcha',
                    targets: [{ type: 'form' }], required: true,
                    config: { siteKey: initialApi === 'invalid-site-key' ? 'invalid' : 'v3-test-key', action: 'contact', handle: 'recaptcha', badge: 'inline', theme: 'dark', loadingMethod: 'asyncDefer' },
                }] },
            });
            (window as any).captchaHost = host;
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const tokens = await host.prepare('submit');
                const token = tokens['g-recaptcha-response'];
                form.querySelector('output')!.textContent = typeof token === 'string' ? token : '';
            });
        }, initialApi);
        if (initialApi === 'blocked' || initialApi === 'invalid-site-key') {
            await expect(page.locator('[data-formie-module-error]')).toBeVisible();
            await page.getByRole('button', { name: 'Send' }).click();
            await expect(page.locator('output')).toBeEmpty();
        } else {
            await expect(page.locator('form [data-recaptcha-placeholder] .grecaptcha-badge')).toBeVisible();
            await expect(page.locator('form .grecaptcha-badge')).toHaveCount(1);
            if (!initialApi.startsWith('layout-')) {
                await expect(page.locator('.grecaptcha-badge')).toHaveCount(1);
            }
            await page.getByRole('button', { name: 'Send' }).click();
            await expect(page.locator('output')).toHaveText('v3-test-key:contact');
            await expect(page.locator('[data-formie-module-error]')).toHaveCount(0);
            expect(await page.evaluate(() => (window as any).captchaHost.failures)).toEqual([]);
            expect(await page.evaluate(() => (window as any).recaptchaRenders)).toEqual([
                expect.objectContaining({ sitekey: 'v3-test-key', size: 'invisible', badge: 'inline', theme: 'dark' }),
            ]);
            // Expiry invalidates an existing token, and another submit obtains a fresh one.
            await page.evaluate(() => (window as any).recaptchaRenders[0]['expired-callback']());
            await expect(page.locator('input[name="g-recaptcha-response"]')).toHaveValue('');
            await page.getByRole('button', { name: 'Send' }).click();
            await expect(page.locator('input[name="g-recaptcha-response"]')).toHaveValue('v3-test-key:contact');
        }
        expect(requestedAssets.some((asset) => /recaptcha-v3-/.test(asset))).toBe(true);
        expect(requestedScripts).toHaveLength(1);
        await page.evaluate(() => (window as any).captchaHost.destroy());
        if (initialApi !== 'blocked' && initialApi !== 'invalid-site-key') {
            expect(await page.evaluate(() => (window as any).recaptchaResets)).toEqual([0]);
            await expect(page.locator('input[name="g-recaptcha-response"]')).toHaveValue('');
        }
    });
}
