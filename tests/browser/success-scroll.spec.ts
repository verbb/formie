import { test, expect, type Page } from '@playwright/test';
import { build } from 'esbuild';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import type { createFormieClient } from '../../packages/formie-browser/src';

declare global {
    interface Window {
        successScrollBoundary: {
            createFormieClient: typeof createFormieClient;
        };
        successScrollCalls: Element[];
    }
}

let script: string;

test.beforeAll(async () => {
    const imports = JSON.parse(
        readFileSync('packages/formie-browser/package.json', 'utf8'),
    ).imports;
    const result = await build({
        stdin: {
            contents: `export { createFormieClient } from './packages/formie-browser/src/js/core/create-formie-client';`,
            resolveDir: process.cwd(),
        },
        bundle: true,
        format: 'iife',
        globalName: 'successScrollBoundary',
        loader: {
            '.css': 'text',
            '.svg': 'text',
            '.png': 'dataurl',
            '.webp': 'dataurl',
        },
        alias: Object.fromEntries(
            Object.entries(imports).map(([key, value]) => [
                key.replace('/*', ''),
                path.resolve(
                    'packages/formie-browser',
                    (typeof value === 'string'
                        ? value
                        : (value as { default: string }).default
                    ).replace('/*', ''),
                ),
            ]),
        ),
        write: false,
    });
    script = result.outputFiles[0].text;
});

async function mountForm(
    page: Page,
    options: {
        scroll: string | null;
        hide: boolean;
        position?: string;
        error?: boolean;
    },
) {
    await page.setViewportSize({ width: 1000, height: 720 });
    await page.route('**/success-scroll-fixture', (route) =>
        route.fulfill({
            contentType: 'text/html',
            body: `
            <style>
                body { margin: 0; }
                header { height: 900px; }
                [data-formie-form-body] { height: 1000px; }
                footer { height: 3000px; }
                [hidden] { display: none !important; }
            </style>
            <header>Content above form</header>
            <form data-formie data-formie-submit-method="ajax" method="post" action="/success-scroll-submit"
                data-formie-completion-behavior="message" data-formie-validation-on-submit="true"
                data-formie-success-message-position="${options.position || 'top-form'}"
                data-formie-hide-form-after-submit="${options.hide}"
                ${options.scroll === null ? '' : `data-formie-scroll-to-top="${options.scroll}"`}
                data-formie-modules='{"contractVersion":2,"surface":"server-rendered","entries":[]}'>
                <div data-formie-form-messages-top></div>
                <div data-formie-form-body><label>Name <input name="fields[name]" value="Visitor"></label></div>
                <div data-formie-form-footer><button type="submit">Submit</button></div>
                <div data-formie-form-messages-bottom></div>
            </form>
            <footer>Content below form</footer>
        `,
        }),
    );
    await page.route('**/success-scroll-submit', (route) =>
        route.fulfill({
            json: options.error
                ? { success: false, errors: { form: ['Submission failed.'] } }
                : {
                      success: true,
                      submitAction: 'submit',
                      outcome: 'completed',
                      completion: {
                          behavior: 'message',
                          message: 'Submission saved.',
                      },
                  },
        }),
    );
    await page.goto('/success-scroll-fixture');
    await page.addScriptTag({ content: script });
    await page.evaluate(async () => {
        window.successScrollCalls = [];
        const scrollIntoView = Element.prototype.scrollIntoView;
        Element.prototype.scrollIntoView = function (...args) {
            window.successScrollCalls.push(this);
            return scrollIntoView.apply(this, args);
        };
        await window.successScrollBoundary
            .createFormieClient()
            .mount(document.querySelector('form')!, {
                mode: 'server-rendered',
                refreshTokens: false,
            });
    });
    await page
        .getByRole('button', { name: 'Submit', exact: true })
        .scrollIntoViewIfNeeded();
}

for (const position of ['top-form', 'bottom-form']) {
    for (const hide of [true, false]) {
        test(`AJAX success scrolls to the ${position} message with hide form ${hide}`, async ({
            page,
        }) => {
            await mountForm(page, { scroll: '', hide, position });
            await page
                .getByRole('button', { name: 'Submit', exact: true })
                .click();
            const message = page.locator('[data-formie-success-container]');
            await expect(message).toHaveText('Submission saved.');
            await expect(message).toBeInViewport();
            await expect(page.locator('[data-formie-form-body]')).toBeVisible({
                visible: !hide,
            });
            expect(
                await page.evaluate(() => window.successScrollCalls.length),
            ).toBe(1);
        });
    }
}

