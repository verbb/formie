import { test, expect } from '@playwright/test';
import path from 'node:path';

for (const bundle of ['assets', 'browser']) {
    test(`${bundle} applies theme attributes to repeated AJAX error and success messages`, async ({
        page,
    }) => {
        const attributes = {
            messageError: {
                'data-ui-theme': 'error',
                title: 'Quotes " & <tags> café',
                'aria-label': 'Error result',
                style: 'border-color: red;',
                'data-count': '0',
                inert: true,
            },
            messageSuccess: {
                'data-ui-theme': 'success',
                title: 'Quotes " & <tags> café',
                'aria-label': 'Success result',
                style: 'border-color: green;',
                'data-count': '0',
            },
        };
        await page.addInitScript(() =>
            document.addEventListener('formie:mount:after', (event) =>
                (event.target as HTMLElement).setAttribute(
                    'data-test-ready',
                    'true',
                ),
            ),
        );
        let success = false;
        await page.route(
            'https://message-attributes.example.test/**',
            async (route) => {
                const url = new URL(route.request().url());
                if (
                    url.pathname.startsWith('/assets/') ||
                    url.pathname.startsWith('/browser/')
                ) {
                    const root = url.pathname.startsWith('/assets/')
                        ? 'src/web/assets/frontend/dist'
                        : 'packages/formie-browser/dist';
                    await route.fulfill({
                        path: path.resolve(
                            root,
                            url.pathname.replace(/^\/(assets|browser)\//, ''),
                        ),
                        contentType: 'application/javascript',
                    });
                } else if (url.pathname === '/submit') {
                    await route.fulfill({
                        json: success
                            ? {
                                  success: true,
                                  submitAction: 'submit',
                                  outcome: 'completed',
                                  completion: {
                                      behavior: 'message',
                                      message: 'Saved.',
                                  },
                              }
                            : {
                                  success: false,
                                  errors: { form: ['Please try again.'] },
                              },
                    });
                } else {
                    const escape = (value: unknown) =>
                        JSON.stringify(value)
                            .replace(/&/g, '&amp;')
                            .replace(/'/g, '&#39;');
                    const form = (
                        id: string,
                        themed: boolean,
                    ) => `<form id="${id}" data-formie action="/submit" method="post"
                    data-formie-submit-method="ajax" data-formie-submit-delay="0" data-formie-completion-behavior="message"
                    data-formie-success-message-position="top-form" data-formie-error-aria-live="polite"
                    data-formie-message-attributes='${escape(themed ? attributes : {})}'
                    data-formie-theme-classes='{"message":["alert"],"messageError":["alert--error"],"messageSuccess":["alert--success"]}'>
                    <div data-formie-form-messages-top></div><button type="submit" data-formie-action="submit">Send</button></form>`;
                    const script =
                        bundle === 'assets'
                            ? '<script type="module" data-formie-startup src="/assets/js/formie.js"></script>'
                            : '<script type="module">import { createFormieClient } from "/browser/index.js"; const client = createFormieClient(); await client.mount(document.querySelector("#first"), { mode: "server-rendered", refreshTokens: false }); await client.mount(document.querySelector("#other"), { mode: "server-rendered", refreshTokens: false }); window.ready = true;</script>';
                    await route.fulfill({
                        contentType: 'text/html; charset=utf-8',
                        body: `${form('first', true)}${form('other', false)}${script}`,
                    });
                }
            },
        );
        await page.goto('https://message-attributes.example.test/');
        const form = page.locator('#first');
        await expect(form).toHaveAttribute('data-test-ready', 'true');

        for (const outcome of [false, false, true, true, false]) {
            success = outcome;
            await form
                .getByRole('button', { name: 'Send', exact: true })
                .click();
            const state = success ? 'success' : 'error';
            const message = form.locator(`[data-formie-message-${state}]`);
            await expect(message).toHaveCount(1);
            await expect(message).toHaveAttribute('data-ui-theme', state);
            await expect(message).toHaveAttribute(
                'title',
                'Quotes " & <tags> café',
            );
            await expect(message).toHaveAttribute(
                'aria-label',
                success ? 'Success result' : 'Error result',
            );
            await expect(message).toHaveAttribute('data-count', '0');
            await expect(message).toHaveCSS(
                'border-top-color',
                success ? 'rgb(0, 128, 0)' : 'rgb(255, 0, 0)',
            );
            await expect(message).toHaveClass(`alert alert--${state}`);
            await expect(message).toHaveAttribute(
                'role',
                success ? 'status' : 'alert',
            );
            await expect(message).toHaveAttribute('aria-live', 'polite');
            await expect(
                form.locator(
                    `[data-ui-theme="${success ? 'error' : 'success'}"]`,
                ),
            ).toHaveCount(0);
        }

        await page
            .locator('#other')
            .getByRole('button', { name: 'Send', exact: true })
            .click();
        await expect(
            page.locator('#other [data-formie-message-error]'),
        ).toBeVisible();
        await expect(page.locator('#other [data-ui-theme]')).toHaveCount(0);
    });
}
