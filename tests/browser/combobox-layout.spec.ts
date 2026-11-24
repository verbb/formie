import { test, expect, type Locator, type Page } from '@playwright/test';
import { build } from 'esbuild';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import type { initFormieCombobox } from '../../packages/formie-browser/src/js/modules/fields/combobox';

declare global {
    interface Window {
        comboboxBoundary: { initFormieCombobox: typeof initFormieCombobox };
    }
}

let script: string;
let tokens: string;

test.beforeAll(async () => {
    const imports = JSON.parse(
        readFileSync('packages/formie-browser/package.json', 'utf8'),
    ).imports;
    const aliases = Object.fromEntries(
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
    );
    const result = await build({
        stdin: {
            contents: `export { initFormieCombobox } from './packages/formie-browser/src/js/modules/fields/combobox';`,
            resolveDir: process.cwd(),
        },
        bundle: true,
        format: 'iife',
        globalName: 'comboboxBoundary',
        alias: aliases,
        plugins: [{
            // The module injects its own stylesheet, which is what these checks measure.
            name: 'inline-css',
            setup(builder) {
                builder.onResolve({ filter: /\?inline$/ }, (args) => {
                    const [alias, ...rest] = args.path.replace(/\?inline$/, '').split('/');

                    return { path: path.join(aliases[alias], ...rest), namespace: 'inline-css' };
                });
                builder.onLoad({ filter: /.*/, namespace: 'inline-css' }, (args) => ({
                    contents: readFileSync(args.path, 'utf8'),
                    loader: 'text',
                }));
            },
        }],
        write: false,
    });
    script = result.outputFiles[0].text;
    tokens = readFileSync('packages/formie-browser/src/css/theme/_tokens.css', 'utf8');
});

const OPTIONS = `
    <option value="">Select an option</option>
    <optgroup label="Preserves">
        <option value="kiwi">Kiwi - Small</option>
        <option value="marmalade">Mandarin Orange Marmalade</option>
    </optgroup>
    <optgroup label="Pantry">
        <option value="vinegar">Apple Cider Vinegar</option>
    </optgroup>
`;

test.beforeEach(async ({ page }) => {
    await page.setViewportSize({ width: 800, height: 900 });
    // A narrow column, as a field in a multi-column row gets.
    await page.setContent(`
        <style>${tokens}</style>
        <form class="formie-form" style="width: 240px">
            <div class="formie-field" id="single" style="margin-bottom: 20rem">
                <select name="fields[product]" data-formie-combobox-input>${OPTIONS}</select>
            </div>
            <div class="formie-field" id="multi">
                <select name="fields[products][]" multiple data-formie-combobox-input>${OPTIONS}</select>
            </div>
        </form>
    `);
    await page.addScriptTag({ content: script });
    await page.evaluate(() => {
        const single = document.querySelector('#single select') as HTMLSelectElement;
        const multi = document.querySelector('#multi select') as HTMLSelectElement;
        multi.querySelector('option[value=""]')!.remove();
        window.comboboxBoundary.initFormieCombobox(single, { allowClear: true });
        window.comboboxBoundary.initFormieCombobox(multi, { multiple: true });
    });
});

async function pick(page: Page, field: Locator, label: string): Promise<void> {
    await field.locator('.ts-control').click();
    await field.locator('.ts-dropdown .option', { hasText: label }).click();
}

async function box(locator: Locator): Promise<{ x: number; y: number; width: number; height: number }> {
    return (await locator.boundingBox())!;
}

for (const label of ['Kiwi - Small', 'Mandarin Orange Marmalade']) {
    test(`picking “${label}” leaves the control the same height focused and unfocused`, async ({ page }) => {
        const field = page.locator('#single');
        await pick(page, field, label);

        // Focus stays in the control after a pick, with its search input showing.
        await expect(field.locator('.ts-wrapper')).toHaveClass(/\bfocus\b/);
        const focused = await box(field.locator('.ts-control'));
        const item = await box(field.locator('.ts-control .item'));
        const input = await box(field.locator('.ts-control input'));

        await page.mouse.click(700, 5);
        await expect(field.locator('.ts-wrapper')).not.toHaveClass(/\bfocus\b/);
        const unfocused = await box(field.locator('.ts-control'));

        expect(focused.height).toBe(unfocused.height);
        // The search input shares the value's line instead of wrapping below it.
        expect(input.y).toBeLessThan(item.y + item.height);
    });
}