for (const scroll of ['true', '1']) {
    test(`explicit scroll value ${scroll} brings success into view`, async ({
        page,
    }) => {
        await mountForm(page, { scroll, hide: true });
        await page.getByRole('button', { name: 'Submit', exact: true }).click();
        await expect(
            page.locator('[data-formie-success-container]'),
        ).toBeInViewport();
    });
}

for (const scroll of [null, 'false', '0']) {
    test(`scroll value ${scroll} leaves the viewport unchanged`, async ({
        page,
    }) => {
        await mountForm(page, { scroll, hide: true });
        const before = await page.evaluate(() => window.scrollY);
        await page.getByRole('button', { name: 'Submit', exact: true }).click();
        await expect(
            page.locator('[data-formie-success-container]'),
        ).toHaveText('Submission saved.');
        await expect(page.locator('[data-formie-form-body]')).toBeHidden();
        expect(await page.evaluate(() => window.scrollY)).toBe(before);
        expect(
            await page.evaluate(() => window.successScrollCalls.length),
        ).toBe(0);
        await expect(
            page.locator('[data-formie-success-container]'),
        ).not.toBeInViewport();
    });
}

for (const scroll of ['', null]) {
    test(`error feedback remains visible with scroll value ${scroll}`, async ({
        page,
    }) => {
        await mountForm(page, { scroll, hide: true, error: true });
        await page.getByRole('button', { name: 'Submit', exact: true }).click();
        await expect(page.locator('[data-formie-error]')).toBeInViewport();
        await expect(page.locator('[data-formie-form-body]')).toBeVisible();
        expect(
            await page.evaluate(() => window.successScrollCalls.length),
        ).toBe(1);
    });
}

async function mountMultiPageForm(page: Page, scroll: string | null) {
    await page.setViewportSize({ width: 1000, height: 720 });
    await page.route('**/page-scroll-fixture', (route) =>
        route.fulfill({
            contentType: 'text/html',
            body: `
            <style>
                body { margin: 0; }
                header { height: 900px; }
                [data-formie-page] { min-height: 1500px; }
                [data-formie-page-hidden] { display: none !important; }
                footer { height: 3000px; }
            </style>
            <header>Content above form</header>
            <form data-formie data-formie-submit-method="ajax" method="post" action="/page-scroll-submit"
                data-formie-completion-behavior="message" data-formie-validation-on-submit="true"
                data-formie-success-message-position="top-form"
                ${scroll === null ? '' : `data-formie-scroll-to-top="${scroll}"`}
                data-formie-modules='{"contractVersion":2,"surface":"server-rendered","entries":[]}'>
                <div data-formie-form-messages-top></div>
                <section data-formie-page data-formie-page-id="1">
                    <label>First <input name="fields[first]" value="Visitor"></label>
                    <button type="submit" data-formie-action="submit">Next</button>
                </section>
                <section data-formie-page data-formie-page-id="2" data-formie-page-hidden>
                    <label>Second <input name="fields[second]"></label>
                    <button type="submit" data-formie-action="submit">Submit</button>
                </section>
            </form>
            <footer>Content below form</footer>
        `,
        }),
    );
    await page.route('**/page-scroll-submit', (route) =>
        route.fulfill({
            json: {
                success: true,
                submitAction: 'submit',
                outcome: 'pageChanged',
                nextPageId: 2,
                totalPages: 2,
                isFinalPage: false,
            },
        }),
    );
    await page.goto('/page-scroll-fixture');
    await page.addScriptTag({ content: script });
    await page.evaluate(async () => {
        await window.successScrollBoundary
            .createFormieClient()
            .mount(document.querySelector('form')!, {
                mode: 'server-rendered',
                refreshTokens: false,
            });
    });
    await page
        .getByRole('button', { name: 'Next', exact: true })
        .scrollIntoViewIfNeeded();
}

