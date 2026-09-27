import { test, expect } from '@playwright/test';

test('server-rendered theme config uses the canonical manifest and escapes text', async ({ page }) => {
    await page.goto('/browser-theme?theme=formie');

    const form = page.locator('form[data-formie]');
    await expect(form).toHaveClass(/browser-theme-config/);
    await expect(form).toHaveAttribute('data-formie-theme-classes', /fieldControlError/);
    await expect(page.locator('link[href*="formie-base.css"]')).toHaveCount(1);
    await expect(page.locator('link[href*="formie-theme.css"]')).toHaveCount(1);
    await expect(page.getByText('<Theme>', { exact: true }).first()).toBeVisible();
    expect(await page.locator('script').evaluateAll(nodes => nodes.some(node => node.textContent?.includes('<Theme>')))).toBe(false);

    await page.getByRole('button', { name: 'Submit', exact: true }).click();
    const input = page.locator('input[name="fields[visitorName]"]');
    await expect(input).toHaveAttribute('aria-invalid', 'true');
    await expect(input).toHaveClass(/browser-input-error/);
});

test('none theme retains accessible behaviour and base assets without visual theme assets', async ({ page }) => {
    await page.goto('/browser-theme?theme=none');

    const form = page.locator('form[data-formie]');
    await expect(form).toHaveAttribute('method', 'post');
    await expect(form).toHaveAttribute('data-formie', '');
    await expect(form).not.toHaveClass(/formie-form/);
    await expect(page.locator('link[href*="formie-base.css"]')).toHaveCount(1);
    await expect(page.locator('link[href*="formie-theme.css"]')).toHaveCount(0);
    await expect(page.locator('script[data-formie-startup]')).toHaveCount(1);

    await page.getByRole('button', { name: 'Submit', exact: true }).click();
    const input = page.locator('input[name="fields[visitorName]"]');
    await expect(input).toHaveAttribute('aria-invalid', 'true');
    const errorId = await input.getAttribute('aria-errormessage');
    expect(errorId).toBeTruthy();
    await expect(page.locator(`[id="${errorId}"]`)).not.toHaveText('');
});
