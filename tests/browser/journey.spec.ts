import { test, expect } from '@playwright/test';

test('multi-page values, repeater and upload survive back navigation and duplicate clicks', async ({ page, request }) => {
    await page.goto('/browser-fixture?adapter=react&form=browserJourney');
    const before = await (await request.get('/browser-saved?journey=1')).json();
    await page.getByLabel('Visitor name', { exact: false }).fill('Journey visitor');
    await page.locator('#host button[type="submit"]').click();
    await page.getByRole('button', { name: 'Add another row', exact: true }).click();
    await page.getByLabel('Item name', { exact: false }).last().fill('Keep this item');
    await page.locator('input[type="file"]').setInputFiles({ name: 'journey.txt', mimeType: 'text/plain', buffer: Buffer.from('Formie browser upload contract') });
    await page.getByRole('button', { name: /back|previous/i }).click();
    await expect(page.getByLabel('Visitor name', { exact: false })).toHaveValue('Journey visitor');
    await page.locator('#host button[type="submit"]').click();
    await expect(page.getByLabel('Item name', { exact: false }).last()).toHaveValue('Keep this item');
    await expect(page.locator('#host')).toContainText('journey.txt');
    await page.locator('#host button[type="submit"]').evaluate((button: HTMLButtonElement) => { button.click(); button.click(); });
    await expect(page.locator('#host')).toContainText('Submission saved.');
    await expect.poll(async () => (await (await request.get('/browser-saved?journey=1')).json()).length).toBe(before.length + 1);
    const saved = await (await request.get('/browser-saved?journey=1')).json();
    expect(saved.filter(row => !before.some(old => old.id === row.id))).toEqual([
        { id: expect.any(Number), name: 'Journey visitor', items: [{ itemName: 'Keep this item' }], files: [{ filename: expect.stringMatching(/^journey(?:_[a-zA-Z0-9_-]+)?\.txt$/), contents: 'Formie browser upload contract' }] },
    ]);
});
