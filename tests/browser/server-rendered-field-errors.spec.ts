import { test, expect, type Page } from '@playwright/test';
import path from 'node:path';

const invalidNames = (page: Page) =>
    page.evaluate(() =>
        [...document.querySelectorAll('[aria-invalid="true"]')].map((node) =>
            node.getAttribute('name'),
        ),
    );

// Attributes the theme adds to a field and its controls after the page loads.
const errorStyled = (page: Page, selector: string) =>
    page.evaluate(
        (scope) =>
            [...document.querySelectorAll(`${scope} [data-formie-input-has-error="true"]`)].map((node) =>
                node.getAttribute('name'),
            ),
        selector,
    );

const message = (text: string) =>
    `<div data-formie-field-errors><div data-formie-field-error="true" id="error-${text.replace(/\W/g, '')}">${text}</div></div>`;

const text = (handle: string, name: string, error = '') =>
    `<div data-formie-field-handle="${handle}"><label>${handle}<input type="text" name="${name}"></label>${error}</div>`;

const forms = {
    repeater: `<div data-formie-field-handle="items">
        <div data-repeater-row>${text('items.0.name', 'fields[items][0][name]')}${text('items.0.email', 'fields[items][0][email]')}</div>
        <div data-repeater-row>${text('items.1.name', 'fields[items][1][name]')}${text('items.1.email', 'fields[items][1][email]', message('Blocked row'))}</div>
    </div>`,
    group: `<div data-formie-field-handle="details">
        ${text('details.name', 'fields[details][name]')}${text('details.email', 'fields[details][email]', message('Blocked group'))}
    </div>`,
    deep: `<div data-formie-field-handle="items">
        <div data-formie-field-handle="items.0.address">
            ${text('items.0.address.city', 'fields[items][0][address][city]')}${text('items.0.address.zip', 'fields[items][0][address][zip]', message('Bad zip'))}
        </div>
        ${text('items.0.note', 'fields[items][0][note]')}
    </div>`,
    ownError: `<div data-formie-field-handle="address">
        ${text('address.city', 'fields[address][city]')}${text('address.zip', 'fields[address][zip]')}${message('Address is required')}
    </div>`,
    options: `<div data-formie-field-handle="choices">
        <label><input type="checkbox" name="fields[choices][]" value="a">A</label>
        <label><input type="checkbox" name="fields[choices][]" value="b">B</label>
        ${message('Choose one')}
    </div>`,
    plain: `${text('first', 'fields[first]')}${text('second', 'fields[second]', message('Second is wrong'))}`,
    // A hidden input first, so an unticked field still posts a value.
    checkboxes: `${text('before', 'fields[before]')}<div data-formie-field-handle="choices">
        <input type="hidden" name="fields[choices]" value="">
        <label><input type="checkbox" name="fields[choices][]" value="a">A</label>
        <label><input type="checkbox" name="fields[choices][]" value="b">B</label>
        ${message('Choose one')}
    </div>`,
    agree: `<div data-formie-field-handle="terms">
        <input type="hidden" name="fields[terms]" value="">
        <label><input type="checkbox" name="fields[terms]" value="1">I agree</label>
        ${message('Please agree')}
    </div>`,
    radio: `<div data-formie-field-handle="pick">
        <label><input type="radio" name="fields[pick]" value="a">A</label>
        <label><input type="radio" name="fields[pick]" value="b">B</label>
        ${message('Pick one')}
    </div>`,
    select: `<div data-formie-field-handle="pick">
        <select name="fields[pick]"><option value="">Choose</option><option value="a">A</option></select>
        ${message('Pick one')}
    </div>`,
    // A searchable dropdown hides its real select behind the search input.
    searchable: `<div data-formie-field-handle="pick">
        <select name="fields[pick]" style="display:none"><option value="">Choose</option><option value="a">A</option></select>
        <input type="text" aria-label="Search" data-formie-validation-skip>
        ${message('Pick one')}
    </div>`,
    // Mounting waits for this element, which stands in for a module enhancing the field afterwards.
    lateEnhancement: `${text('other', 'fields[other]')}<craft-csrf-input></craft-csrf-input><div data-formie-field-handle="pick">
        <select name="fields[pick]"><option value="">Choose</option><option value="a">A</option></select>
        <input type="text" aria-label="Search" data-formie-validation-skip hidden>
        ${message('Pick one')}
    </div>`,
    onlyHidden: `<div data-formie-field-handle="secret"><input type="hidden" name="fields[secret]" value="">${message('Secret is wrong')}</div>${text('after', 'fields[after]')}`,
};

