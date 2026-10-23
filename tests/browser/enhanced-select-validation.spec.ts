import { test, expect } from '@playwright/test';
import { build } from 'esbuild';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import type { FormieValidator } from '../../packages/formie-browser/src/js/validation/validator';

declare global {
    interface Window {
        enhancedSelectBoundary: { FormieValidator: typeof FormieValidator };
        enhancedSelectValidator: FormieValidator;
    }
}

let script: string;

test.beforeAll(async () => {
    const imports = JSON.parse(
        readFileSync('packages/formie-browser/package.json', 'utf8'),
    ).imports;
    const result = await build({
        stdin: {
            contents: `export { FormieValidator } from './packages/formie-browser/src/js/validation/validator';`,
            resolveDir: process.cwd(),
        },
        bundle: true,
        format: 'iife',
        globalName: 'enhancedSelectBoundary',
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

test.beforeEach(async ({ page }) => {
    // Match the DOM boundary used by select enhancements without depending on a vendor's bundle.
    await page.setContent(`
        <form>
            <section data-formie-page>
                <div data-formie-field-handle="entries" data-formie-validation='[{"type":"required"}]'>
                    <label data-formie-field-label>Entries</label>
                    <div class="enhanced-select">
                        <select name="fields[entries][]" multiple hidden data-formie-required-message="Choose an entry.">
                            <option value="123">First entry</option>
                            <option value="456">Second entry</option>
                        </select>
                        <input aria-label="Search entries">
                        <input name="" aria-label="Empty-name helper">
                    </div>
                </div>
            </section>
            <button type="button">Validate</button>
        </form>
    `);
    await page.addScriptTag({ content: script });
    await page.evaluate(() => {
        window.enhancedSelectValidator =
            new window.enhancedSelectBoundary.FormieValidator(
                document.querySelector('form')!,
                { live: true },
            );
        document
            .querySelector('button')!
            .addEventListener('click', () =>
                window.enhancedSelectValidator.submit(),
            );
    });
});

test('selected values pass without validating unnamed helper controls', async ({
    page,
}) => {
    await page.evaluate(() => {
        document.querySelector('select')!.options[0].selected = true;
    });
    await page.getByRole('button', { name: 'Validate' }).click();
    await expect(page.locator('[data-formie-field-error]')).toHaveCount(0);
    expect(
        await page.evaluate(() =>
            window.enhancedSelectValidator.inputs().map((input) => input.name),
        ),
    ).toEqual(['fields[entries][]']);
    await page.getByRole('textbox', { name: 'Search entries' }).fill('search');
    await page.getByRole('textbox', { name: 'Search entries' }).fill('');
    await page.getByRole('button', { name: 'Validate' }).focus();
    await expect(page.locator('[data-formie-field-error]')).toHaveCount(0);
});

test('empty enhanced selects use their own required message, including direct validation', async ({
    page,
}) => {
    await page.getByRole('button', { name: 'Validate' }).click();
    await expect(page.locator('[data-formie-field-error]')).toHaveText(
        'Choose an entry.',
    );
    expect(
        await page.evaluate(() =>
            window.enhancedSelectValidator
                .validate(document.querySelector('select'))
                .map((error) => error.message),
        ),
    ).toEqual(['Choose an entry.']);
    expect(
        await page.evaluate(() =>
            window.enhancedSelectValidator.validate(
                document.querySelector('input'),
            ),
        ),
    ).toEqual([]);
});

test('native change events clear and restore errors on the hidden value control', async ({
    page,
}) => {
    await page.getByRole('button', { name: 'Validate' }).click();
    await page.evaluate(() => {
        const select = document.querySelector('select')!;
        select.options[0].selected = true;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    });
    await expect(page.locator('[data-formie-field-error]')).toHaveCount(0);
    await page.evaluate(() => {
        const select = document.querySelector('select')!;
        select.selectedIndex = -1;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    });
    await expect(page.locator('[data-formie-field-error]')).toHaveText(
        'Choose an entry.',
    );
});

for (const inactive of [
    'disabled',
    'skip',
    'conditions-disabled',
    'conditionally-hidden',
    'hidden-field',
    'hidden-parent',
]) {
    test(`enhanced selects respect ${inactive}`, async ({ page }) => {
        await page.evaluate((state) => {
            const select = document.querySelector('select')!;
            const field = select.closest<HTMLElement>(
                '[data-formie-field-handle]',
            )!;
            if (state === 'disabled') select.disabled = true;
            if (state === 'skip')
                select.setAttribute('data-formie-validation-skip', '');
            if (state === 'conditions-disabled')
                field.setAttribute('data-formie-conditions-disabled', '');
            if (state === 'conditionally-hidden')
                field.setAttribute('data-formie-conditionally-hidden', '');
            if (state === 'hidden-field') field.hidden = true;
            if (state === 'hidden-parent')
                document.querySelector('section')!.style.display = 'none';
        }, inactive);
        await page.getByRole('button', { name: 'Validate' }).click();
        await expect(page.locator('[data-formie-field-error]')).toHaveCount(0);
    });
}

test('previous pages are validated only on final submission, with conditions still excluded', async ({
    page,
}) => {
    const result = await page.evaluate(() => {
        const section = document.querySelector('section')!;
        section.hidden = true;
        section.setAttribute('data-formie-page-hidden', '');
        const validator = window.enhancedSelectValidator;
        const current = validator.submit().length;
        const final = validator
            .submit(null, { final: true })
            .map((error) => error.message);
        section.setAttribute('data-formie-conditionally-hidden', '');
        const conditional = validator.submit(null, { final: true }).length;
        return { current, final, conditional };
    });
    expect(result).toEqual({
        current: 0,
        final: ['Choose an entry.'],
        conditional: 0,
    });
});

for (const mode of [
    'native',
    'css-hidden',
    'built-in-combobox',
    'display-contents',
]) {
    test(`required validation works for ${mode} selects`, async ({ page }) => {
        await page.evaluate((mode) => {
            const select = document.querySelector('select')!;
            select.hidden = false;
            if (mode === 'css-hidden') select.style.display = 'none';
            if (mode === 'display-contents')
                select.closest<HTMLElement>(
                    '[data-formie-field-handle]',
                )!.style.display = 'contents';
            if (mode === 'built-in-combobox') {
                select.hidden = true;
                select.setAttribute('data-formie-combobox-input', '');
                const combobox = document.createElement('div');
                combobox.className = 'formie-combobox';
                combobox.textContent = 'Choose an entry';
                select.after(combobox);
            }
        }, mode);
        await page.getByRole('button', { name: 'Validate' }).click();
        await expect(page.locator('[data-formie-field-error]')).toHaveText(
            'Choose an entry.',
        );
    });
}
