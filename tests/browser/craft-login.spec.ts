import { test, expect } from '@playwright/test';

test('Craft login displays authentication errors and redirects an authenticated session', async ({ page }) => {
    const formieRequests: string[] = [];
    page.on('request', request => {
        if (request.url().includes('/actions/formie/')) formieRequests.push(request.url());
    });
    await page.goto('/browser-craft-login');
    const form = page.locator('form[data-formie-craft-form]');
    await expect(form).toHaveAttribute('novalidate');
    await expect(form).toHaveClass(/formie-form/);
    await form.getByRole('button', { name: 'Submit', exact: true }).click();
    await expect(form.locator('[data-formie-field-error]')).toHaveCount(2);
    await expect(form.locator('[data-formie-field-errors]').first()).not.toHaveCSS('position', 'absolute');
    await expect(form.locator('[data-formie-field-error]').first()).not.toHaveCSS('color', 'rgb(0, 0, 0)');
    await page.screenshot({ path: test.info().outputPath('login-validation.png') });
    await expect(page.getByRole('textbox', { name: 'Login name', exact: true })).toBeFocused();
    await page.getByRole('textbox', { name: 'Login name', exact: true }).fill('browserCraftLogin');
    await page.getByLabel(/^Password/).fill('wrong-password');
    await form.getByRole('button', { name: 'Submit', exact: true }).click();
    await expect(page.getByRole('alert')).toBeVisible();
    await expect(page.getByLabel(/^Password/)).toHaveValue('');
    await page.getByRole('textbox', { name: 'Login name', exact: true }).fill('browserCraftLogin');
    await page.getByLabel(/^Password/).fill('testing-only-craft-login-password');
    await form.getByRole('button', { name: 'Submit', exact: true }).click();
    await expect(page).toHaveURL(/\/browser-craft-login-done$/);
    await expect(page.getByText('Logged in as browserCraftLogin', { exact: true })).toBeVisible();
    expect(formieRequests).toEqual([]);
});
