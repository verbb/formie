import { test, expect, type Page } from '@playwright/test';
import { build } from 'esbuild';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import type { createFormieClient } from '../../packages/formie-browser/src';

// Editing a completed multi-page submission on the front end (`form.setSubmission()`): page buttons, Back and
// page tabs change page in the browser, and only the last page saves, sending every page's answers once.

declare global {
    interface Window {
        revisePagesBoundary: {
            createFormieClient: typeof createFormieClient;
        };
    }
}

let script: string;

test.beforeAll(async () => {
    const imports = JSON.parse(readFileSync('packages/formie-browser/package.json', 'utf8')).imports;
    const result = await build({
        stdin: {
            contents: `export { createFormieClient } from './packages/formie-browser/src/js/core/create-formie-client';`,
            resolveDir: process.cwd(),
        },
        bundle: true,
        format: 'iife',
        globalName: 'revisePagesBoundary',
        loader: { '.css': 'text', '.svg': 'text', '.png': 'dataurl', '.webp': 'dataurl' },
        alias: Object.fromEntries(
            Object.entries(imports).map(([key, value]) => [
                key.replace('/*', ''),
                path.resolve('packages/formie-browser', (typeof value === 'string' ? value : (value as { default: string }).default).replace('/*', '')),
            ]),
        ),
        write: false,
    });
    script = result.outputFiles[0].text;
});

const field = (handle: string, label: string, value: string, required = false) => `
    <div data-formie-field data-formie-field-handle="${handle}"${required ? ` data-formie-validation='[{"type":"required"}]'` : ''}>
        <label data-formie-field-label for="${handle}">${label}</label>
        <input id="${handle}" type="text" name="fields[${handle}]" value="${value}" data-formie-input>
        <div data-formie-field-errors></div>
    </div>`;

type Options = {
    method: 'ajax' | 'page-reload';
    revise?: boolean;
    response?: Record<string, unknown>;
};

async function mountForm(page: Page, options: Options) {
    const posts: Array<{ url: string; body: string }> = [];
    const revise = options.revise ?? true;

    await page.route('**/revise-fixture', (route) => route.fulfill({
        contentType: 'text/html',
        body: `
        <style>[data-formie-page-hidden], [data-formie-conditionally-hidden] { display: none !important; }</style>
        <form data-formie data-formie-submit-method="${options.method}" method="post" enctype="multipart/form-data" action="/revise-submit"
            ${revise ? 'data-formie-revise' : ''}
            data-formie-completion-behavior="message" data-formie-validation-on-submit="true"
            data-formie-modules='{"contractVersion":2,"surface":"server-rendered","entries":[]}'>
            <div data-formie-form-messages-top></div>
            <input type="hidden" name="action" value="formie/submissions/save-submission">
            <input type="hidden" name="handle" value="revise">
            <input type="hidden" name="submitAction" value="submit">
            <input type="hidden" name="pageId" value="1">
            <div data-formie-page-tabs>
                <div data-formie-tab data-formie-page-id="1"><a data-formie-tab-link data-formie-page-id="1" href="/revise-set-page?pageId=1">One</a></div>
                <div data-formie-tab data-formie-page-id="2"><a data-formie-tab-link data-formie-page-id="2" href="/revise-set-page?pageId=2">Two</a></div>
                <div data-formie-tab data-formie-page-id="4"><a data-formie-tab-link data-formie-page-id="4" href="/revise-set-page?pageId=4">Four</a></div>
            </div>
            <section data-formie-page data-formie-page-id="1">
                ${field('first', 'First', 'One', true)}
                <button type="submit" data-formie-action="submit">Next</button>
            </section>
            <section data-formie-page data-formie-page-id="2" data-formie-page-hidden>
                ${field('second', 'Second', 'Two')}
                <button type="submit" data-formie-action="back">Back</button>
                <button type="submit" data-formie-action="submit">Next</button>
            </section>
            <section data-formie-page data-formie-page-id="3" data-formie-page-hidden data-formie-conditionally-hidden>
                ${field('skipped', 'Skipped', 'Hidden page')}
                <button type="submit" data-formie-action="submit">Next</button>
            </section>
            <section data-formie-page data-formie-page-id="4" data-formie-page-hidden>
                ${field('third', 'Third', 'Three')}
                <button type="submit" data-formie-action="back">Back</button>
                <button type="submit" data-formie-action="submit">Save</button>
            </section>
        </form>`,
    }));

    for (const pattern of ['**/revise-submit', '**/revise-set-page**']) {
        await page.route(pattern, (route) => {
            posts.push({ url: new URL(route.request().url()).pathname, body: route.request().postData() || '' });

            if (options.method === 'page-reload') {
                return route.fulfill({ contentType: 'text/html', body: '<p>Saved natively</p>' });
            }

            return route.fulfill({
                // The response a revise save returns.
                json: options.response ?? {
                    success: true,
                    submitAction: 'submit',
                    outcome: 'revised',
                    nextPageId: null,
                    totalPages: 4,
                    isFinalPage: true,
                    completion: null,
                    redirect: null,
                },
            });
        });
    }

    await page.goto('/revise-fixture');
    await page.addScriptTag({ content: script });
    await page.evaluate(async () => {
        await window.revisePagesBoundary.createFormieClient().mount(document.querySelector('form')!, {
            mode: 'server-rendered',
            refreshTokens: false,
        });
    });

    return posts;
}

