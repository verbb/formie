import { test, expect, type Page } from '@playwright/test';
import { readFile } from 'node:fs/promises';
import path from 'node:path';

const theme = JSON.stringify({ fieldControlError: ['custom-input-error'], fieldErrors: ['formie-field-errors'], fieldError: ['custom-error'] });
const field = (prefix: string, handle: string, label: string, type = 'text') => `
    <div data-formie-field-handle="${handle}" data-formie-validation='[{"type":"required"}${type === 'email' ? ',{"type":"email"}' : ''}]'>
        <label for="${prefix}-${handle}" data-formie-field-label>${label}</label>
        <input id="${prefix}-${handle}" name="${handle}" type="${type}" required>
        <div id="${prefix}-${handle}-errors" data-formie-field-errors aria-live="polite" aria-atomic="true"></div>
    </div>`;
const login = (attributes = '') => `<form id="login" method="post" action="/login" data-formie-craft-form data-formie-theme-classes='${theme}' data-formie-validation-on-submit="true" ${attributes}>
    <input type="hidden" name="action" value="users/login">
    <input type="hidden" name="CRAFT_CSRF_TOKEN" value="csrf-fixture">
    <input type="hidden" name="redirect" value="signed-redirect-fixture">
    ${field('login', 'loginName', 'Email', 'email')}${field('login', 'password', 'Password', 'password')}
    <button name="intent" value="login">Log in</button>
    <button name="intent" value="skip" formnovalidate>Skip checks</button>
    <button type="reset">Reset</button>
</form>`;

