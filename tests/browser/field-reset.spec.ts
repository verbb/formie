import { test, expect } from '@playwright/test';
import { build } from 'esbuild';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import type {
    BrowserModuleDefinition,
    BrowserModuleInstance,
} from '../../packages/formie-browser/src/js/contracts/modules';

declare global {
    interface Window {
        fieldReset: {
            tableModule: BrowserModuleDefinition;
            repeaterModule: BrowserModuleDefinition;
            signatureModule: BrowserModuleDefinition;
        };
        fieldResetTest: {
            mount: () => Promise<void>;
            unmount: () => Promise<void>;
            state: () => {
                tableRows: number;
                tableAddDisabled: boolean;
                tableValue: string;
                repeaterRows: number;
                signature: string;
                signatureDrawn: boolean;
            };
        };
    }
}

let script: string;
test.beforeAll(async () => {
    const imports = JSON.parse(
        readFileSync('packages/formie-browser/package.json', 'utf8'),
    ).imports;
    const result = await build({
        stdin: {
            contents: `export { tableModule } from './packages/formie-browser/src/js/modules/fields/table';
                export { repeaterModule } from './packages/formie-browser/src/js/modules/fields/repeater';
                export { signatureModule } from './packages/formie-browser/src/js/modules/fields/signature';`,
            resolveDir: process.cwd(),
        },
        bundle: true,
        format: 'iife',
        globalName: 'fieldReset',
        write: false,
        loader: { '.css': 'text' },
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
    });
    script = result.outputFiles[0].text;
});

// One page of a multi-page form. Unmounting stands in for the module loader
// disposing field modules when the page is hidden.
test.beforeEach(async ({ page }) => {
    await page.setContent(`<form>
        <div data-formie-field-handle="tbl" data-formie-table-field-layout>
            <table data-formie-table><tbody data-formie-table-body>
                <tr data-formie-table-row data-formie-table-row-id="0"><td><input name="tbl[0][col1]" value="Default"></td><td><button type="button" data-formie-table-remove>Remove</button></td></tr>
            </tbody></table>
            <button type="button" data-formie-table-add="tbl" data-formie-max-rows="3" data-formie-template-id="tbl-row">Add a row</button>
            <template data-formie-template-id="tbl-row"><td><input name="tbl[__ROW__][col1]"></td><td><button type="button" data-formie-table-remove>Remove</button></td></template>
        </div>
        <div data-formie-field-handle="rep" data-formie-repeater-field-layout>
            <div data-formie-repeater-container></div>
            <button type="button" data-formie-repeater-add="rep" data-formie-template-id="rep-row">Add</button>
            <template data-formie-template-id="rep-row"><div data-formie-repeater-item data-formie-repeater-item-id="__ROW__"><input name="rep[__ROW__][text]"><button type="button" data-formie-repeater-remove>Remove</button></div></template>
        </div>
        <div data-formie-field-handle="sig">
            <input type="hidden" name="sig" data-formie-signature-input>
            <canvas data-formie-signature-canvas style="display:block;width:300px;height:100px"></canvas>
        </div>
    </form>`);
    await page.addScriptTag({ content: script });
    await page.evaluate(() => {
        const form = document.querySelector('form')!;
        let instances: BrowserModuleInstance[] = [];
        const setup = async (
            module: BrowserModuleDefinition,
            target: HTMLElement,
        ) => {
            const instance = await module.setup({
                formId: 'field-reset',
                form,
                root: form,
                target,
                scope: 'field',
                state: {},
                surface: 'server-rendered',
                options: {},
                on: () => () => {},
                emit: async () => {},
            });
            if (instance) instances.push(instance as BrowserModuleInstance);
        };
        window.fieldResetTest = {
            mount: async () => {
                const signatureReady = new Promise((resolve) =>
                    form.addEventListener(
                        'formie:field:signature:init',
                        resolve,
                        { once: true },
                    ),
                );
                await setup(
                    window.fieldReset.tableModule,
                    form.querySelector('[data-formie-table-field-layout]')!,
                );
                await setup(
                    window.fieldReset.repeaterModule,
                    form.querySelector('[data-formie-repeater-field-layout]')!,
                );
                await setup(
                    window.fieldReset.signatureModule,
                    form.querySelector('[data-formie-field-handle="sig"]')!,
                );
                await signatureReady;
            },
            unmount: async () => {
                await Promise.all(
                    instances.map((instance) => instance.destroy()),
                );
                instances = [];
            },
            state: () => {
                const canvas = form.querySelector('canvas')!;
                const pixels = canvas
                    .getContext('2d')!
                    .getImageData(0, 0, canvas.width, canvas.height).data;
                return {
                    tableRows: form.querySelectorAll('[data-formie-table-row]')
                        .length,
                    tableAddDisabled: form.querySelector<HTMLButtonElement>(
                        '[data-formie-table-add]',
                    )!.disabled,
                    tableValue: form.querySelector<HTMLInputElement>(
                        '[data-formie-table-row] input',
                    )!.value,
                    repeaterRows: form.querySelectorAll(
                        '[data-formie-repeater-item]',
                    ).length,
                    // Enough of the data URL to tell an empty value from a signature.
                    signature: form
                        .querySelector<HTMLInputElement>(
                            '[data-formie-signature-input]',
                        )!
                        .value.slice(0, 22),
                    signatureDrawn: pixels.some((value) => value !== 0),
                    // What the next submission would send.
                    submitted: [...new FormData(form)].map(
                        ([name, value]) =>
                            `${name}=${String(value).slice(0, 22)}`,
                    ),
                };
            },
        };
    });
});

