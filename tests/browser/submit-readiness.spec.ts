import { test, expect } from '@playwright/test';
import { build } from 'esbuild';
import { readFileSync } from 'node:fs';
import path from 'node:path';

let script: string;

test.beforeAll(async () => {
    const imports = JSON.parse(readFileSync('packages/formie-browser/package.json', 'utf8')).imports;
    const result = await build({
        stdin: {
            contents: `
                export { FormieValidator } from './packages/formie-browser/src/js/validation/validator';
                export { bindSubmitReadiness } from './packages/formie-browser/src/js/validation/submit-readiness';
            `,
            resolveDir: process.cwd(),
        },
        bundle: true,
        format: 'iife',
        globalName: 'readinessBoundary',
        alias: Object.fromEntries(Object.entries(imports).map(([key, value]) => [
            key.replace('/*', ''),
            path.resolve('packages/formie-browser', (typeof value === 'string' ? value : (value as { default: string }).default).replace('/*', '')),
        ])),
        write: false,
    });
    script = result.outputFiles[0].text;
});

test.beforeEach(async ({ page }) => {
    await page.setContent(`
        <form data-formie-disable-submit-until-valid>
            <div data-formie-field-handle="email" data-formie-validation='[{"type":"required"},{"type":"email"}]'>
                <label for="email" data-formie-field-label>Email</label>
                <input id="email" name="email" type="email" required>
            </div>
            <label for="name">Name</label><input id="name" name="name">
            <button data-formie-action="submit">Send</button>
        </form>
    `);
    await page.addScriptTag({ content: script });
    await page.evaluate(() => {
        const boundary = (window as any).readinessBoundary;
        boundary.validator = new boundary.FormieValidator(document.querySelector('form'), { live: true });
    });
});

test('readiness checks preserve an already displayed validation error', async ({ page }) => {
    await page.getByLabel('Email', { exact: true }).fill('invalid');
    await page.getByLabel('Name', { exact: true }).click();
    await expect(page.locator('[data-formie-field-error]')).toHaveCount(1);
    const result = await page.evaluate(() => {
        const form = document.querySelector('form')!;
        const originalError = form.querySelector('[data-formie-field-error]');
        const observer = new MutationObserver(() => {});
        observer.observe(form, { childList: true, subtree: true, attributes: true });
        const valid = (window as any).readinessBoundary.validator.isValid(form, { renderErrors: false });
        const mutations = observer.takeRecords().length;
        observer.disconnect();
        return { valid, mutations, sameError: originalError === form.querySelector('[data-formie-field-error]') };
    });
    expect(result).toEqual({ valid: false, mutations: 0, sameError: true });
});

test('live validation stays responsive with submit gating and recovers after correction', async ({ page }) => {
    await page.evaluate(() => {
        const boundary = (window as any).readinessBoundary;
        const form = document.querySelector('form')!;
        boundary.bindSubmitReadiness(form, boundary.validator, form);
    });
    await expect(page.getByRole('button', { name: 'Send' })).toBeDisabled();
    await page.getByLabel('Email', { exact: true }).fill('invalid');
    await page.getByLabel('Name', { exact: true }).click();
    await expect(page.locator('[data-formie-field-error]')).toHaveCount(1);
    await expect(page.getByRole('button', { name: 'Send' })).toBeDisabled();
    await page.getByLabel('Email', { exact: true }).fill('visitor@example.com');
    await expect(page.locator('[data-formie-field-error]')).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Send' })).toBeEnabled();
    await page.getByLabel('Email', { exact: true }).fill('invalid-again');
    await expect(page.locator('[data-formie-field-error]')).toHaveCount(1);
    await expect(page.getByRole('button', { name: 'Send' })).toBeDisabled();
});

test('submit gating includes the unnamed Upload Manager status input', async ({ page }) => {
    // The Upload Manager keeps its required rule on an unnamed status input and adds hidden asset inputs once a file uploads.
    await page.evaluate(() => {
        const form = document.querySelector('form')!;
        form.querySelector('#email')!.closest('[data-formie-field-handle]')!.remove();
        form.insertAdjacentHTML('afterbegin', `
            <div data-formie-field-handle="files" data-formie-validation='[{"type":"required"}]'>
                <label data-formie-field-label>Files</label>
                <input type="hidden" name="fields[files]" value="" data-formie-file-upload-anchor="true">
                <input type="file" multiple data-formie-upload-manager-input data-formie-validation-skip>
                <input type="text" readonly required data-formie-input data-formie-upload-manager-status data-formie-validation-required="true">
            </div>
        `);
        const boundary = (window as any).readinessBoundary;
        boundary.validator = new boundary.FormieValidator(form, { live: true });
        boundary.bindSubmitReadiness(form, boundary.validator, form);
    });
    await expect(page.getByRole('button', { name: 'Send' })).toBeDisabled();
    await page.evaluate(() => {
        const field = document.querySelector('[data-formie-field-handle="files"]')!;
        (field.querySelector('[data-formie-upload-manager-status]') as HTMLInputElement).value = 'uploaded';
        field.insertAdjacentHTML('beforeend', '<input type="hidden" name="fields[files][0][uploadUid]" value="abc" data-formie-file-upload-entry="true">');
    });
    await expect(page.getByRole('button', { name: 'Send' })).toBeEnabled();
    await page.evaluate(() => {
        const field = document.querySelector('[data-formie-field-handle="files"]')!;
        (field.querySelector('[data-formie-upload-manager-status]') as HTMLInputElement).value = '';
        field.querySelector('[data-formie-file-upload-entry]')!.remove();
    });
    await expect(page.getByRole('button', { name: 'Send' })).toBeDisabled();
});
