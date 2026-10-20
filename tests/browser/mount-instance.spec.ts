import { test, expect, type Page, type Route } from '@playwright/test';
import path from 'node:path';
import type { BeforeSubmitContext, FormieFormInstance, FormSubmitResult } from '../../packages/formie-browser/src';

declare global {
    interface Window {
        mountInstanceState: {
            mounts: Array<{ id: string; instance?: FormieFormInstance; root: EventTarget | null }>;
            unmounts: string[];
            legacyReady: number;
            checks: string[];
        };
    }
}

async function mountFixture(page: Page, compatibility: boolean, check: (route: Route) => Promise<void>, submitted: () => void) {
    await page.addInitScript(() => {
        const state: Window['mountInstanceState'] = {
            mounts: [], unmounts: [], legacyReady: 0, checks: [],
        };
        window.mountInstanceState = state;
        document.addEventListener('onFormieReady', () => state.legacyReady++);
        document.addEventListener('formie:mount:after', (event) => {
            const { id, instance } = (event as CustomEvent<{ id: string; instance?: FormieFormInstance }>).detail;
            state.mounts.push({ id, instance, root: event.target });
            if (!instance) return;

            // Removed roots no longer bubble their teardown event to document.
            instance.root.addEventListener('formie:unmount:after', () => state.unmounts.push(id), { once: true });

            // Register through the public event, without importing or mounting a client.
            instance.on('formie:stage:validate:after', async (payload) => {
                const { action, formData, result, abort } = payload as BeforeSubmitContext & { result: FormSubmitResult | null };
                if (action !== 'submit' || result?.ok === false) return;

                state.checks.push(instance.id);
                try {
                    const response = await fetch('/check-form', { method: 'POST', body: formData });
                    if (!response.ok) throw new Error('Check failed.');
                    const result = await response.json();
                    if (result.valid !== true) abort('Please check your booking code.');
                } catch {
                    abort('Could not check your booking. Please try again.');
                }
            });
        });
    });

    await page.route('https://mount-instance.example.test/**', async (route) => {
        const url = new URL(route.request().url());
        if (url.pathname.startsWith('/assets/')) {
            await route.fulfill({
                path: path.resolve('src/web/assets/frontend/dist', url.pathname.slice('/assets/'.length)),
                contentType: 'application/javascript; charset=utf-8',
            });
            return;
        }
        if (url.pathname === '/check-form') {
            await check(route);
            return;
        }
        if (url.pathname === '/submit') {
            submitted();
            await route.fulfill({ json: {
                success: true, submitAction: 'submit', outcome: 'completed',
                completion: { behavior: 'message', message: 'Booking saved.' },
            } });
            return;
        }
        const form = (id: string) => `
            <form id="${id}" data-formie data-formie-compatibility="${compatibility}"
                action="/submit" method="post" data-formie-submit-method="ajax"
                data-formie-submit-delay="0" data-formie-validation-on-submit="true"
                data-formie-completion-behavior="message" data-formie-success-message-position="top-form"
                data-formie-modules='{"contractVersion":2,"surface":"server-rendered","entries":[]}'>
                <div data-formie-form-messages-top></div>
                <div data-formie-form-body><label>Booking Code <input name="fields[bookingCode]" value="BOOKING123" required></label></div>
                <div data-formie-form-footer><button type="submit" data-formie-action="submit">Send</button></div>
            </form>`;
        await route.fulfill({ contentType: 'text/html; charset=utf-8', body: `
            <!doctype html><html><head><meta charset="utf-8"></head><body>
            ${form('booking-form')}${form('other-form')}
            <script type="module" data-formie-startup src="/assets/js/formie.js"></script>
            </body></html>`,
        });
    });
    await page.goto('https://mount-instance.example.test/');
    await expect.poll(() => page.evaluate(() => window.mountInstanceState.mounts.length)).toBe(2);
}

