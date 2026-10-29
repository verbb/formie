import { test, expect } from '@playwright/test';
import path from 'node:path';

const origin = 'https://action-endpoints.example.test';

for (const build of ['browser', 'plugin']) {
    for (const source of ['form', 'wrapper', 'remote']) {
        for (const routing of ['default', 'custom', 'query']) {
            test(`${build}/${source}/${routing}: preserves server browser endpoints`, async ({
                page,
            }) => {
                const requests: URL[] = [];
                const trigger =
                    routing === 'default' ? 'actions' : 'custom-actions';
                const endpoint = (action: string) =>
                    routing === 'query'
                        ? `${origin}/craft/index.php?p=${encodeURIComponent(`${trigger}/formie/${action}`)}&custom=keep`
                        : `${origin}/craft/${trigger}/formie/${action}?custom=keep`;
                // Craft can emit root-relative URLs when path-based routing is enabled.
                const renderedEndpoint = (action: string) =>
                    routing === 'query'
                        ? endpoint(action)
                        : endpoint(action).replace(origin, '');
                const actionFor = (url: URL) =>
                    (url.searchParams.get('p') || url.pathname).split(
                        '/formie/',
                    )[1];
                const form = `<form id="contact" ${source === 'form' ? 'data-formie' : ''} data-formie-handle="contact"
                    ${source === 'remote' ? '' : `action="${renderedEndpoint('server/submissions/submit')}"`} method="post"
                    data-formie-submit-endpoint="${renderedEndpoint('server/submissions/submit')}"
                    data-formie-submit-method="ajax" data-formie-submit-delay="0" data-formie-static-cache="true"
                    data-formie-automatic-submission-state="false"
                    data-formie-refresh-tokens-endpoint="${renderedEndpoint('server/forms/refresh-tokens')}"
                    data-formie-clear-submission-endpoint="${renderedEndpoint('server/submissions/clear-submission')}"
                    data-formie-completion-behavior="message" data-formie-success-message-position="top-form"
                    data-formie-modules='{"contractVersion":2,"surface":"server-rendered","entries":[]}'>
                    <input type="hidden" name="handle" value="contact">
                    <input type="hidden" name="siteId" value="2">
                    <input type="hidden" name="renderId" value="initial-render">
                    <input type="hidden" name="requestToken" value="initial-request">
                    <div data-formie-form-messages-top></div>
                    <a data-formie-tab-link data-formie-page-id="second" href="${renderedEndpoint('server/submissions/set-page')}&pageId=second">Next page</a>
                    <label>Name<input name="fields[name]" value="Visitor"></label>
                    <input type="file" data-formie-file-upload-hydrate-endpoint="${renderedEndpoint('file-upload/hydrate')}">
                    <button type="submit" data-formie-action="submit">Send</button>
                </form>`;
                const shell = `<div id="host" data-formie data-formie-handle="contact" data-formie-auto-visible="false" data-formie-static-cache="true"
                    data-formie-endpoint="${endpoint('server/forms/render')}">${source === 'remote' ? '' : form}</div>`;

                await page.route(`${origin}/**`, async (route) => {
                    const url = new URL(route.request().url());
                    const asset = url.pathname.startsWith('/browser/')
                        ? 'packages/formie-browser/dist'
                        : url.pathname.startsWith('/assets/')
                          ? 'src/web/assets/frontend/dist'
                          : null;
                    if (asset) {
                        await route.fulfill({
                            path: path.resolve(
                                asset,
                                url.pathname.replace(
                                    /^\/(browser|assets)\//,
                                    '',
                                ),
                            ),
                            contentType: 'application/javascript',
                        });
                        return;
                    }
                    if (url.pathname === '/es/') {
                        const script =
                            build === 'browser'
                                ? `<script type="module">import { createFormieClient } from '/browser/index.js'; window.endpointClient = createFormieClient(); await window.endpointClient.scan();</script>`
                                : '<script type="module" data-formie-startup src="/assets/js/formie.js"></script>';
                        await route.fulfill({
                            contentType: 'text/html',
                            body: `<!doctype html><html><body>${source === 'form' ? form : shell}
                            <script>window.endpointMounted = false; document.addEventListener('formie:mount:after', () => window.endpointMounted = true);</script>${script}</body></html>`,
                        });
                        return;
                    }
                    requests.push(url);
                    const action = actionFor(url);
                    if (!action || !url.href.startsWith(endpoint(action))) {
                        await route.fulfill({
                            status: 404,
                            json: { error: 'Wrong action URL' },
                        });
                    } else if (action === 'server/forms/render') {
                        await route.fulfill({ json: { html: form } });
                    } else if (action === 'server/forms/refresh-tokens') {
                        await route.fulfill({
                            json: {
                                refreshTokens: {
                                    requestToken: 'fresh-request',
                                    renderId: 'fresh-render',
                                },
                            },
                        });
                    } else if (action === 'server/submissions/submit') {
                        await route.fulfill({
                            json: {
                                success: true,
                                submitAction: 'submit',
                                outcome: 'completed',
                                completion: {
                                    behavior: 'message',
                                    message: 'Saved.',
                                },
                            },
                        });
                    } else {
                        await route.fulfill({
                            json: { success: true, pageId: 'second' },
                        });
                    }
                });

                await page.goto(`${origin}/es/`);
                await expect
                    .poll(() =>
                        page.evaluate(
                            () =>
                                (
                                    window as unknown as {
                                        endpointMounted: boolean;
                                    }
                                ).endpointMounted,
                        ),
                    )
                    .toBe(true);
                const mountedEndpoint =
                    source === 'form' ? renderedEndpoint : endpoint;
                await expect(page.locator('form')).toHaveAttribute(
                    'action',
                    mountedEndpoint('server/submissions/submit'),
                );
                await expect(page.locator('input[type=file]')).toHaveAttribute(
                    'data-formie-file-upload-hydrate-endpoint',
                    mountedEndpoint('file-upload/hydrate'),
                );
                await page.getByRole('link', { name: 'Next page' }).click();
                await expect
                    .poll(() =>
                        requests.some(
                            (url) =>
                                actionFor(url) ===
                                'server/submissions/set-page',
                        ),
                    )
                    .toBe(true);

                if (build === 'browser') {
                    await page.evaluate(async () => {
                        const client = (
                            window as unknown as {
                                endpointClient: import('@verbb/formie-browser').FormieClient;
                            }
                        ).endpointClient;
                        await client.refreshForCache(
                            document.querySelector('[data-formie]')!,
                        );
                    });
                }

                await page
                    .getByRole('button', { name: 'Send', exact: true })
                    .click();
                await expect(
                    page.locator('[data-formie-success-container]'),
                ).toHaveText('Saved.');
                await expect(page.locator('form')).not.toHaveAttribute(
                    'data-formie-loading',
                    'true',
                );
                const refreshes = requests.filter(
                    (url) => actionFor(url) === 'server/forms/refresh-tokens',
                );
                expect(refreshes).toHaveLength(build === 'browser' ? 3 : 2);
                expect(
                    refreshes.every(
                        (url) => url.searchParams.get('siteId') === '2',
                    ),
                ).toBe(true);
                expect(refreshes[0].searchParams.get('requestToken')).toBe(
                    'initial-request',
                );

                await page.evaluate(() =>
                    window.dispatchEvent(new PageTransitionEvent('pagehide')),
                );
                await expect
                    .poll(() =>
                        requests.some(
                            (url) =>
                                actionFor(url) ===
                                'server/submissions/clear-submission',
                        ),
                    )
                    .toBe(true);
                expect(
                    requests.every((url) =>
                        url.href.startsWith(endpoint(actionFor(url))),
                    ),
                ).toBe(true);
            });
        }
    }
}
