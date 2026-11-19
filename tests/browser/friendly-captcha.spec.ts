import { test, expect, type Page } from '@playwright/test';
import { build } from 'esbuild';
import { readFileSync } from 'node:fs';
import path from 'node:path';

let script: string;

// A stand-in for the v2 SDK's widget handle. Like the real one, it keeps a
// dot-prefixed state marker in its managed input until the challenge completes.
const fakeFriendlyCaptchaSdk = `
    export class FriendlyCaptchaSDK {
        createWidget(options) {
            const element = options.element;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = options.formFieldName;
            element.appendChild(input);
            const setResponse = (response, name) => {
                input.value = response;
                if (name) element.dispatchEvent(new CustomEvent(name, { detail: { response } }));
            };
            setResponse('.UNACTIVATED');
            window.friendlyCaptchaV2 = { starts: 0, complete: (response) => setResponse(response, 'frc:widget.complete') };
            return {
                addEventListener: (type, listener) => element.addEventListener(type, listener),
                start: () => {
                    window.friendlyCaptchaV2.starts++;
                    setResponse('.REQUESTING');
                    setTimeout(() => setResponse('.SOLVING'), 300);
                    if (window.friendlyCaptchaV2SolveMs !== null) {
                        setTimeout(() => setResponse('v2-solution', 'frc:widget.complete'), window.friendlyCaptchaV2SolveMs);
                    }
                },
                destroy: () => input.remove(),
            };
        }
    }
`;

test.beforeAll(async() => {
    const imports = JSON.parse(readFileSync('packages/formie-browser/package.json', 'utf8')).imports;
    const result = await build({
        stdin: {
            contents: `export { hydrateFormieModules } from './packages/formie-browser/src/js/core/hydrate-modules';`,
            resolveDir: process.cwd(),
        },
        bundle: true,
        format: 'iife',
        globalName: 'friendlyCaptchaBoundary',
        define: { 'process.env.NODE_ENV': '"development"' },
        alias: {
            ...Object.fromEntries(Object.entries(imports).map(([key, value]) => [
                key.replace('/*', ''),
                path.resolve('packages/formie-browser', (typeof value === 'string' ? value : (value as { default: string }).default).replace('/*', '')),
            ])),
            '@verbb/formie-core': path.resolve('packages/formie-core/src/index.ts'),
        },
        plugins: [{
            name: 'inline-assets',
            setup(pluginBuild) {
                pluginBuild.onResolve({ filter: /\?(inline|raw)$/ }, async(args) => {
                    const resolved = await pluginBuild.resolve(args.path.replace(/\?(inline|raw)$/, ''), { resolveDir: args.resolveDir, kind: args.kind });
                    return { path: resolved.path, namespace: 'inline-assets' };
                });
                pluginBuild.onLoad({ filter: /.*/, namespace: 'inline-assets' }, (args) => ({ contents: readFileSync(args.path, 'utf8'), loader: 'text' }));
            },
        }, {
            name: 'fake-friendly-captcha-sdk',
            setup(pluginBuild) {
                pluginBuild.onResolve({ filter: /^@friendlycaptcha\/sdk$/ }, () => ({ path: 'sdk', namespace: 'fake-friendly-captcha-sdk' }));
                pluginBuild.onLoad({ filter: /.*/, namespace: 'fake-friendly-captcha-sdk' }, () => ({ contents: fakeFriendlyCaptchaSdk, loader: 'js' }));
            },
        }],
        write: false,
    });
    script = result.outputFiles[0].text;
});