for (const compatibility of [false, true]) {
    test(`automatic mount exposes an instance for awaited checks with compatibility ${compatibility}`, async ({ page }) => {
        let attempts = 0;
        let submissions = 0;
        let releaseCheck: () => void = () => {};
        const pendingCheck = new Promise<void>((resolve) => { releaseCheck = resolve; });
        await mountFixture(page, compatibility, async (route) => {
            attempts++;
            if (attempts === 1) {
                await pendingCheck;
                await route.fulfill({ json: { valid: false } });
            } else if (attempts === 2) {
                await route.fulfill({ status: 500, body: 'Unavailable' });
            } else {
                await route.fulfill({ json: { valid: true } });
            }
        }, () => { submissions++; });

        expect(await page.evaluate(() => {
            const { mounts, legacyReady } = window.mountInstanceState;
            return {
                ids: mounts.map(({ id }) => id).sort(),
                correctInstances: mounts.every(({ id, instance, root }) => instance?.id === id && instance.root === root),
                distinctInstances: mounts[0].instance !== mounts[1].instance,
                legacyReady,
            };
        })).toEqual({
            ids: ['booking-form', 'other-form'], correctInstances: true,
            distinctInstances: true, legacyReady: compatibility ? 2 : 0,
        });

        const form = page.locator('#booking-form');
        const send = form.getByRole('button', { name: 'Send', exact: true });
        await send.click();
        await expect.poll(() => attempts).toBe(1);
        await expect(form).toHaveAttribute('data-formie-loading', 'true');
        await expect(send).toBeDisabled();
        expect(submissions).toBe(0);
        releaseCheck();

        await expect(form.locator('[data-formie-error]')).toHaveText('Please check your booking code.');
        await expect(form).not.toHaveAttribute('data-formie-loading', 'true');
        await expect(send).toBeEnabled();
        expect(submissions).toBe(0);

        await send.click();
        await expect(form.locator('[data-formie-error]')).toHaveText('Could not check your booking. Please try again.');
        await expect(send).toBeEnabled();
        expect(submissions).toBe(0);

        await send.click();
        await expect(form.locator('[data-formie-success-container]')).toHaveText('Booking saved.');
        await expect(form.locator('[data-formie-error]')).toHaveCount(0);
        await expect(send).toBeEnabled();
        expect(attempts).toBe(3);
        expect(submissions).toBe(1);
        expect(await page.evaluate(() => window.mountInstanceState.checks)).toEqual(['booking-form', 'booking-form', 'booking-form']);
        await expect(page.locator('#other-form [data-formie-error]')).toHaveCount(0);
    });
}

test('automatic remount exposes the new instance and registers one check', async ({ page }) => {
    let checks = 0;
    let submissions = 0;
    await mountFixture(page, false, async (route) => {
        checks++;
        await route.fulfill({ json: { valid: true } });
    }, () => { submissions++; });
    await page.locator('#booking-form').evaluate((form) => form.remove());
    await expect.poll(() => page.evaluate(() => window.mountInstanceState.unmounts)).toEqual(['booking-form']);
    await page.evaluate(() => {
        const replacement = window.mountInstanceState.mounts.find(({ id }) => id === 'booking-form')!.root as HTMLFormElement;
        document.body.append(replacement.cloneNode(true));
    });
    await expect.poll(() => page.evaluate(() => window.mountInstanceState.mounts.length)).toBe(3);
    expect(await page.evaluate(() => {
        const mounts = window.mountInstanceState.mounts.filter(({ id }) => id === 'booking-form');
        return {
            newInstance: !!mounts[1].instance && mounts[0].instance !== mounts[1].instance,
            correctRoot: mounts[1].instance?.root === document.querySelector('#booking-form'),
            legacyReady: window.mountInstanceState.legacyReady,
        };
    })).toEqual({ newInstance: true, correctRoot: true, legacyReady: 0 });
    await page.locator('#booking-form').getByRole('button', { name: 'Send', exact: true }).click();
    await expect(page.locator('#booking-form [data-formie-success-container]')).toHaveText('Booking saved.');
    expect(checks).toBe(1);
    expect(submissions).toBe(1);
});
