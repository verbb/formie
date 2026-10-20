import { test, expect, type Page, type Route } from '@playwright/test';
import path from 'node:path';
import type { FormieFormInstance } from '../../packages/formie-browser/src';

type Submission = { method: string; native: boolean; bookingCode: string | null; action: string | null };

async function openForm(page: Page, options: {
    delay: number | null;
    rejectTestCode?: boolean;
    check?: (route: Route) => Promise<void>;
}) {
    const submissions: Submission[] = [];
    await page.addInitScript(({ rejectTestCode, check }) => {
        document.addEventListener('formie:mount:after', (event) => {
            const { instance } = (event as CustomEvent<{ instance: FormieFormInstance }>).detail;
            instance.root.setAttribute('data-test-mounted', 'true');
            if (rejectTestCode) {
                instance.root.addEventListener('formie:stage:validate:after', (event) => {
                    const { formData, abort } = (event as CustomEvent<{
                        formData: FormData; abort: (message: string) => void;
                    }>).detail;
                    if (formData.get('fields[bookingCode]') === 'TEST') abort('Please enter a valid booking code.');
                });
            }
            if (check) {
                instance.on('formie:stage:validate:after', async () => {
                    await fetch('/check');
                });
            }
        });
    }, { rejectTestCode: options.rejectTestCode === true, check: !!options.check });

    await page.route('https://submit-delay.example.test/**', async (route) => {
        const url = new URL(route.request().url());
        if (url.pathname.startsWith('/assets/')) {
            await route.fulfill({
                path: path.resolve('src/web/assets/frontend/dist', url.pathname.slice('/assets/'.length)),
                contentType: 'application/javascript; charset=utf-8',
            });
            return;
        }
        if (url.pathname === '/check' && options.check) {
            await options.check(route);
            return;
        }
        if (url.pathname === '/submit') {
            const request = route.request();
            const values = new URLSearchParams(request.postData() || '');
            submissions.push({
                method: request.method(), native: request.isNavigationRequest(),
                bookingCode: values.get('fields[bookingCode]'), action: values.get('submitAction'),
            });
            await route.fulfill({ contentType: 'text/html; charset=utf-8', body: '<h1>Booking saved.</h1>' });
            return;
        }
        await route.fulfill({ contentType: 'text/html; charset=utf-8', body: `
            <!doctype html><html><head><meta charset="utf-8"></head><body>
            <form id="booking-form" data-formie data-formie-compatibility="false"
                action="/submit" method="post" data-formie-submit-method="reload"
                ${options.delay === null ? '' : `data-formie-submit-delay="${options.delay}"`}
                data-formie-validation-on-submit="true"
                data-formie-modules='{"contractVersion":2,"surface":"server-rendered","entries":[]}'>
                <div data-formie-form-messages-top></div>
                <div data-formie-form-body><label>Booking Code <input name="fields[bookingCode]" value="BOOKING123" required></label></div>
                <input type="hidden" name="submitAction" value="submit">
                <div data-formie-form-footer><button type="submit" data-formie-action="submit">Send</button></div>
            </form>
            <script type="module" data-formie-startup src="/assets/js/formie.js"></script>
            </body></html>`,
        });
    });
    await page.goto('https://submit-delay.example.test/');
    await expect(page.locator('form')).toHaveAttribute('data-test-mounted', 'true');
    return submissions;
}

async function expectNativeSubmission(page: Page, submissions: Submission[]) {
    await expect(page.getByRole('heading', { name: 'Booking saved.' })).toBeVisible();
    expect(submissions).toEqual([{ method: 'POST', native: true, bookingCode: 'BOOKING123', action: 'submit' }]);
}

for (const delay of [0, -20, 1, null]) {
    test(`native click submits once with delay ${delay ?? 'default'}`, async ({ page }) => {
        const submissions = await openForm(page, { delay });
        await page.getByRole('button', { name: 'Send', exact: true }).click();
        await expectNativeSubmission(page, submissions);
    });
}

test('zero-delay native submission works through Enter', async ({ page }) => {
    const submissions = await openForm(page, { delay: 0 });
    await page.getByRole('textbox', { name: 'Booking Code' }).press('Enter');
    await expectNativeSubmission(page, submissions);
});

test('zero-delay native submission works through requestSubmit', async ({ page }) => {
    const submissions = await openForm(page, { delay: 0 });
    await page.locator('form').evaluate((form: HTMLFormElement) => form.requestSubmit());
    await expectNativeSubmission(page, submissions);
});

test('zero-delay native submission can reject a synchronous check and retry', async ({ page }) => {
    const submissions = await openForm(page, { delay: 0, rejectTestCode: true });
    const input = page.getByRole('textbox', { name: 'Booking Code' });
    const send = page.getByRole('button', { name: 'Send', exact: true });
    await input.fill('TEST');
    await send.click();
    await expect(page.locator('[data-formie-error]')).toHaveText('Please enter a valid booking code.');
    await expect(page.locator('form')).not.toHaveAttribute('data-formie-loading', 'true');
    await expect(send).toBeEnabled();
    expect(submissions).toHaveLength(0);
    await input.fill('BOOKING123');
    await send.click();
    await expectNativeSubmission(page, submissions);
});

test('zero-delay native submission still awaits asynchronous checks', async ({ page }) => {
    let checking = false;
    let releaseCheck: () => void = () => {};
    const gate = new Promise<void>((resolve) => { releaseCheck = resolve; });
    const submissions = await openForm(page, { delay: 0, check: async (route) => {
        checking = true;
        await gate;
        await route.fulfill({ json: { valid: true } });
    } });
    const send = page.getByRole('button', { name: 'Send', exact: true });
    await send.click();
    await expect.poll(() => checking).toBe(true);
    await expect(send).toBeDisabled();
    await expect(page.locator('form')).toHaveAttribute('data-formie-loading', 'true');
    expect(submissions).toHaveLength(0);
    releaseCheck();
    await expectNativeSubmission(page, submissions);
});
