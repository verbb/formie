import { test, expect } from '@playwright/test';

test('shows assigned forms and submission sources without permission to create forms', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByRole('textbox', { name: 'Username or Email', exact: true }).fill('browserScopedEditor');
    await page.locator('input[name="password"]').fill('testing-only-password');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
    await page.goto('/admin/formie/forms');
    await expect(page.getByRole('link', { name: 'Scoped role form', exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Other role form', exact: true })).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'New Form', exact: true })).toHaveCount(0);
    await page.goto('/admin/formie/submissions');
    const sources = page.locator('[data-key^="form:"]');
    await expect(sources.filter({ hasText: 'Scoped role form' })).toBeVisible();
    await expect(sources.filter({ hasText: 'Other role form' })).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'New submission', exact: true })).toBeVisible();
    const editable = await page.evaluate(() => (window as any).Craft.Formie.editableForms.map((form: { handle: string }) => form.handle));
    expect(editable).toEqual(['scopedRoleForm']);

    await page.getByRole('button', { name: 'New submission', exact: true }).click();
    const title = `Create-only submission ${Date.now()}`;
    await page.getByRole('textbox', { name: 'Title', exact: true }).fill(title);
    await page.locator('[name="fields[message]"]').fill('Created without edit permission');
    await page.getByRole('button', { name: 'Save', exact: true }).click();
    await page.getByRole('link', { name: title, exact: true }).click();
    await expect(page.locator('[name="fields[message]"]')).toHaveValue('Created without edit permission');
    await expect(page.getByRole('button', { name: 'Save', exact: true })).toHaveCount(0);
});
