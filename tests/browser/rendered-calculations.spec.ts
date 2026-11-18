import { test, expect } from '@playwright/test';

// Server-rendered forms with published assets, using the adapterNestedCalculations fixture form.
test('server-rendered Calculations fields in Groups and Repeater rows read their own row', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('console', message => {
        if (message.type() === 'error') errors.push(message.text());
    });
    await page.goto('/browser-rendered?form=adapterNestedCalculations');

    const qty = page.locator('[name^="fields[items]"][name$="[qty]"]');
    const rowTotals = page.locator('[name^="fields[items]"][name$="[rowTotal]"]');
    await expect(qty).toHaveCount(2);

    await page.locator('[name="fields[rate]"]').fill('3');
    await page.locator('[name="fields[details][hours]"]').fill('2');
    await qty.nth(0).fill('4');
    await qty.nth(1).fill('5');

    await expect(page.locator('[name="fields[details][groupTotal]"]')).toHaveValue('6');
    await expect(rowTotals.nth(0)).toHaveValue('12');
    await expect(rowTotals.nth(1)).toHaveValue('15');

    // A row added in the browser works out its own total, and every row follows a change outside the Repeater.
    await page.locator('[data-formie-repeater-add="items"]').click();
    await expect(qty).toHaveCount(3);
    await qty.nth(2).fill('6');
    await expect(rowTotals.nth(2)).toHaveValue('18');

    await page.locator('[name="fields[rate]"]').fill('2');
    await expect(rowTotals.nth(0)).toHaveValue('8');
    await expect(rowTotals.nth(1)).toHaveValue('10');
    await expect(rowTotals.nth(2)).toHaveValue('12');
    await expect(page.locator('[name="fields[details][groupTotal]"]')).toHaveValue('4');
    expect(errors).toEqual([]);
});
