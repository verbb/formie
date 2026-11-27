import { test, expect, type Page } from '@playwright/test';
import { build } from 'esbuild';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import type { FormieValidator } from '../../packages/formie-browser/src/js/validation/validator';

declare global {
    interface Window {
        datePartsBoundary: { FormieValidator: typeof FormieValidator };
        datePartsValidator: FormieValidator;
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
        globalName: 'datePartsBoundary',
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

type Part = { part: string; value?: string; options?: string[] };

// Matches the server-rendered Date field: the rule sits on the Date wrapper, each part is a subfield with its own input.
function dateField(handle: string, rule: Record<string, unknown>, parts: Part[]): string {
    const subfields = parts.map(({ part, value = '', options }) => {
        const name = `fields[${handle}][${part}]`;
        const control = options
            ? `<select name="${name}">${options.map((option) => `<option value="${option}"${option === value ? ' selected' : ''}>${option}</option>`).join('')}</select>`
            : `<input type="number" name="${name}" value="${value}">`;

        return `<div data-formie-field-handle="${handle}.${part}"><label data-formie-field-label>${part}</label>${control}<div data-formie-field-errors></div></div>`;
    }).join('');

    return `<div data-formie-field-handle="${handle}" data-formie-validation='${JSON.stringify([{ type: 'dateParts', ...rule }])}'>
        <fieldset><legend data-formie-field-label>${handle}</legend>${subfields}<div data-formie-field-errors></div></fieldset>
    </div>`;
}

async function mount(page: Page, fields: string, hidden = ''): Promise<void> {
    await page.setContent(`<form><section data-formie-page>${hidden}${fields}</section></form>`);
    await page.addScriptTag({ content: script });
    await page.evaluate(() => {
        window.datePartsValidator = new window.datePartsBoundary.FormieValidator(document.querySelector('form')!, { live: false });
    });
}

async function submit(page: Page): Promise<{ handle: string | null; validator: string; message: string }[]> {
    return page.evaluate(() => window.datePartsValidator.submit().map((error) => ({
        handle: error.handle,
        validator: error.validator,
        message: error.message,
    })));
}

async function setPart(page: Page, handle: string, part: string, value: string): Promise<void> {
    const control = page.locator(`[name="fields[${handle}][${part}]"]`);

    if (await control.evaluate((element) => element instanceof HTMLSelectElement)) {
        await control.selectOption(value);
    } else {
        await control.fill(value);
    }
}

async function shownErrors(page: Page): Promise<string[]> {
    return page.locator('[data-formie-field-error]').allTextContents();
}

const minRule = { minDate: '2026-12-01T00:00:00', messages: { dateMin: 'The date must be on or after Dec 1, 2026.' } };
const maxRule = { maxDate: '2026-06-30T00:00:00', messages: { dateMax: 'The date must be on or before Jun 30, 2026.' } };
const textParts = (year = '', month = '', day = ''): Part[] => [{ part: 'year', value: year }, { part: 'month', value: month }, { part: 'day', value: day }];

test('reports a date before Min Date once, with the server message, under the last part', async ({ page }) => {
    await mount(page, dateField('starts', minRule, textParts('2026', '11', '30')));

    expect(await submit(page)).toEqual([
        { handle: 'starts.day', validator: 'dateParts', message: 'The date must be on or after Dec 1, 2026.' },
    ]);
    expect(await shownErrors(page)).toEqual(['The date must be on or after Dec 1, 2026.']);
});

test('accepts dates on and after Min Date', async ({ page }) => {
    await mount(page, dateField('starts', minRule, textParts('2026', '12', '1')));
    expect(await submit(page)).toEqual([]);

    await setPart(page, 'starts', 'year', '2027');
    expect(await submit(page)).toEqual([]);
});

test('reports a date after Max Date in dropdowns', async ({ page }) => {
    await mount(page, dateField('ends', maxRule, [
        { part: 'day', value: '15', options: ['', '1', '15', '30'] },
        { part: 'month', value: '7', options: ['', '6', '7'] },
        { part: 'year', value: '2026', options: ['', '2026'] },
    ]));

    // In day-month-year order the last part is the year.
    expect(await submit(page)).toEqual([
        { handle: 'ends.year', validator: 'dateParts', message: 'The date must be on or before Jun 30, 2026.' },
    ]);

    await setPart(page, 'ends', 'month', '6');
    expect(await submit(page)).toEqual([]);
});

test('leaves an incomplete or impossible date to other rules and the server', async ({ page }) => {
    await mount(page, dateField('starts', minRule, textParts('2026', '11', '')));
    expect(await submit(page)).toEqual([]);

    await setPart(page, 'starts', 'month', '2');
    await setPart(page, 'starts', 'day', '31');
    expect(await submit(page)).toEqual([]);
});

test('clears the message when another part is corrected', async ({ page }) => {
    await mount(page, dateField('starts', minRule, textParts('2025', '12', '5')));
    await submit(page);

    expect(await shownErrors(page)).toEqual(['The date must be on or after Dec 1, 2026.']);

    await setPart(page, 'starts', 'year', '2026');

    await expect(page.locator('[data-formie-field-error]')).toHaveCount(0);
});

test('reads the time and AM/PM parts against a limit with a time', async ({ page }) => {
    const rule = { maxDate: '2026-06-30T13:00:00', messages: { dateMax: 'Too late.' } };

    await mount(page, dateField('meeting', rule, [
        ...textParts('2026', '6', '30'),
        { part: 'hour', value: '1' },
        { part: 'minute', value: '30' },
        { part: 'ampm', value: 'PM', options: ['AM', 'PM'] },
    ]));

    expect((await submit(page)).map((error) => error.message)).toEqual(['Too late.']);

    await setPart(page, 'meeting', 'ampm', 'AM');
    expect(await submit(page)).toEqual([]);
});

test('doesn\'t check a saved submission\'s date until it changes', async ({ page }) => {
    await mount(page, dateField('starts', minRule, textParts('2026', '11', '30')), '<input type="hidden" name="submissionId" value="12">');

    expect(await submit(page)).toEqual([]);

    await setPart(page, 'starts', 'day', '29');
    expect((await submit(page)).map((error) => error.message)).toEqual(['The date must be on or after Dec 1, 2026.']);
});
