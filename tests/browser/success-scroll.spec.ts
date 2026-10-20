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