test('a search takes the place of the single value until it is cleared', async ({ page }) => {
    const field = page.locator('#single');
    await pick(page, field, 'Mandarin Orange Marmalade');
    const item = field.locator('.ts-control .item');
    const picked = await box(field.locator('.ts-control'));

    await page.keyboard.type('cider');
    await expect(item).toBeHidden();
    await expect(field.locator('.ts-dropdown .option')).toHaveText(['Apple Cider Vinegar']);
    expect((await box(field.locator('.ts-control input'))).width).toBeGreaterThan(100);

    // Leaving without choosing drops the search and shows the value again.
    await page.mouse.click(700, 5);
    await expect(item).toBeVisible();
    await expect(item).toContainText('Mandarin Orange Marmalade');
    expect((await box(field.locator('.ts-control'))).height).toBe(picked.height);

    // Choosing a result shows the new value.
    await field.locator('.ts-control').click();
    await page.keyboard.type('cider');
    await field.locator('.ts-dropdown .option', { hasText: 'Apple Cider Vinegar' }).click();
    await expect(item).toBeVisible();
    await expect(item).toContainText('Apple Cider Vinegar');
    await expect(field.locator('select')).toHaveValue('vinegar');
});

test('the clear button of a single value sits beside the indicator', async ({ page }) => {
    const field = page.locator('#single');
    const positions: number[] = [];

    for (const label of ['Kiwi - Small', 'Mandarin Orange Marmalade']) {
        await pick(page, field, label);
        await page.mouse.click(700, 5);

        const control = await box(field.locator('.ts-control'));
        const remove = await box(field.locator('.ts-control .remove'));
        positions.push(remove.x);

        // Clear of the indicator on its right, and in the control's last 4rem whatever the value.
        expect(control.x + control.width - (remove.x + remove.width)).toBeGreaterThanOrEqual(22);
        expect(control.x + control.width - remove.x).toBeLessThanOrEqual(64);
        expect(Math.abs((remove.y + remove.height / 2) - (control.y + control.height / 2))).toBeLessThanOrEqual(1);
    }

    expect(positions[0]).toBe(positions[1]);

    await field.locator('.ts-control .remove').click();
    await expect(field.locator('select')).toHaveValue('');
    await expect(field.locator('.ts-control .item')).toHaveCount(0);
});

test('the chosen option is marked in a single dropdown’s list', async ({ page }) => {
    const field = page.locator('#single');
    await pick(page, field, 'Kiwi - Small');
    await field.locator('.ts-control').click();

    const marks = await field.locator('.ts-dropdown .option').evaluateAll((options) => options.map((option) => ({
        label: option.textContent,
        selected: option.classList.contains('selected'),
        mark: getComputedStyle(option, '::after').content !== 'none',
    })));

    expect(marks).toEqual([
        { label: 'Kiwi - Small', selected: true, mark: true },
        { label: 'Mandarin Orange Marmalade', selected: false, mark: false },
        { label: 'Apple Cider Vinegar', selected: false, mark: false },
    ]);

    // Focus never left the control, so Tom Select hasn't redrawn the list between picks.
    await field.locator('.ts-dropdown .option', { hasText: 'Apple Cider Vinegar' }).click();
    await field.locator('.ts-control').click();
    await expect(field.locator('.ts-dropdown .option.selected')).toHaveText(['Apple Cider Vinegar']);

    await field.locator('.ts-control .remove').click();
    await expect(field.locator('select')).toHaveValue('');
    await expect(field.locator('.ts-dropdown .option.selected')).toHaveCount(0);
});

test('group headings are styled apart from options', async ({ page }) => {
    const field = page.locator('#single');
    await field.locator('.ts-control').click();
    await expect(field.locator('.ts-dropdown .optgroup-header').first()).toBeVisible();

    const styles = await field.locator('.ts-dropdown').evaluate((dropdown) => {
        const read = (selector: string) => {
            const element = dropdown.querySelector(selector) as HTMLElement;
            const style = getComputedStyle(element);

            return { size: parseFloat(style.fontSize), weight: Number(style.fontWeight), left: parseFloat(style.paddingLeft), color: style.color, cursor: style.cursor };
        };

        return { header: read('.optgroup-header'), option: read('.optgroup .option') };
    });

    expect(styles.header.size).toBeLessThan(styles.option.size);
    expect(styles.header.weight).toBeGreaterThan(styles.option.weight);
    expect(styles.header.color).not.toBe(styles.option.color);
    expect(styles.header.cursor).toBe('default');
    expect(styles.option.left).toBeGreaterThan(styles.header.left);
});

test('multi-select values stay as pills with their own remove buttons', async ({ page }) => {
    const field = page.locator('#multi');
    await pick(page, field, 'Kiwi - Small');
    await page.mouse.click(700, 5);

    const item = await box(field.locator('.ts-control .item'));
    const remove = await box(field.locator('.ts-control .item .remove'));

    await expect(field.locator('.ts-control .item')).toHaveCSS('border-top-width', '1px');
    expect(remove.x).toBeGreaterThanOrEqual(item.x);
    expect(remove.x + remove.width).toBeLessThanOrEqual(item.x + item.width);
    // Picked options leave the list, so there is nothing to mark.
    await field.locator('.ts-control').click();
    await expect(field.locator('.ts-dropdown .option', { hasText: 'Kiwi - Small' })).toHaveCount(0);
});
