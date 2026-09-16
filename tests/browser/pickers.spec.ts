import { test, expect } from '@playwright/test';

for (const kind of ['field', 'notification']) {
    test(`copies an existing ${kind} with the keyboard and preserves it after reload`, async ({ page }) => {
        await page.goto('/admin/login');
        await page.getByRole('textbox', { name: 'Username or Email', exact: true }).fill('admin');
        await page.locator('input[name="password"]').fill('testing-only-password');
        await page.getByRole('button', { name: 'Sign in', exact: true }).click();
        await page.waitForURL(url => !url.pathname.endsWith('/login'));
        await page.goto('/admin/formie/forms/new');
        await page.locator('pk-field[data-name="title"]').getByRole('textbox').fill(`Browser copied ${kind} ${Date.now()}`);
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        if (kind === 'field') {
            await page.getByRole('button', { name: 'Add existing fields', exact: true }).click();
        } else {
            await page.getByRole('tab', { name: 'Email Notifications', exact: true }).click();
            const actions = page.locator('pk-button-group').filter({ has: page.getByRole('button', { name: 'New Notification', exact: true }) });
            await actions.getByRole('button', { name: 'Open menu', exact: true }).click();
            await page.getByRole('menuitem', { name: 'Select existing notification', exact: true }).click();
        }
        const picker = page.locator(kind === 'field' ? '.formie-existing-fields-dialog' : '.formie-existing-notifications-dialog');
        await picker.getByRole('button', { name: 'Browser contract', exact: true }).click();
        const label = picker.getByText(kind === 'field' ? 'Visitor name' : 'Browser notification', { exact: true });
        await label.click();
        await expect(picker.getByRole('button', { name: kind === 'field' ? 'Add 1 as new field' : 'Add 1 notification', exact: true })).toBeEnabled();
        await label.click();
        const choice = picker.getByRole('checkbox', { name: kind === 'field' ? /Visitor name/ : /Browser notification/ });
        await expect(choice).toBeVisible();
        await picker.getByRole('textbox', { name: 'Search', exact: true }).focus();
        for (let i = 0; i < 30 && !(await choice.evaluate(el => el.matches(':focus'))); i++) {
            await page.keyboard.press('Tab');
        }
        await expect(choice).toBeFocused();
        await page.keyboard.press('Space');
        await expect(choice).toBeChecked();
        await page.keyboard.press('Space');
        await expect(choice).not.toBeChecked();
        await page.keyboard.press('Space');
        await picker.getByRole('button', { name: kind === 'field' ? 'Add 1 as new field' : 'Add 1 notification', exact: true }).click();
        await expect(picker).not.toBeVisible();
        await page.getByRole('button', { name: 'Save', exact: true }).click();
        await expect(page.locator('#notifications').getByText('Form saved.', { exact: true })).toBeVisible();
        await page.reload();
        if (kind === 'field') {
            await expect(page.getByRole('button', { name: 'Edit Visitor name', exact: true })).toBeVisible();
        } else {
            await page.getByRole('tab', { name: 'Email Notifications', exact: true }).click();
            await expect(page.getByText('Browser notification', { exact: true })).toBeVisible();
        }
    });
}
