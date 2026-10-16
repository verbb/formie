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

for (const initialApi of ['absent', 'ready-stub', 'enterprise', 'blocked']) {
    test(`production v3 module handles submission with ${initialApi} SDK state`, async ({ page }) => {
        const requestedAssets: string[] = [];
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
        await page.route('https://www.recaptcha.net/recaptcha/api.js*', async (route) => {
            if (initialApi === 'blocked') {
                await route.abort('failed');
                return;
            }
            // Match the two-stage SDK load: api.js can finish before its API is ready.
            await route.fulfill({ contentType: 'application/javascript', body: `
                window.grecaptcha ||= {};
                setTimeout(() => Object.assign(window.grecaptcha, {
                    ready: cb => cb(), render: () => 0, reset: () => {},
                    execute: async (key, options) => key + ':' + options.action,
                }), 30);
            ` });
        });
        await page.goto('https://formie.example.test/');
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
                    config: { siteKey: 'v3-test-key', action: 'contact', handle: 'recaptcha' },
                }] },
            });
            (window as any).captchaHost = host;
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const tokens = await host.prepare('submit');
                form.querySelector('output')!.textContent = tokens['g-recaptcha-response'];
            });
        }, initialApi);
        if (initialApi === 'blocked') {
            await expect(page.locator('[data-formie-module-error]')).toBeVisible();
            await page.getByRole('button', { name: 'Send' }).click();
            await expect(page.locator('output')).toBeEmpty();
        } else {
            await page.getByRole('button', { name: 'Send' }).click();
            await expect(page.locator('output')).toHaveText('v3-test-key:contact');
            await expect(page.locator('[data-formie-module-error]')).toHaveCount(0);
            expect(await page.evaluate(() => (window as any).captchaHost.failures)).toEqual([]);
        }
        expect(requestedAssets.some((asset) => /recaptcha-v3-/.test(asset))).toBe(true);
        await page.evaluate(() => (window as any).captchaHost.destroy());
    });
}
