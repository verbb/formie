import { test, expect } from '@playwright/test';

test('creates a form in the control panel and preserves its field after reload', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByRole('textbox', { name: 'Username or Email', exact: true }).fill('admin');
    await page.locator('input[name="password"]').fill('testing-only-password');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
    await page.goto('/admin/formie/forms/new');
    await page.locator('pk-field[data-name="title"]').getByRole('textbox').fill(`Browser authored ${Date.now()}`);
    await page.getByRole('button', { name: 'Next', exact: true }).click();
    await page.getByRole('button', { name: 'Add Single-line Text', exact: true }).dblclick();
    // Scope through the dialog host because its controls are slotted into shadow DOM.
    const dialog = page.locator('.formie-field-edit-dialog');
    await expect(dialog.getByRole('button', { name: 'Apply', exact: true })).toBeVisible();
    await dialog.locator('pk-field[data-name="label"]').getByRole('textbox').fill('Delivery instructions');
    await dialog.locator('pk-field[data-name="placeholder"]').getByRole('textbox').fill('Where should we leave it?');
    await dialog.getByRole('button', { name: 'Apply', exact: true }).click();
    await expect(dialog).not.toBeVisible();
    await page.getByRole('button', { name: 'Save', exact: true }).click();
    await expect(page.locator('#notifications').getByText('Form saved.', { exact: true })).toBeVisible();
    await page.reload();
    // Craft may generate query routes while the server also accepts pretty URLs.
    const currentUrl = new URL(page.url());
    const pathParam = await page.evaluate(() => (window as any).Craft.pathParam || 'p');
    const route = currentUrl.searchParams.get(pathParam);
    if (route) {
        await page.goto(new URL(route, currentUrl).toString());
    }
    await page.getByRole('button', { name: 'Edit Delivery instructions', exact: true }).click();
    await expect(dialog.locator('pk-field[data-name="label"]').getByRole('textbox')).toHaveValue('Delivery instructions');
    await expect(dialog.locator('pk-field[data-name="placeholder"]').getByRole('textbox')).toHaveValue('Where should we leave it?');
});

test('preserves a dynamic payment amount field after reload', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByRole('textbox', { name: 'Username or Email', exact: true }).fill('admin');
    await page.locator('input[name="password"]').fill('testing-only-password');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
    await page.goto('/admin/formie/forms/new');
    await page.locator('pk-field[data-name="title"]').getByRole('textbox').fill(`Browser payment ${Date.now()}`);
    await page.getByRole('button', { name: 'Next', exact: true }).click();

    const dialog = page.locator('.formie-field-edit-dialog');
    await page.getByRole('button', { name: 'Add Number', exact: true }).dblclick();
    await dialog.locator('pk-field[data-name="label"]').getByRole('textbox').fill('Payment total');
    await dialog.getByRole('button', { name: 'Apply', exact: true }).click();

    await page.getByRole('button', { name: 'Add Payment', exact: true }).dblclick();
    await dialog.locator('pk-field[data-name="label"]').getByRole('textbox').fill('Card payment');
    await dialog.getByRole('button', { name: 'Select an option', exact: true }).first().click();
    await page.getByRole('option', { name: 'Browser Stripe', exact: true }).click();
    await dialog.getByRole('button', { name: 'Fixed Value', exact: true }).first().click();
    await page.getByRole('option', { name: 'Dynamic Value', exact: true }).click();
    await dialog.getByRole('combobox').first().click();
    await page.getByRole('option', { name: 'Payment total', exact: true }).click();
    await expect(dialog.getByRole('button', { name: 'Dynamic Value', exact: true }).first()).toBeVisible();
    await expect(dialog.getByRole('combobox').first()).toHaveValue('Payment total');
    await dialog.getByRole('button', { name: 'Apply', exact: true }).click();

    await page.getByRole('button', { name: 'Edit Card payment', exact: true }).click();
    await expect(dialog.getByRole('button', { name: 'Dynamic Value', exact: true }).first()).toBeVisible();
    await expect(dialog.getByRole('combobox').first()).toHaveValue('Payment total');
    await dialog.getByRole('button', { name: 'Apply', exact: true }).click();

    await page.getByRole('button', { name: 'Save', exact: true }).click();
    await expect(page.locator('#notifications').getByText('Form saved.', { exact: true })).toBeVisible();
    await page.reload();
    await page.getByRole('button', { name: 'Edit Card payment', exact: true }).click();
    await expect(dialog.getByRole('button', { name: 'Dynamic Value', exact: true }).first()).toBeVisible();
    await expect(dialog.getByRole('combobox').first()).toHaveValue('Payment total');
});
