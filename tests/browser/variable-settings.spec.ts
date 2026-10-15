import { test, expect } from '@playwright/test';

test('switches variable settings between chips without closing or mixing drafts', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByRole('textbox', { name: 'Username or Email', exact: true }).fill('admin');
    await page.locator('input[name="password"]').fill('testing-only-password');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
    await page.goto('/admin/formie/forms/new');
    await page.locator('pk-field[data-name="title"]').getByRole('textbox').fill(`Browser variable settings ${Date.now()}`);
    await page.getByRole('button', { name: 'Next', exact: true }).click();
    await page.getByRole('button', { name: 'Add Single-line Text', exact: true }).dblclick();
    const fieldDialog = page.locator('.formie-field-edit-dialog');
    await fieldDialog.locator('pk-field[data-name="label"]').getByRole('textbox').fill('Testing');
    await fieldDialog.getByRole('button', { name: 'Apply', exact: true }).click();
    await expect(fieldDialog).not.toBeVisible();
    await page.getByRole('tab', { name: 'Email Notifications', exact: true }).click();
    await page.getByRole('button', { name: 'New Notification', exact: true }).click();

    for (const label of ['Testing', 'All Form Fields']) {
        await page.getByRole('button', { name: 'Variables', exact: true }).click();
        await page.getByRole('button', { name: label, exact: true }).click();
        await expect(page.getByPlaceholder('Search variables').filter({ visible: true })).toHaveCount(0);
    }

    const firstChip = page.getByRole('button', { name: 'Testing', exact: true });
    const secondChip = page.getByRole('button', { name: 'All Form Fields', exact: true });
    const settings = page.getByRole('dialog', { name: 'Configure variable', exact: true });
    const defaultInput = settings.getByRole('textbox').first();
    // The close notification arrives after the exit animation, so checking only
    // the first visible frame would miss the original flicker-and-close bug.
    const expectSettledSettings = async () => {
        await expect(settings).toBeVisible();
        await page.waitForTimeout(500);
        await expect(settings).toBeVisible();
    };

    await firstChip.click();
    await expect(defaultInput).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(settings.getByRole('button', { name: 'None', exact: true })).toBeFocused();
    await page.keyboard.press('Shift+Tab');
    await expect(defaultInput).toBeFocused();
    await page.keyboard.press('Shift+Tab');
    await expect(settings.getByRole('button', { name: 'Save', exact: true })).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(defaultInput).toBeFocused();
    await page.keyboard.press('Tab');
    await page.keyboard.press('Enter');
    await expect(page.getByRole('option', { name: 'Lowercase', exact: true })).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(settings).toBeVisible();
    await expect(settings.getByRole('button', { name: 'None', exact: true })).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(settings.getByRole('button', { name: 'Change variable', exact: true })).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(settings.getByRole('button', { name: 'Save', exact: true })).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(defaultInput).toBeFocused();
    await defaultInput.fill('Discard first draft');
    await secondChip.click();
    await expectSettledSettings();
    await expect(defaultInput).toBeFocused();
    await expect(defaultInput).toHaveValue('');
    await defaultInput.fill('Second fallback');

    await secondChip.click();
    await expectSettledSettings();
    await expect(defaultInput).toHaveValue('Second fallback');
    await settings.getByRole('button', { name: 'Save', exact: true }).click();
    await expect(settings).not.toBeVisible();
    await expect(secondChip).toBeFocused();

    await secondChip.click();
    await expect(defaultInput).toHaveValue('Second fallback');
    await firstChip.click();
    await expectSettledSettings();
    await expect(defaultInput).toHaveValue('');

    await settings.getByRole('button', { name: 'None', exact: true }).click();
    await page.getByRole('option', { name: 'Lowercase', exact: true }).click();
    await expectSettledSettings();
    await settings.getByRole('button', { name: 'Save', exact: true }).click();
    await firstChip.click();
    await expect(settings.getByRole('button', { name: 'Lowercase', exact: true })).toBeVisible();

    await page.keyboard.press('Escape');
    await expect(settings).not.toBeVisible();
    await expect(firstChip).toBeFocused();
    await secondChip.click();
    await expectSettledSettings();
    await expect(defaultInput).toHaveValue('Second fallback');
    await page.getByRole('heading', { name: 'New Notification', exact: true }).click();
    await expect(settings).not.toBeVisible();
    await firstChip.click();
    await expectSettledSettings();

    // Changing the reference previews the chip immediately. Abandoning that
    // preview by switching chips must restore the original reference.
    await settings.getByRole('button', { name: 'Change variable', exact: true }).click();
    await expect(settings.getByPlaceholder('Search variables')).toBeFocused();
    await settings.getByRole('button', { name: 'Form Handle', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Form Handle', exact: true })).toBeVisible();
    await expect(defaultInput).toBeFocused();
    await secondChip.click();
    await expectSettledSettings();
    await expect(firstChip).toBeVisible();
    await expect(defaultInput).toHaveValue('Second fallback');
});