// friendly-challenge (v1) refuses to start in a browser that reports itself as automated.
test.use({ userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36' });

async function mountForm(page: Page, version: 'v1' | 'v2', tokenFieldName: string) {
    await page.addInitScript(() => {
        Object.defineProperty(Navigator.prototype, 'webdriver', { get: () => false });
    });
    await page.route('https://formie.example.test/**', async(route) => {
        if (route.request().url().endsWith('/boundary.js')) {
            await route.fulfill({ contentType: 'application/javascript; charset=utf-8', body: script });
            return;
        }
        await route.fulfill({
            contentType: 'text/html; charset=utf-8',
            body: '<form><label>Message<input name="message" value="Hello"></label><div data-friendly-captcha-placeholder></div><button>Send</button><output></output></form><script src="/boundary.js"></script>',
        });
    });
    await page.goto('https://formie.example.test/');
    await page.evaluate(async({ version, tokenFieldName }) => {
        const form = document.querySelector('form')!;
        const host = await (window as any).friendlyCaptchaBoundary.hydrateFormieModules({
            root: form,
            surface: 'server-rendered',
            modules: { contractVersion: 2, surface: 'server-rendered', entries: [{
                key: 'captcha', moduleId: `formie:friendly-captcha-${version}`, kind: 'captcha',
                targets: [{ type: 'form' }], required: true,
                config: { siteKey: 'test-site-key', startMode: 'none', handle: 'friendlyCaptcha', theme: 'light' },
            }] },
        });
        (window as any).captchaHost = host;
        form.addEventListener('submit', async(event) => {
            event.preventDefault();
            const output = form.querySelector('output')!;
            output.textContent = 'pending';
            try {
                const tokens = await host.prepare('submit');
                output.textContent = `posted:${tokens[tokenFieldName]}`;
            } catch (error) {
                output.textContent = `aborted:${(error as Error).message}`;
            }
        });
    }, { version, tokenFieldName });
}

// The smallest puzzle friendly-challenge accepts: one solution at difficulty 0,
// expiring in an hour (byte 13 counts five-minute blocks).
function easyPuzzle(): string {
    const buffer = Buffer.alloc(32);
    buffer[13] = 12;
    buffer[14] = 1;
    buffer[15] = 0;

    return `test-signature.${buffer.toString('base64')}`;
}

async function routePuzzle(page: Page, delayMs: number): Promise<string[]> {
    const requests: string[] = [];
    await page.route('https://api.friendlycaptcha.com/api/v1/puzzle**', async(route) => {
        const headers = { 'access-control-allow-origin': '*', 'access-control-allow-headers': '*' };
        if (route.request().method() === 'OPTIONS') {
            await route.fulfill({ status: 204, headers });
            return;
        }
        requests.push(route.request().url());
        await new Promise((resolve) => setTimeout(resolve, delayMs));
        await route.fulfill({ contentType: 'application/json', headers, body: JSON.stringify({ success: true, data: { puzzle: easyPuzzle() } }) });
    });

    return requests;
}

test('v1 starts the puzzle on submit and waits for its solution instead of posting the widget state', async({ page }) => {
    // Fetching the puzzle alone takes longer than the shared two-second token wait.
    const requests = await routePuzzle(page, 2500);
    await mountForm(page, 'v1', 'frc-captcha-solution');
    await expect(page.locator('input[name="frc-captcha-solution"]')).toHaveValue('.UNSTARTED');

    await page.getByRole('button', { name: 'Send' }).click();
    await expect(page.locator('output')).toHaveText(/^posted:test-signature\.[^.]+\.[^.]+\.[^.]+$/, { timeout: 15000 });
    expect(requests).toHaveLength(1);
    await expect(page.locator('[data-formie-captcha-error]')).toHaveCount(0);
});

test('v1 submits an existing solution without starting another puzzle', async({ page }) => {
    const requests = await routePuzzle(page, 0);
    await mountForm(page, 'v1', 'frc-captcha-solution');
    await page.getByRole('button', { name: 'Click to start verification' }).click();
    await expect(page.locator('input[name="frc-captcha-solution"]')).toHaveValue(/^test-signature\./, { timeout: 15000 });
    const solution = await page.locator('input[name="frc-captcha-solution"]').inputValue();

    await page.getByRole('button', { name: 'Send' }).click();
    await expect(page.locator('output')).toHaveText(`posted:${solution}`);
    expect(requests).toHaveLength(1);
});

test('v2 starts the widget on submit and waits for its response instead of posting the widget state', async({ page }) => {
    await page.addInitScript(() => { (window as any).friendlyCaptchaV2SolveMs = 3000; });
    await mountForm(page, 'v2', 'frc-captcha-response');
    await expect(page.locator('input[name="frc-captcha-response"]')).toHaveValue('.UNACTIVATED');

    await page.getByRole('button', { name: 'Send' }).click();
    await expect(page.locator('input[name="frc-captcha-response"]')).toHaveValue('.SOLVING');
    await expect(page.locator('output')).toHaveText('pending');
    await expect(page.locator('output')).toHaveText('posted:v2-solution', { timeout: 10000 });
    expect(await page.evaluate(() => (window as any).friendlyCaptchaV2.starts)).toBe(1);
});

test('v2 aborts with the captcha error when no response arrives in time', async({ page }) => {
    await page.clock.install();
    await page.addInitScript(() => { (window as any).friendlyCaptchaV2SolveMs = null; });
    await mountForm(page, 'v2', 'frc-captcha-response');

    await page.getByRole('button', { name: 'Send' }).click();
    await page.clock.runFor(60000);
    await expect(page.locator('output')).toHaveText('pending');
    await page.clock.runFor(61000);
    await expect(page.locator('output')).toHaveText('aborted:Captcha challenge must be completed.');
    await expect(page.locator('[data-formie-captcha-error]')).toHaveText('Captcha challenge must be completed.');
    expect(await page.evaluate(() => (window as any).friendlyCaptchaV2.starts)).toBe(1);
});
