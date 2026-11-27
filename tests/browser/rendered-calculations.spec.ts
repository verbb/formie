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

// Conditions clear a hidden field's answer on the server, so a calculation ignores it in the browser too. A row
// picked by position follows deleted rows, as on the server.
test('server-rendered Calculations fields skip hidden sources and read Repeater rows by position', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('console', message => {
        if (message.type() === 'error') errors.push(message.text());
    });
    await page.goto('/browser-rendered?form=adapterHiddenCalculations');

    const total = page.locator('[name="fields[total]"]');
    await page.locator('[name="fields[base]"]').fill('10');

    // Extra starts hidden with its default of 5, and `10 + ''` joins as text in the browser, as on the server.
    await expect(total).toHaveValue('10');

    // Five fields share the row, which overlaps the radios for pointer clicks, so pick the plan directly.
    const choosePlan = (value: string) => page.locator(`[name="fields[plan]"][value="${value}"]`).evaluate((input: HTMLInputElement) => {
        input.checked = true;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    await choosePlan('pro');
    await expect(total).toHaveValue('15');

    await choosePlan('basic');
    await expect(total).toHaveValue('10');

    const qty = page.locator('[name^="fields[items]"][name$="[qty]"]');
    await page.locator('[data-formie-repeater-add="items"]').click();
    await expect(qty).toHaveCount(2);
    await qty.nth(0).fill('4');
    await qty.nth(1).fill('7');
    await expect(page.locator('[name="fields[firstQty]"]')).toHaveValue('4');

    await page.locator('[data-formie-repeater-remove]').first().click();
    await expect(qty).toHaveCount(1);
    await expect(page.locator('[name="fields[firstQty]"]')).toHaveValue('7');
    expect(errors).toEqual([]);
});
