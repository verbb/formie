import { test, expect } from '@playwright/test';

test('authors redirect variables with an advisory warning and preserves them after saving', async ({ page }) => {
    test.setTimeout(60000);
    await page.goto('/admin/login');
    await page.getByRole('textbox', { name: 'Username or Email', exact: true }).fill('admin');
    await page.locator('input[name="password"]').fill('testing-only-password');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
    await page.goto('/admin/formie/forms/new');
    await page.locator('pk-field[data-name="title"]').getByRole('textbox').fill(`Browser redirect ${Date.now()}`);
    await page.getByRole('button', { name: 'Next', exact: true }).click();
    await page.getByRole('button', { name: 'Add Single-line Text', exact: true }).dblclick();
    const dialog = page.locator('.formie-field-edit-dialog');
    await dialog.locator('pk-field[data-name="label"]').getByRole('textbox').fill('Destination');
    await dialog.getByRole('button', { name: 'Apply', exact: true }).click();
    await expect(dialog).not.toBeVisible();
    await page.getByRole('tab', { name: 'Behaviour', exact: true }).click();
    const save = async () => {
        const response = page.waitForResponse(r => r.request().method() === 'POST' && decodeURIComponent(r.url()).includes('formie/forms/save'));
        await page.getByRole('button', { name: 'Save', exact: true }).click();
        expect((await response).ok()).toBe(true);
        await expect(page.locator('#notifications').getByText('Form saved.', { exact: true })).toBeVisible();
    };
    await page.locator('pk-field[data-name="settings.completionBehavior"]').getByRole('button').click();
    await page.getByRole('option', { name: 'Redirect', exact: true }).click();

    const urlField = page.locator('pk-field[data-name="settings.redirectUrl"]');
    const warning = urlField.getByText(/This URL starts with a variable/);
    await urlField.getByRole('textbox').fill('https://buy.stripe.com/example');
    await expect(warning).not.toBeVisible();
    await save();

    await urlField.getByRole('textbox').fill('');
    await urlField.getByRole('button', { name: 'Variables', exact: true }).click();
    await page.getByRole('listbox').filter({ visible: true }).getByRole('button', { name: 'Destination', exact: true }).click();
    await expect(warning).toBeVisible();
    await save();
    await page.reload();
    await page.getByRole('tab', { name: 'Behaviour', exact: true }).click();
    await expect(warning).toBeVisible();
    await expect(urlField.getByRole('button', { name: 'Destination', exact: true })).toBeVisible();

    await urlField.getByRole('textbox').fill('https://example.test/thanks?name={field:destination}');
    await expect(warning).not.toBeVisible();
    await page.locator('pk-field[data-name="settings.enableRedirectRules"]').getByRole('switch').click();
    await page.getByRole('button', { name: 'Add redirect rule', exact: true }).click();
    const ruleUrl = page.locator('pk-field[data-name="settings.redirectRules.0.redirectUrl"]');
    await ruleUrl.getByRole('button', { name: 'Variables', exact: true }).click();
    await page.getByRole('listbox').filter({ visible: true }).getByRole('button', { name: 'Destination', exact: true }).click();
    await expect(ruleUrl.getByText(/This URL starts with a variable/)).toBeVisible();
});
