import { test, expect, type Page } from '@playwright/test';
import path from 'node:path';

// A page can load the startup script under two URLs, for example when cached
// markup still points at an earlier asset build. Each copy is its own module
// instance, so without a page-level guard both mount every form (#3152).
declare global {
    interface Window {
        duplicateStartupState: { mounts: string[]; renders: number[]; detachedExecutes: number };
    }
}

const ORIGIN = 'https://duplicate-startup.example.test';
const SITE_KEY = 'v3-test-key';

// The second copy either shares the first copy's chunks (same folder, another
// query string) or has its own module graph (another asset folder).
const SECOND_COPIES = {
    'query string': '/assets/js/formie.js?v=stale',
    'asset folder': '/stale/js/formie.js',
} as const;

async function loadPage(page: Page, secondCopy: string, secondCopyTiming: 'at load' | 'after start') {
    const posted: string[][] = [];

    await page.addInitScript(() => {
        window.duplicateStartupState = { mounts: [], renders: [], detachedExecutes: 0 };
        document.addEventListener('formie:mount:after', (event) => {
            window.duplicateStartupState.mounts.push((event.target as HTMLElement).id);
        });
    });

    // Like Google, only a widget whose container is still in the page answers.
    // A detached widget gets an error token instead of a valid one.
    await page.route(/https:\/\/www\.(google\.com|recaptcha\.net)\/recaptcha\/api\.js/, async (route) => {
        await route.fulfill({ contentType: 'application/javascript', body: `
            window.grecaptcha ||= {};
            const containers = new Map();
            setTimeout(() => Object.assign(window.grecaptcha, {
                ready: (cb) => cb(),
                render: (container) => {
                    const id = containers.size;
                    containers.set(id, container);
                    window.duplicateStartupState.renders.push(id);
                    const textarea = document.createElement('textarea');
                    textarea.name = 'g-recaptcha-response';
                    container.appendChild(textarea);
                    return id;
                },
                reset: () => {},
                execute: async (id, options) => {
                    const container = containers.get(id);
                    if (!container || !container.isConnected) {
                        window.duplicateStartupState.detachedExecutes++;
                        return 'error-token';
                    }
                    const token = ${JSON.stringify(SITE_KEY)} + ':' + options.action + ':' + id;
                    container.querySelector('textarea').value = token;
                    return token;
                },
            }), 20);
        ` });
    });

    await page.route(`${ORIGIN}/**`, async (route) => {
        const url = new URL(route.request().url());
        const assetPrefix = ['/assets/', '/stale/'].find((prefix) => url.pathname.startsWith(prefix));

        if (assetPrefix) {
            await route.fulfill({
                path: path.resolve('src/web/assets/frontend/dist', url.pathname.slice(assetPrefix.length)),
                contentType: 'application/javascript; charset=utf-8',
            });
            return;
        }

        if (url.pathname === '/submit') {
            const body = route.request().postData() || '';
            posted.push([...body.matchAll(/name="g-recaptcha-response"\r\n\r\n([^\r]*)/g)].map((match) => match[1]));
            await route.fulfill({ json: {
                success: true, submitAction: 'submit', outcome: 'completed',
                completion: { behavior: 'message', message: 'Subscribed.' },
            } });
            return;
        }

        const modules = JSON.stringify({ contractVersion: 2, surface: 'server-rendered', entries: [{
            key: 'form:formie:recaptcha-v3:0', moduleId: 'formie:recaptcha-v3', kind: 'captcha',
            targets: [{ type: 'form' }], required: true,
            config: { handle: 'recaptcha', placeholderSelector: '[data-recaptcha-placeholder]', siteKey: SITE_KEY, action: 'submit', badge: 'bottomright', theme: 'light', loadingMethod: 'asyncDefer' },
        }] }).replace(/'/g, '&#39;');
        const secondTag = secondCopyTiming === 'at load'
            ? `<script type="module" data-formie-startup src="${secondCopy}"></script>`
            : '';

        await route.fulfill({ contentType: 'text/html; charset=utf-8', body: `
            <!doctype html><html><head><meta charset="utf-8"></head><body>
            <form id="newsletter" data-formie data-formie-form action="/submit" method="post"
                data-formie-submit-method="ajax" data-formie-submit-delay="0"
                data-formie-completion-behavior="message" data-formie-success-message-position="top-form"
                data-formie-modules='${modules}'>
                <div data-formie-form-messages-top></div>
                <div data-formie-form-body><label>Email <input name="fields[email]" value="test@example.com"></label></div>
                <div data-formie-form-footer>
                    <div data-recaptcha-placeholder></div>
                    <button type="submit" data-formie-action="submit">Subscribe</button>
                </div>
            </form>
            ${secondTag}
            <script type="module" data-formie-startup src="/assets/js/formie.js"></script>
            </body></html>`,
        });
    });

    await page.goto(`${ORIGIN}/`);
    await expect.poll(() => page.evaluate(() => window.duplicateStartupState.renders.length)).toBeGreaterThan(0);

    if (secondCopyTiming === 'after start') {
        await page.evaluate(async (src) => {
            await import(src);
        }, secondCopy);
    }

    // Give a second copy time to mount and render if it is going to.
    await page.waitForTimeout(500);

    return posted;
}

for (const [copyName, secondCopy] of Object.entries(SECOND_COPIES)) {
    for (const timing of ['at load', 'after start'] as const) {
        test(`a second startup copy from another ${copyName} ${timing} doesn't mount the form again`, async ({ page }) => {
            const posted = await loadPage(page, secondCopy, timing);
            const state = await page.evaluate(() => window.duplicateStartupState);

            expect(state.mounts).toEqual(['newsletter']);
            expect(state.renders).toEqual([0]);
            await expect(page.locator('form [data-recaptcha-placeholder] textarea')).toHaveCount(1);

            await page.getByRole('button', { name: 'Subscribe' }).click();
            await expect(page.getByText('Subscribed.')).toBeVisible();

            // Every posted value is the live widget's token, so the server can't read an empty one.
            expect(posted).toEqual([[`${SITE_KEY}:submit:0`, `${SITE_KEY}:submit:0`]]);
            expect(await page.evaluate(() => window.duplicateStartupState.detachedExecutes)).toBe(0);
        });
    }
}