async function fixture(page: Page, options: { layered?: boolean; manual?: boolean; attributes?: string; contact?: boolean } = {}) {
    const requests: string[] = [];
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('request', request => { if (request.method() === 'POST') requests.push(request.url()); });
    await page.route('http://craft-forms.test/**', async route => {
        const url = new URL(route.request().url());
        if (url.pathname.startsWith('/assets/') || url.pathname.startsWith('/browser/')) {
            const base = url.pathname.startsWith('/assets/')
                ? `src/web/assets/frontend/dist/${options.layered ? 'layered/' : ''}`
                : 'packages/formie-browser/dist/';
            const relative = url.pathname.replace(/^\/(assets|browser)\//, '');
            await route.fulfill({ body: await readFile(path.resolve(base, relative)), contentType: 'text/javascript' });
        } else if (route.request().method() === 'POST') {
            await route.fulfill({ contentType: 'text/html', body: '<p>Craft received the form.</p>' });
        } else {
            await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><body>
                ${login(options.attributes)}
                ${options.contact ? `<form id="contact" data-formie data-formie-validation-on-submit="true" data-formie-submit-method="ajax">${field('contact', 'fullName', 'Contact name')}<button>Send contact</button></form>` : ''}
                <script type="module">${options.manual
                    ? `import { craftForms, setFormieTranslations } from '/browser/index.js'; window.craftApi = { craftForms, setFormieTranslations }; window.craftApp = craftForms({ observe: true });`
                    : `window.pluginStartup = await import('/assets/js/formie.js');`}
                </script></body></html>` });
        }
    });
    await page.goto('http://craft-forms.test/');
    await expect(page.locator('#login')).toHaveAttribute('novalidate');
    return { requests, errors };
}

for (const layered of [false, true]) {
    test(`production ${layered ? 'layered' : 'unlayered'} startup validates inline and preserves native POST`, async ({ page }) => {
        const { requests, errors } = await fixture(page, { layered, contact: true });
        await page.getByRole('button', { name: 'Log in', exact: true }).click();
        await expect(page.locator('#login [data-formie-field-error]')).toHaveCount(2);
        await expect(page.getByLabel('Email', { exact: true })).toBeFocused();
        await expect(page.locator('#login-loginName-errors')).toHaveClass('formie-field-errors');
        await expect(page.getByLabel('Email', { exact: true })).toHaveClass('custom-input-error');
        await expect(page.locator('#login-loginName-errors .custom-error')).toHaveText('Email cannot be blank.');
        await expect(page.getByLabel('Email', { exact: true })).toHaveAttribute('aria-describedby', 'login-loginName-errors-1');
        await page.getByLabel('Email', { exact: true }).fill('invalid');
        await expect(page.locator('#login-loginName-errors')).not.toHaveText('');
        await page.getByLabel('Email', { exact: true }).fill('alex@example.test');
        await page.getByLabel('Password', { exact: true }).fill('example-password');
        await expect(page.locator('#login [data-formie-field-error]')).toHaveCount(0);
        await page.getByRole('button', { name: 'Send contact' }).click();
        await expect(page.locator('#contact [data-formie-field-error]')).toHaveCount(1);
        expect(requests).toEqual([]);
        const post = page.waitForRequest(request => request.method() === 'POST');
        await page.getByRole('button', { name: 'Log in', exact: true }).click();
        const request = await post;
        expect(request.url()).toBe('http://craft-forms.test/login');
        expect(Object.fromEntries(new URLSearchParams(request.postData()!))).toEqual({
            action: 'users/login', CRAFT_CSRF_TOKEN: 'csrf-fixture', redirect: 'signed-redirect-fixture',
            loginName: 'alex@example.test', password: 'example-password', intent: 'login',
        });
        await expect(page.getByText('Craft received the form.')).toBeVisible();
        expect(requests).toEqual(['http://craft-forms.test/login']);
        expect(errors).toEqual([]);
    });
}

test('keyboard submission validates and allows the original default submitter', async ({ page }) => {
    await fixture(page);
    await page.getByLabel('Password', { exact: true }).press('Enter');
    await expect(page.locator('#login [data-formie-field-error]')).toHaveCount(2);
    await page.getByLabel('Email', { exact: true }).fill('alex@example.test');
    await page.getByLabel('Password', { exact: true }).fill('example-password');
    const post = page.waitForRequest(request => request.method() === 'POST');
    await page.getByLabel('Password', { exact: true }).press('Enter');
    expect(new URLSearchParams((await post).postData()!).get('intent')).toBe('login');
});

for (const optOut of ['button', 'form', 'setting']) {
    test(`honours ${optOut} validation opt-out`, async ({ page }) => {
        await fixture(page, { attributes: optOut === 'form' ? 'novalidate="original"' : '' });
        if (optOut === 'setting') await page.locator('#login').evaluate(form => form.setAttribute('data-formie-validation-on-submit', 'false'));
        const post = page.waitForRequest(request => request.method() === 'POST');
        await page.getByRole('button', { name: optOut === 'button' ? 'Skip checks' : 'Log in', exact: true }).click();
        expect(new URLSearchParams((await post).postData()!).get('loginName')).toBe('');
    });
}

test('manual mounting is idempotent, translates errors and cleans up removed forms', async ({ page }) => {
    await fixture(page, { manual: true, attributes: 'data-formie-validation-on-focus="true"' });
    await page.evaluate(() => {
        const w = window as any;
        w.craftApi.setFormieTranslations({ '{label} cannot be blank.': '{label} is required here.' });
        w.craftApp.rescan();
        w.otherApp = w.craftApi.craftForms();
        w.originalValidator = w.craftApp.get(document.querySelector('#login'));
        if (w.originalValidator !== w.otherApp.get(document.querySelector('#login'))) throw new Error('Duplicate validator');
        w.otherApp.destroy();
    });
    await page.getByLabel('Email', { exact: true }).fill('bad');
    await page.getByLabel('Password', { exact: true }).click();
    await expect(page.locator('#login-loginName-errors [data-formie-field-error]')).toHaveCount(1);
    await page.getByRole('button', { name: 'Log in', exact: true }).click();
    await expect(page.locator('#login-password-errors')).toHaveText('Password is required here.');
    await page.getByRole('button', { name: 'Reset', exact: true }).click();
    await expect(page.locator('#login [data-formie-field-error]')).toHaveCount(0);
    await page.evaluate(() => { (window as any).removedForm = document.querySelector('#login'); (window as any).removedForm.remove(); });
    await expect.poll(() => page.evaluate(() => (window as any).removedForm.hasAttribute('novalidate'))).toBe(false);
    expect(await page.evaluate(() => (window as any).originalValidator.boundListeners)).toBe(false);
    await page.evaluate(() => document.body.append((window as any).removedForm));
    await expect(page.locator('#login')).toHaveAttribute('novalidate');
    await page.evaluate(() => (window as any).craftApp.destroy());
    await expect(page.locator('#login')).not.toHaveAttribute('novalidate');
});

test('startup observes inserted Craft forms and restores native validation on removal', async ({ page }) => {
    await fixture(page);
    await page.evaluate(() => {
        const second = document.querySelector('#login')!.cloneNode(true) as HTMLFormElement;
        second.id = 'second';
        second.removeAttribute('novalidate');
        second.querySelectorAll('[id]').forEach(node => node.id = `second-${node.id}`);
        document.body.append(second);
    });
    await expect(page.locator('#second')).toHaveAttribute('novalidate');
    await page.locator('#second').getByRole('button', { name: 'Log in', exact: true }).click();
    await expect(page.locator('#second [data-formie-field-error]')).toHaveCount(2);
    await expect(page.locator('#login [data-formie-field-error]')).toHaveCount(0);
    await page.evaluate(() => { (window as any).removed = Array.from(document.querySelectorAll('form')); (window as any).removed.forEach((form: HTMLFormElement) => form.remove()); });
    await expect.poll(() => page.evaluate(() => (window as any).removed.every((form: HTMLFormElement) => !form.noValidate))).toBe(true);
});

test('without JavaScript required inputs retain browser validation', async ({ browser }) => {
    const context = await browser.newContext({ javaScriptEnabled: false });
    const page = await context.newPage();
    await page.setContent(login());
    await page.getByRole('button', { name: 'Log in', exact: true }).click();
    await expect(page.getByLabel('Email', { exact: true })).toBeFocused();
    await expect(page.locator('#login')).not.toHaveAttribute('novalidate');
    await expect(page.locator('[data-formie-field-error]')).toHaveCount(0);
    await context.close();
});

test('deferred initialization, custom messages and requestSubmit preserve submitter overrides', async ({ page }) => {
    await fixture(page, { manual: true });
    await page.evaluate(() => {
        const w = window as any;
        const form = document.querySelector('#login') as HTMLFormElement;
        w.craftApp.destroy();
        form.dataset.formieInit = 'false';
        w.craftApp = w.craftApi.craftForms({ observe: true });
    });
    await expect(page.locator('#login')).not.toHaveAttribute('novalidate');
    await page.locator('#login').evaluate(form => form.removeAttribute('data-formie-init'));
    await expect(page.locator('#login')).toHaveAttribute('novalidate');
    await page.getByLabel('Email', { exact: true }).evaluate(input => input.setAttribute('data-formie-required-message', 'Please enter your login email.'));
    await page.getByRole('button', { name: 'Log in', exact: true }).click();
    await expect(page.locator('#login-loginName-errors')).toHaveText('Please enter your login email.');
    await page.getByLabel('Email', { exact: true }).fill('alex@example.test');
    await page.getByLabel('Password', { exact: true }).fill('example-password');
    const post = page.waitForRequest(request => request.method() === 'POST');
    // Exercise the programmatic API as well as the real button/keyboard flows.
    await page.evaluate(() => {
        const form = document.querySelector('#login') as HTMLFormElement;
        const button = form.querySelector('button')!;
        button.formAction = '/alternate-login';
        form.requestSubmit(button);
    });
    const request = await post;
    expect(request.url()).toBe('http://craft-forms.test/alternate-login');
    expect(new URLSearchParams(request.postData()!).get('intent')).toBe('login');
});

test('teardown restores an existing novalidate attribute exactly', async ({ page }) => {
    await fixture(page, { manual: true, attributes: 'novalidate="original"' });
    await page.evaluate(() => (window as any).craftApp.destroy());
    await expect(page.locator('#login')).toHaveAttribute('novalidate', 'original');
});