const mount = (page: import('@playwright/test').Page) =>
    page.evaluate(() => window.fieldResetTest.mount());

async function fillFields(page: import('@playwright/test').Page) {
    await page.getByRole('textbox').first().fill('Visitor A');
    await page.getByRole('button', { name: 'Add a row' }).click();
    await page.getByRole('button', { name: 'Add a row' }).click();
    await page.getByRole('button', { name: 'Add', exact: true }).click();
    await page.getByRole('button', { name: 'Add', exact: true }).click();
    const box = (await page.locator('canvas').boundingBox())!;
    await page.mouse.move(box.x + 20, box.y + 20);
    await page.mouse.down();
    await page.mouse.move(box.x + 150, box.y + 70, { steps: 10 });
    await page.mouse.up();
    await expect
        .poll(() => page.evaluate(() => window.fieldResetTest.state()))
        .toMatchObject({
            tableRows: 3,
            tableAddDisabled: true,
            repeaterRows: 2,
            signatureDrawn: true,
        });
    expect(
        (await page.evaluate(() => window.fieldResetTest.state())).signature,
    ).toBe('data:image/png;base64,');
}

const initialState = {
    tableRows: 1,
    tableAddDisabled: false,
    tableValue: 'Default',
    repeaterRows: 0,
    signature: '',
    signatureDrawn: false,
    submitted: ['tbl[0][col1]=Default', 'sig='],
};

test('mounted Table, Repeater and Signature return to their initial state on reset', async ({
    page,
}) => {
    await mount(page);
    await fillFields(page);
    await page.evaluate(() => document.querySelector('form')!.reset());
    await expect
        .poll(() => page.evaluate(() => window.fieldResetTest.state()))
        .toEqual(initialState);

    // The restored rows keep working.
    await page.getByRole('button', { name: 'Add a row' }).click();
    await expect(page.locator('[data-formie-table-row]')).toHaveCount(2);
    // The default row's Remove was rebound by the reset.
    await page.getByRole('button', { name: 'Remove' }).first().click();
    await expect(page.locator('[data-formie-table-row]')).toHaveCount(1);
});

test('fields reset while their page is hidden and unmounted', async ({
    page,
}) => {
    await mount(page);
    await fillFields(page);
    await page.evaluate(async () => {
        await window.fieldResetTest.unmount();
        document.querySelector('form')!.reset();
        await new Promise((resolve) => setTimeout(resolve, 0));
        await window.fieldResetTest.mount();
    });
    await expect
        .poll(() => page.evaluate(() => window.fieldResetTest.state()))
        .toEqual(initialState);
});

test('a remount after page navigation keeps the first mount as the reset state', async ({
    page,
}) => {
    await mount(page);
    await fillFields(page);
    await page.evaluate(async () => {
        await window.fieldResetTest.unmount();
        await window.fieldResetTest.mount();
        // Let the remounted pad finish redrawing before the reset, as it
        // would long before a visitor submits.
        await new Promise((resolve) => setTimeout(resolve, 500));
        document.querySelector('form')!.reset();
    });
    await expect
        .poll(() => page.evaluate(() => window.fieldResetTest.state()))
        .toEqual(initialState);
});

test('a value the browser restored before mounting is not kept as the reset state', async ({
    page,
}) => {
    // Browsers can restore typed values into server-rendered rows on reload.
    await page.getByRole('textbox').first().fill('Restored');
    await mount(page);
    await page.evaluate(() => document.querySelector('form')!.reset());
    await expect
        .poll(() => page.evaluate(() => window.fieldResetTest.state()))
        .toEqual(initialState);
});
