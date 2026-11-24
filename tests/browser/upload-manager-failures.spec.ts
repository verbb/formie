import { test, expect, type Page, type Route } from '@playwright/test';
import { build } from 'esbuild';
import { readFileSync } from 'node:fs';
import path from 'node:path';

// An Upload Manager file that fails to upload, or is still uploading, must stop the form being sent without it. Server-rendered
// forms show this on the field through the browser validator. Client-rendered forms have no validator, so the module's
// submit hook stops the submit instead.
let script: string;
const origin = 'http://upload-manager.test';

test.beforeAll(async () => {
    const imports = JSON.parse(readFileSync('packages/formie-browser/package.json', 'utf8')).imports;
    const result = await build({
        stdin: {
            contents: `
                export { uploadManagerModule } from './packages/formie-browser/src/js/modules/fields/upload-manager';
                export { FormieValidator } from './packages/formie-browser/src/js/validation/validator';
            `,
            resolveDir: process.cwd(),
        },
        bundle: true,
        format: 'iife',
        globalName: 'uploadBoundary',
        loader: { '.css': 'text', '.svg': 'text' },
        define: { 'process.env.NODE_ENV': '"development"' },
        alias: Object.fromEntries(Object.entries(imports).map(([key, value]) => [
            key.replace('/*', ''),
            path.resolve('packages/formie-browser', (typeof value === 'string' ? value : (value as { default: string }).default).replace('/*', '')),
        ])),
        write: false,
    });
    script = result.outputFiles[0].text;
});

const markup = (required: boolean) => `<!doctype html><html><body>
    <form>
        <input type="hidden" name="handle" value="uploads">
        <div data-formie-field-handle="files">
            <label data-formie-field-label for="files-input">Files</label>
            <div class="formie-upload-manager" data-formie-upload-manager-root>
                <div data-formie-upload-manager data-formie-file-upload-upload-endpoint="${origin}/upload">
                    <button type="button" data-formie-upload-manager-browse>Browse files</button>
                </div>
                <input id="files-input" type="file" multiple data-formie-upload-manager-input data-formie-validation-skip>
                <input type="text" readonly data-formie-input data-formie-upload-manager-status ${required ? 'required data-formie-validation-required="true"' : ''}>
                <ul data-formie-upload-manager-list></ul>
            </div>
        </div>
        <button type="submit" data-formie-action="submit">Submit</button>
    </form>
</body></html>`;

const file = { name: 'report.txt', mimeType: 'text/plain', buffer: Buffer.from('upload manager failure') };

async function open(page: Page, surface: 'client-rendered' | 'server-rendered', { required = false } = {}) {
    await page.route(`${origin}/`, (route) => route.fulfill({ contentType: 'text/html', body: markup(required) }));
    await page.goto(`${origin}/`);
    await page.addScriptTag({ content: script });
    await page.evaluate(async(surface) => {
        const boundary = (window as any).uploadBoundary;
        const form = document.querySelector('form')!;

        // Server-rendered forms register their validator on the form before modules mount, as create-formie-client does.
        if (surface === 'server-rendered') {
            const validator = new boundary.FormieValidator(form, { live: true });
            (form as any).formieValidation = validator;
            boundary.validator = validator;
        }

        boundary.instance = await boundary.uploadManagerModule.setup({
            formId: 'uploads', root: form, form, target: form, scope: 'form', surface, state: {}, options: {},
            on: () => () => {}, emit: async() => {},
        });
    }, surface);
}

// Runs the module's submit hook as the client-rendered host does, and returns the abort message, if any.
const submitHook = (page: Page, action: 'submit' | 'back' | 'save' = 'submit') => page.evaluate(async(action) => {
    const { instance } = (window as any).uploadBoundary;
    let reason: string | null = null;
    const form = document.querySelector('form')!;
    await instance.beforeSubmit?.({ form, action, formData: new FormData(form), abort: (message: string) => { reason = message; }, isAborted: () => reason !== null, abortReason: () => reason ?? undefined });
    return reason;
}, action);

