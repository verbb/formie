import { test, expect, type Page } from '@playwright/test';
import path from 'node:path';
import type { BeforeSubmitContext, FormieClient, FormieFormInstance, FormSubmitResult } from '../../packages/formie-browser/src';

const stages = ['prepare', 'validate', 'challenge', 'payment', 'send', 'result'];
const submitEvents = ['formie:submit:before', 'formie:submit:after', 'formie:submit:final:before', 'formie:submit:final:after'];
const stageEvents = stages.flatMap((stage) => [`formie:stage:${stage}:before`, `formie:stage:${stage}:after`]);
const lifecycleEvents = [...submitEvents, ...stageEvents, 'formie:submit:result', 'formie:mount:after',
    'formie:unmount:before', 'formie:unmount:after', 'formie:page:navigate', 'formie:page:navigate:after',
    'formie:page:navigate:error', 'formie:refresh-tokens:after', 'formie:refresh-tokens:refreshed',
    'formie:state:reset', 'formie:client-event', 'formie:validator:ready', 'formie:theme:applied'];

type Observation = { name: string; owner: string; instance?: FormieFormInstance; payload: Record<string, unknown> };
declare global {
    interface Window {
        lifecycleState: {
            mounts: Record<string, FormieFormInstance>;
            dom: Observation[];
            bus: Observation[];
            legacyReady: number;
        };
    }
}