for (const scroll of ['', 'true', '1']) {
    test(`AJAX page change returns to the top of the form with scroll value "${scroll}"`, async ({
        page,
    }) => {
        await mountMultiPageForm(page, scroll);
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await expect(page.getByLabel('Second')).toBeVisible();
        await expect(page.getByLabel('Second')).toBeInViewport();
        await expect
            .poll(() => page.evaluate(() => Math.round(document.querySelector('form')!.getBoundingClientRect().top)))
            .toBe(0);
    });
}

for (const scroll of [null, 'false', '0']) {
    test(`AJAX page change keeps the viewport with scroll value ${scroll}`, async ({
        page,
    }) => {
        await mountMultiPageForm(page, scroll);
        const before = await page.evaluate(() => window.scrollY);
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await expect(page.locator('[data-formie-page-id="2"]')).toBeVisible();
        await page.waitForTimeout(600);
        expect(await page.evaluate(() => window.scrollY)).toBe(before);
    });
}

async function mountTabbedForm(page: Page, submitMethod: string) {
    const posts: string[] = [];
    await page.route('**/tab-fixture', (route) =>
        route.fulfill({
            contentType: 'text/html',
            body: `
            <style>[data-formie-page-hidden] { display: none !important; }</style>
            <form data-formie data-formie-submit-method="${submitMethod}" method="post"
                data-formie-completion-behavior="message" data-formie-validation-on-submit="true"
                data-formie-modules='{"contractVersion":2,"surface":"server-rendered","entries":[]}'>
                <div data-formie-form-messages-top></div>
                <input type="hidden" name="handle" value="tabs">
                <div data-formie-page-tabs>
                    <div data-formie-tab data-formie-page-id="1"><a data-formie-tab-link data-formie-page-id="1" href="/tab-set-page?pageId=1">One</a></div>
                    <div data-formie-tab data-formie-page-id="2"><a data-formie-tab-link data-formie-page-id="2" href="/tab-set-page?pageId=2">Two</a></div>
                    <div data-formie-tab data-formie-page-id="3"><a data-formie-tab-link data-formie-page-id="3" href="/tab-set-page?pageId=3">Three</a></div>
                </div>
                <section data-formie-page data-formie-page-id="1"><label>First <input name="fields[first]" value="Visitor"></label></section>
                <section data-formie-page data-formie-page-id="2" data-formie-page-hidden><label>Second <input name="fields[second]"></label></section>
                <section data-formie-page data-formie-page-id="3" data-formie-page-hidden><label>Third <input name="fields[third]"></label></section>
            </form>
        `,
        }),
    );
    await page.route('**/tab-set-page**', (route) => {
        const pageId = new URL(route.request().url()).searchParams.get('pageId');
        posts.push(`${route.request().method()} ${pageId}`);
        const body = route.request().postData() || '';

        return route.fulfill(pageId === '3'
            ? { status: 422, json: { success: false, pageId: '1', errors: { form: ['The requested page is unavailable.'] } } }
            : { json: { success: true, pageId, hasValue: body.includes('Visitor') } });
    });
    await page.goto('/tab-fixture');
    await page.addScriptTag({ content: script });
    await page.evaluate(async () => {
        await window.successScrollBoundary
            .createFormieClient()
            .mount(document.querySelector('form')!, {
                mode: 'server-rendered',
                refreshTokens: false,
            });
    });

    return posts;
}

for (const submitMethod of ['page-reload', 'ajax']) {
    test(`page tabs post the page change on ${submitMethod} forms`, async ({ page }) => {
        const posts = await mountTabbedForm(page, submitMethod);

        await page.getByRole('link', { name: 'Two', exact: true }).click();
        await expect(page.getByLabel('Second')).toBeVisible();
        await expect(page.getByLabel('First')).toBeHidden();
        expect(posts).toEqual(['POST 2']);
        expect(new URL(page.url()).pathname).toBe('/tab-fixture');
    });

    test(`a page tab error clears after the next page change on ${submitMethod} forms`, async ({ page }) => {
        await mountTabbedForm(page, submitMethod);

        await page.getByRole('link', { name: 'Three', exact: true }).click();
        await expect(page.getByText('The requested page is unavailable.')).toBeVisible();
        await page.getByRole('link', { name: 'Two', exact: true }).click();
        await expect(page.getByLabel('Second')).toBeVisible();
        await expect(page.getByText('The requested page is unavailable.')).toHaveCount(0);
    });
}