const validatorErrors = (page: Page) => page.evaluate(() => {
    const { validator } = (window as any).uploadBoundary;
    validator.submit(document.querySelector('form'), { final: true });
    return Array.from(document.querySelectorAll('[data-formie-field-error]')).map((element) => element.textContent?.trim());
});

const failUploads = (page: Page) => page.route(`${origin}/upload`, (route) => route.fulfill({ status: 400, contentType: 'application/json', body: JSON.stringify({ message: 'Invalid form handle.' }) }));

const succeedUpload = (route: Route) => route.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, assetId: 41, filename: 'report.txt', uploadUid: 'uid-41', attachToken: 'attach-41' }) });

test.describe('client-rendered', () => {
    test('a failed upload stops the submit until the file is removed', async ({ page }) => {
        await failUploads(page);
        await open(page, 'client-rendered');
        await page.locator('[data-formie-upload-manager-input]').setInputFiles(file);
        await expect(page.locator('[data-formie-upload-manager-list] li.is-error')).toHaveCount(1);

        expect(await submitHook(page)).toBe('Remove the files that failed to upload from Files.');
        // Going back or saving doesn't send the form as complete, so it isn't stopped.
        expect(await submitHook(page, 'back')).toBeNull();
        expect(await submitHook(page, 'save')).toBeNull();

        await page.locator('[data-formie-upload-manager-list] li [data-formie-upload-manager-remove]').click();
        await expect(page.locator('[data-formie-upload-manager-list] li')).toHaveCount(0);
        expect(await submitHook(page)).toBeNull();
    });

    test('a file still uploading stops the submit until it finishes', async ({ page }) => {
        let release: () => void = () => {};
        await page.route(`${origin}/upload`, (route) => new Promise<void>((resolve) => { release = () => { void succeedUpload(route).then(resolve); }; }));
        await open(page, 'client-rendered');
        await page.locator('[data-formie-upload-manager-input]').setInputFiles(file);
        await expect(page.locator('[data-formie-upload-manager-list] li')).toHaveCount(1);

        expect(await submitHook(page)).toBe('Wait for Files to finish uploading.');

        release();
        await expect(page.locator('[data-formie-upload-manager-status]')).toHaveValue('uploaded');
        expect(await submitHook(page)).toBeNull();
    });

    test('a field that is not shown does not stop the submit', async ({ page }) => {
        await failUploads(page);
        await open(page, 'client-rendered');
        await page.locator('[data-formie-upload-manager-input]').setInputFiles(file);
        await expect(page.locator('[data-formie-upload-manager-list] li.is-error')).toHaveCount(1);
        await page.evaluate(() => { (document.querySelector('[data-formie-field-handle="files"]') as HTMLElement).style.display = 'none'; });

        expect(await submitHook(page)).toBeNull();
    });
});

test.describe('server-rendered', () => {
    test('a failed upload shows an error on the field until the file is removed', async ({ page }) => {
        await failUploads(page);
        await open(page, 'server-rendered');
        await page.locator('[data-formie-upload-manager-input]').setInputFiles(file);
        await expect(page.locator('[data-formie-upload-manager-list] li.is-error')).toHaveCount(1);

        expect(await validatorErrors(page)).toEqual(['Remove the files that failed to upload from Files.']);
        // The validator is what stops server-rendered forms, so the submit hook leaves them alone.
        expect(await submitHook(page)).toBeNull();

        await page.locator('[data-formie-upload-manager-list] li [data-formie-upload-manager-remove]').click();
        expect(await validatorErrors(page)).toEqual([]);
    });

    test('a required field with only a failed upload asks for it to be removed rather than saying it is blank', async ({ page }) => {
        await failUploads(page);
        await open(page, 'server-rendered', { required: true });
        await page.locator('[data-formie-upload-manager-input]').setInputFiles(file);
        await expect(page.locator('[data-formie-upload-manager-list] li.is-error')).toHaveCount(1);

        expect(await validatorErrors(page)).toEqual(['Remove the files that failed to upload from Files.']);

        await page.locator('[data-formie-upload-manager-list] li [data-formie-upload-manager-remove]').click();
        expect(await validatorErrors(page)).toEqual(['Files cannot be blank.']);
    });
});