async function openForms(page: Page, options: { compatibility?: boolean; pages?: boolean; failure?: boolean } = {}) {
    await page.addInitScript(({ names, busNames }) => {
        const state: Window['lifecycleState'] = { mounts: {}, dom: [], bus: [], legacyReady: 0 };
        window.lifecycleState = state;
        const recorded = new WeakSet<Event>();
        const record = (event: Event) => {
            if (recorded.has(event)) return;
            recorded.add(event);
            const payload = ((event as CustomEvent).detail ?? {}) as Record<string, unknown>;
            const root = (event.target as Element).closest('[data-formie]') as HTMLElement;
            state.dom.push({ name: event.type, owner: root.id, instance: payload.instance as FormieFormInstance | undefined, payload });
        };
        for (const name of names) document.addEventListener(name, record);
        document.addEventListener('onFormieReady', () => state.legacyReady++);
        document.addEventListener('formie:mount:after', (event) => {
            const { instance } = (event as CustomEvent<{ instance: FormieFormInstance }>).detail;
            const owner = instance.root.id;
            state.mounts[owner] = instance;
            // Detached roots cannot bubble their teardown events to document.
            instance.root.addEventListener('formie:unmount:before', record);
            instance.root.addEventListener('formie:unmount:after', record);
            for (const name of busNames) instance.on(name, (detail) => {
                const payload = detail as Record<string, unknown>;
                state.bus.push({ name, owner, instance: payload.instance as FormieFormInstance | undefined, payload });
            });
        });
    }, { names: lifecycleEvents, busNames: [...submitEvents, ...stageEvents] });

    await page.route('https://lifecycle-instance.example.test/**', async (route) => {
        const url = new URL(route.request().url());
        const assets = url.pathname.startsWith('/assets/') ? 'src/web/assets/frontend/dist'
            : url.pathname.startsWith('/browser/') ? 'packages/formie-browser/dist' : null;
        if (assets) {
            await route.fulfill({ path: path.resolve(assets, url.pathname.replace(/^\/(assets|browser)\//, '')), contentType: 'application/javascript; charset=utf-8' });
        } else if (url.pathname === '/refresh' || url.pathname.endsWith('/refresh-tokens')) {
            await route.fulfill({ json: { refreshTokens: { requestToken: 'fresh-request', renderId: 'fresh-render', csrf: { param: 'csrf', token: 'fresh-csrf' } } } });
        } else if (url.pathname === '/submit') {
            await route.fulfill({ json: options.failure ? { success: false, errors: { form: ['Server rejected the booking.'] } } : {
                success: true, submitAction: 'submit', outcome: 'completed',
                completion: { behavior: 'message', message: 'Booking saved.' },
                clientEvents: [{ event: 'bookingSaved', payload: { event: 'bookingSaved', booking: 'CONFIRMED' } }],
            } });
        } else if (url.pathname === '/page') {
            if (url.searchParams.has('fail')) await route.abort('failed');
            else await route.fulfill({ json: { success: true, pageId: 'second' } });
        } else {
            const form = (name: string) => `<div id="${name}-root" data-formie data-formie-handle="${name}">
                <form id="${name}" action="/submit" method="post" data-formie-compatibility="${options.compatibility ?? false}"
                    data-formie-submit-method="ajax" data-formie-submit-delay="0" data-formie-validation-on-submit="true"
                    data-formie-static-cache="true" data-formie-refresh-tokens-endpoint="/refresh"
                    data-formie-completion-behavior="message" data-formie-success-message-position="top-form"
                    data-formie-theme-classes='{"fieldErrors":["custom-errors"]}'
                    data-formie-modules='{"contractVersion":2,"surface":"server-rendered","entries":[]}'>
                    <input type="hidden" name="renderId" value="initial-render"><input type="hidden" name="requestToken" value="initial-request">
                    <div data-formie-form-messages-top></div>
                    ${options.pages ? '<a data-formie-tab-link data-formie-page-id="second" href="/page">Next page</a><a data-formie-tab-link data-formie-page-id="first" href="/page?fail=1">Failed page</a><section data-formie-page data-formie-page-id="first">' : ''}
                    <label>Booking Code <input name="fields[bookingCode]" value="BOOKING123" required></label>
                    ${options.pages ? '</section><section data-formie-page data-formie-page-id="second" data-formie-page-hidden>Second page</section>' : ''}
                    <button type="submit" data-formie-action="submit">Send</button>
                </form></div>`;
            await route.fulfill({ contentType: 'text/html; charset=utf-8', body: `<!doctype html><html><head><meta charset="utf-8"></head><body>
                <style>[data-formie-page-hidden]{display:none}</style>${form('first')}${form('other')}
                <script type="module" data-formie-startup src="/assets/js/formie.js"></script></body></html>` });
        }
    });
    await page.goto('https://lifecycle-instance.example.test/');
    await expect.poll(() => page.evaluate(() => Object.keys(window.lifecycleState.mounts).length)).toBe(2);
}

async function expectContexts(page: Page, names: string[], channel: 'dom' | 'bus' = 'dom') {
    expect(await page.evaluate(({ names, channel }) => names.map((name) => {
        const events = window.lifecycleState[channel].filter((item) => item.name === name);
        return { name, present: events.length > 0, correct: events.every((item) => item.instance === window.lifecycleState.mounts[item.owner]) };
    }), { names, channel })).toEqual(names.map((name) => ({ name, present: true, correct: true })));
}

for (const compatibility of [false, true]) {
    test(`form lifecycle contexts share the mounted instance with compatibility ${compatibility}`, async ({ page }) => {
        await openForms(page, { compatibility });
        await page.locator('#first-root').getByRole('button', { name: 'Send', exact: true }).click();
        await expect(page.locator('#first [data-formie-success-container]')).toHaveText('Booking saved.');
        await expect(page.locator('#first')).not.toHaveAttribute('data-formie-loading', 'true');
        await expectContexts(page, [...submitEvents, ...stageEvents, 'formie:submit:result', 'formie:state:reset',
            'formie:refresh-tokens:after', 'formie:refresh-tokens:refreshed', 'formie:client-event']);
        await expectContexts(page, [...submitEvents, ...stageEvents], 'bus');
        expect(await page.evaluate(() => ({
            legacy: window.lifecycleState.legacyReady,
            early: window.lifecycleState.dom.filter((item) => ['formie:validator:ready', 'formie:theme:applied'].includes(item.name)).every((item) => item.instance === undefined),
            analytics: (window as Window & { dataLayer?: unknown[] }).dataLayer,
        }))).toEqual({ legacy: compatibility ? 2 : 0, early: true, analytics: [{ event: 'bookingSaved', booking: 'CONFIRMED' }] });
        await page.locator('#other-root').getByRole('button', { name: 'Send', exact: true }).click();
        await expect(page.locator('#other [data-formie-success-container]')).toHaveText('Booking saved.');
        await expectContexts(page, ['formie:submit:result', 'formie:state:reset']);
    });
}

test('aborted and failed results preserve their instance and mutable payload', async ({ page }) => {
    await openForms(page, { failure: true });
    await page.evaluate(() => {
        const instance = window.lifecycleState.mounts['first-root'];
        const stop = instance.on('formie:stage:validate:before', (payload) => {
            const context = payload as BeforeSubmitContext & { instance?: FormieFormInstance };
            if (context.instance !== instance) throw new Error('Wrong instance');
            context.abort('Custom rejection.');
            stop();
        });
        instance.on('formie:submit:after', (payload) => {
            const result = payload as FormSubmitResult;
            if (result.code !== 'ABORTED') result.formErrors = ['Changed by listener.'];
        });
    });
    const send = page.locator('#first-root').getByRole('button', { name: 'Send', exact: true });
    await send.click();
    await expect(page.locator('#first [data-formie-error]')).toHaveText('Custom rejection.');
    await expect(send).toBeEnabled();
    await expectContexts(page, ['formie:stage:validate:before', 'formie:submit:after', 'formie:submit:result']);
    await send.click();
    await expect(page.locator('#first [data-formie-error]')).toHaveText('Changed by listener.');
    await expectContexts(page, ['formie:submit:result']);
});

test('page navigation success and failure expose the owning instance', async ({ page }) => {
    await openForms(page, { pages: true });
    await page.locator('#first-root').getByRole('link', { name: 'Next page', exact: true }).click();
    await expect(page.locator('#first [data-formie-page-id="second"][data-formie-page]')).toBeVisible();
    await expectContexts(page, ['formie:page:navigate', 'formie:page:navigate:after']);
    await page.locator('#first-root').getByRole('link', { name: 'Failed page', exact: true }).click();
    await expect.poll(() => page.evaluate(() => window.lifecycleState.dom.filter((item) => item.name === 'formie:page:navigate:error').length)).toBe(1);
    await expectContexts(page, ['formie:page:navigate:error']);
});

test('detached-root teardown keeps the old instance and remount uses a new one', async ({ page }) => {
    await openForms(page);
    await page.locator('#first-root').evaluate((root) => root.remove());
    await expect.poll(() => page.evaluate(() => window.lifecycleState.dom.filter((item) => item.name === 'formie:unmount:after').length)).toBe(1);
    await expectContexts(page, ['formie:unmount:before', 'formie:unmount:after']);
    expect(await page.evaluate(async () => {
        const old = window.lifecycleState.mounts['first-root'];
        const start = window.lifecycleState.dom.length;
        document.body.append(old.root);
        await new Promise<void>((resolve) => document.addEventListener('formie:mount:after', () => resolve(), { once: true }));
        const earlyEvents = window.lifecycleState.dom.slice(start).filter((item) => ['formie:validator:ready', 'formie:theme:applied'].includes(item.name));
        return {
            newInstance: old !== window.lifecycleState.mounts['first-root'],
            oldContextCleared: earlyEvents.length > 0 && earlyEvents.every((item) => item.instance === undefined),
        };
    })).toEqual({ newInstance: true, oldContextCleared: true });
    await page.locator('#first-root').getByRole('button', { name: 'Send', exact: true }).click();
    await expect(page.locator('#first [data-formie-success-container]')).toHaveText('Booking saved.');
    await expectContexts(page, ['formie:submit:result']);
});

test('theme updates and explicit token refresh use the mounted wrapper instance', async ({ page }) => {
    await openForms(page);
    await page.evaluate(async () => {
        const moduleUrl = '/browser/index.js';
        const { createFormieClient } = await import(moduleUrl) as { createFormieClient: () => FormieClient };
        const root = document.createElement('div');
        root.id = 'manual-root';
        root.setAttribute('data-formie', '');
        root.setAttribute('data-formie-init', 'false');
        root.innerHTML = '<form id="manual"><input name="renderId" value="manual-render"><input name="requestToken" value="manual-request"></form>';
        document.body.append(root);
        const client = createFormieClient();
        const instance = await client.mount(root, { mode: 'server-rendered', formHandle: 'manual', refreshTokens: false });
        await client.update(root, { payload: { theme: { fieldErrors: ['updated-errors'] } } });
        await client.refreshForCache(root);
        if (client.getInstance(root) !== instance) throw new Error('Instance changed');
    });
    expect(await page.evaluate(() => {
        const events = window.lifecycleState.dom.filter((item) => item.owner === 'manual-root');
        return ['formie:theme:applied', 'formie:refresh-tokens:after'].map((name) => {
            const matches = events.filter((item) => item.name === name);
            return { name, count: matches.length, correct: matches.every((item) => item.instance === window.lifecycleState.mounts['manual-root']) };
        });
    })).toEqual(['formie:theme:applied', 'formie:refresh-tokens:after'].map((name) => ({ name, count: 1, correct: true })));
});

test('failed cache refresh does not expose an abandoned instance during mount retry', async ({ page }) => {
    await openForms(page);
    await page.route('**/failed-refresh/**', (route) => route.abort('failed'));
    expect(await page.evaluate(async () => {
        const moduleUrl = '/browser/index.js';
        const { createFormieClient } = await import(moduleUrl) as { createFormieClient: () => FormieClient };
        const root = document.createElement('div');
        root.id = 'failed-root';
        root.setAttribute('data-formie', '');
        root.setAttribute('data-formie-init', 'false');
        root.setAttribute('data-formie-refresh-tokens-endpoint', '/failed-refresh/actions/formie/server/forms/refresh-tokens');
        root.innerHTML = `<form data-formie-theme-classes='{"fieldErrors":["custom-errors"]}'></form>`;
        document.body.append(root);
        const client = createFormieClient();
        let failed = false;
        try {
            await client.mount(root, { mode: 'server-rendered', formHandle: 'failed', staticCache: true });
        } catch {
            failed = true;
        }
        const start = window.lifecycleState.dom.length;
        const instance = await client.mount(root, { mode: 'server-rendered', staticCache: false });
        const early = window.lifecycleState.dom.slice(start).filter((item) => ['formie:validator:ready', 'formie:theme:applied'].includes(item.name));
        return {
            failed,
            clearedContext: early.length > 0 && early.every((item) => item.instance === undefined),
            mounted: window.lifecycleState.mounts['failed-root'] === instance,
        };
    })).toEqual({ failed: true, clearedContext: true, mounted: true });
});