const visiblePage = (page: Page) => page.locator('[data-formie-page]:not([data-formie-page-hidden])').getAttribute('data-formie-page-id');

for (const method of ['ajax', 'page-reload'] as const) {
    test(`${method}: page buttons change page in the browser and the last page saves every answer once`, async ({ page }) => {
        const posts = await mountForm(page, { method });

        await page.getByRole('button', { name: 'Next' }).first().click();
        expect(await visiblePage(page)).toBe('2');
        await page.getByLabel('Second').fill('Two edited');
        await page.locator('[data-formie-page-id="2"]').getByRole('button', { name: 'Next' }).click();
        // The conditionally hidden page is skipped.
        expect(await visiblePage(page)).toBe('4');
        await page.locator('[data-formie-page-id="4"]').getByRole('button', { name: 'Back' }).click();
        expect(await visiblePage(page)).toBe('2');
        await page.locator('[data-formie-page-id="2"]').getByRole('button', { name: 'Next' }).click();
        expect(posts).toEqual([]);

        await page.getByLabel('Third').fill('Three edited');
        await page.getByRole('button', { name: 'Save' }).click();

        if (method === 'ajax') {
            await expect.poll(() => posts.length).toBe(1);
            await expect(page.locator('form')).not.toHaveAttribute('data-formie-loading', 'true');
            expect(await visiblePage(page)).toBe('4');
        } else {
            await expect(page.getByText('Saved natively')).toBeVisible();
        }

        expect(posts).toHaveLength(1);
        expect(posts[0].url).toBe('/revise-submit');

        for (const value of ['name="fields[first]"\r\n\r\nOne', 'name="fields[second]"\r\n\r\nTwo edited', 'name="fields[third]"\r\n\r\nThree edited', 'name="submitAction"\r\n\r\nsubmit']) {
            expect(posts[0].body).toContain(value);
        }
    });
}

test('a page with an invalid answer stays put without saving', async ({ page }) => {
    const posts = await mountForm(page, { method: 'ajax' });

    await page.getByLabel('First').fill('');
    await page.getByRole('button', { name: 'Next' }).first().click();
    expect(await visiblePage(page)).toBe('1');
    await expect(page.locator('[data-formie-field-handle="first"]')).toHaveAttribute('data-formie-field-has-error', 'true');
    expect(posts).toEqual([]);
});

test('page tabs change page in the browser', async ({ page }) => {
    const posts = await mountForm(page, { method: 'ajax' });

    await page.getByRole('link', { name: 'Four' }).click();
    expect(await visiblePage(page)).toBe('4');
    await page.getByRole('link', { name: 'One' }).click();
    expect(await visiblePage(page)).toBe('1');
    expect(posts).toEqual([]);
});

test('a save rejected for an answer on an earlier page shows that page', async ({ page }) => {
    await mountForm(page, {
        method: 'ajax',
        response: { success: false, errors: { first: ['First is not allowed.'], form: ['Couldn’t save submission due to errors.'] } },
    });

    await page.getByRole('link', { name: 'Four' }).click();
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.locator('[data-formie-field-handle="first"]')).toHaveAttribute('data-formie-field-has-error', 'true');
    expect(await visiblePage(page)).toBe('1');
});

test('forms that are not editing a completed submission still send each page', async ({ page }) => {
    const posts = await mountForm(page, {
        method: 'ajax',
        revise: false,
        response: { success: true, submitAction: 'submit', outcome: 'pageChanged', nextPageId: 2, totalPages: 4, isFinalPage: false },
    });

    await page.getByRole('button', { name: 'Next' }).first().click();
    await expect.poll(() => visiblePage(page)).toBe('2');
    expect(posts).toHaveLength(1);
});