for (const bundle of ['assets', 'browser']) {
    test.describe(`${bundle} bundle`, () => {
        const load = async (page: Page, body: string) => {
            await page.addInitScript(() =>
                document.addEventListener('formie:mount:after', (event) =>
                    (event.target as HTMLElement).setAttribute('data-test-ready', 'true'),
                ),
            );
            await page.route('https://server-errors.example.test/**', async (route) => {
                const url = new URL(route.request().url());

                if (url.pathname.startsWith('/assets/') || url.pathname.startsWith('/browser/')) {
                    await route.fulfill({
                        path: path.resolve(
                            url.pathname.startsWith('/assets/') ? 'src/web/assets/frontend/dist' : 'packages/formie-browser/dist',
                            url.pathname.replace(/^\/(assets|browser)\//, ''),
                        ),
                        contentType: 'application/javascript',
                    });
                    return;
                }

                const script =
                    bundle === 'assets'
                        ? '<script type="module" data-formie-startup src="/assets/js/formie.js"></script>'
                        : '<script type="module">import { createFormieClient } from "/browser/index.js"; const client = createFormieClient(); await client.mount(document.querySelector("form"), { mode: "server-rendered", refreshTokens: false });</script>';

                await route.fulfill({
                    contentType: 'text/html; charset=utf-8',
                    body: `<!doctype html><meta charset="utf-8"><form id="form" data-formie action="/submit" method="post" data-formie-submit-method="page-reload" data-formie-theme-classes='{"fieldLayoutError":["layout-error"],"fieldControlError":["control-error"]}'>${body}<button type="submit" data-formie-action="submit">Send</button></form>${script}`,
                });
            });
            await page.goto('https://server-errors.example.test/');
            await expect(page.locator('#form')).toHaveAttribute('data-test-ready', 'true');
        };

        test('marks only the control with the error in a Repeater row', async ({ page }) => {
            await load(page, forms.repeater);
            expect(await invalidNames(page)).toEqual(['fields[items][1][email]']);
            expect(await errorStyled(page, '#form')).toEqual(['fields[items][1][email]']);
            await expect(page.locator('input[name="fields[items][1][email]"]')).toBeFocused();
            await expect(page.locator('[data-formie-field-handle="items"]')).not.toHaveClass(/layout-error/);
            await expect(page.locator('[data-formie-field-handle="items.1.email"]')).toHaveClass(/layout-error/);
            await expect(page.locator('input[name="fields[items][1][email]"]')).toHaveAttribute('aria-errormessage', 'error-Blockedrow');
        });

        test('marks only the control with the error in a Group', async ({ page }) => {
            await load(page, forms.group);
            expect(await invalidNames(page)).toEqual(['fields[details][email]']);
            await expect(page.locator('[data-formie-field-handle="details"]')).not.toHaveClass(/layout-error/);
        });

        test('marks only the control with the error through several levels of nesting', async ({ page }) => {
            await load(page, forms.deep);
            expect(await invalidNames(page)).toEqual(['fields[items][0][address][zip]']);
        });

        test('still marks every sub-field when the parent field has its own error', async ({ page }) => {
            await load(page, forms.ownError);
            expect(await invalidNames(page)).toEqual(['fields[address][city]', 'fields[address][zip]']);
            await expect(page.locator('[data-formie-field-handle="address"]')).toHaveClass(/layout-error/);
        });

        test('still marks every control of a field with several inputs', async ({ page }) => {
            await load(page, forms.options);
            expect(await invalidNames(page)).toEqual(['fields[choices][]', 'fields[choices][]']);
        });

        test('focuses the first checkbox rather than the hidden input before it', async ({ page }) => {
            await load(page, forms.checkboxes);
            await expect(page.locator('input[type="checkbox"]').first()).toBeFocused();
        });

        test('focuses an Agree checkbox rather than the hidden input before it', async ({ page }) => {
            await load(page, forms.agree);
            await expect(page.locator('input[type="checkbox"]')).toBeFocused();
        });

        test('focuses the first radio button', async ({ page }) => {
            await load(page, forms.radio);
            await expect(page.locator('input[type="radio"]').first()).toBeFocused();
        });

        test('focuses a visible select', async ({ page }) => {
            await load(page, forms.select);
            await expect(page.locator('select')).toBeFocused();
        });

        test('focuses the search input of a searchable dropdown rather than its hidden select', async ({ page }) => {
            await load(page, forms.searchable);
            await expect(page.getByRole('textbox', { name: 'Search' })).toBeFocused();
        });

        test('moves focus to the search input when a module hides the select that had it', async ({ page }) => {
            await page.addInitScript(() =>
                document.addEventListener('DOMContentLoaded', () =>
                    setTimeout(() => {
                        document.querySelector<HTMLElement>('select')!.style.display = 'none';
                        document.querySelector<HTMLElement>('[aria-label="Search"]')!.hidden = false;
                        document.querySelector('craft-csrf-input')!.remove();
                    }, 300),
                ),
            );
            await load(page, forms.lateEnhancement);
            await expect(page.getByRole('textbox', { name: 'Search' })).toBeFocused();
        });

        test('does not take focus back after the visitor moved it while the form was mounting', async ({ page }) => {
            await page.addInitScript(() =>
                document.addEventListener('DOMContentLoaded', () =>
                    setTimeout(() => {
                        document.querySelector<HTMLElement>('[name="fields[other]"]')!.focus();
                        document.querySelector<HTMLElement>('select')!.style.display = 'none';
                        document.querySelector<HTMLElement>('[aria-label="Search"]')!.hidden = false;
                        document.querySelector('craft-csrf-input')!.remove();
                    }, 300),
                ),
            );
            await load(page, forms.lateEnhancement);
            await expect(page.locator('[name="fields[other]"]')).toBeFocused();
        });

        test('does not move focus into another field when only a hidden input has the error', async ({ page }) => {
            await load(page, forms.onlyHidden);
            await expect(page.locator('input[name="fields[after]"]')).not.toBeFocused();
        });

        test('leaves fields without an error alone', async ({ page }) => {
            await load(page, forms.plain);
            expect(await invalidNames(page)).toEqual(['fields[second]']);
            await expect(page.locator('[data-formie-field-handle="first"]')).not.toHaveClass(/layout-error/);
        });
    });
}
