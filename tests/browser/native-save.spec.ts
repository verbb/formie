import { test, expect } from '@playwright/test';

for (const mode of ['default', 'none']) {
    test(`native Save exposes a portable link with ${mode} completion notices`, async ({ page, browser, request }) => {
        const marker = `Native ${mode} ${Date.now()}`;
        await page.goto(`/browser-native-save?mode=${mode}`);
        const main = page.locator('#saved-form');
        await main.getByRole('textbox', { name: 'Visitor name', exact: true }).fill(marker);
        await page.waitForFunction(() => Date.now() - Number((document.querySelector('#saved-form input[name="formStartedAt"]') as HTMLInputElement)?.value) >= 3500);
        await main.getByRole('button', { name: 'Next', exact: true }).click();
        await expect(main.getByRole('textbox', { name: 'Confirmation', exact: true })).toBeVisible();
        await main.getByRole('button', { name: 'Save', exact: true }).click();
        const link = main.getByRole('link', { name: 'Save this link to continue later.', exact: true });
        await expect(link).toBeVisible();
        await expect(page).not.toHaveURL(/resumeToken=/);
        await expect(main.getByText('Your progress has been saved.', { exact: true })).toBeVisible();
        await expect(page.locator('#unrelated').getByRole('link')).toHaveCount(0);
        const resumeUrl = await link.getAttribute('href');
        expect(new URL(resumeUrl!).searchParams.get('resumeToken')).toBeTruthy();
        const savedRows = await (await request.get('/browser-native-save-records')).json();
        const saved = savedRows.filter((row: {name: string}) => row.name === marker);
        expect(saved).toHaveLength(1);
        expect(saved[0].isIncomplete).toBe(true);

        const fresh = await browser.newContext();
        try {
            const resumed = await fresh.newPage();
            await resumed.goto(resumeUrl!);
            await expect(resumed).not.toHaveURL(/resumeToken=/);
            const resumedForm = resumed.locator('#saved-form');
            await expect(resumedForm.getByRole('textbox', { name: 'Confirmation', exact: true })).toBeVisible();
            await expect(resumedForm.getByRole('link', { name: 'Save this link to continue later.', exact: true })).toHaveCount(0);
            await resumedForm.getByRole('button', { name: 'Back', exact: true }).click();
            await expect(resumedForm.getByRole('textbox', { name: 'Visitor name', exact: true })).toHaveValue(marker);
            await resumed.waitForFunction(() => Date.now() - Number((document.querySelector('#saved-form input[name="formStartedAt"]') as HTMLInputElement)?.value) >= 3500);
            await resumedForm.getByRole('button', { name: 'Next', exact: true }).click();
            await resumedForm.getByRole('textbox', { name: 'Confirmation', exact: true }).fill('Finished in a fresh browser');
            await resumed.waitForFunction(() => Date.now() - Number((document.querySelector('#saved-form input[name="formStartedAt"]') as HTMLInputElement)?.value) >= 3500);
            await resumedForm.getByRole('button', { name: 'Submit', exact: true }).click();
            await expect.poll(async () => {
                const rows = await (await request.get('/browser-native-save-records')).json();
                return rows.filter((row: {name: string}) => row.name === marker);
            }).toEqual([{id: saved[0].id, isIncomplete: false, name: marker, confirmation: 'Finished in a fresh browser'}]);
            if (mode === 'none') {
                await expect(resumedForm.getByText('Submission complete.', { exact: true })).toHaveCount(0);
            } else {
                await expect(resumedForm.getByText('Submission complete.', { exact: true })).toBeVisible();
            }
        } finally {
            await fresh.close();
        }
    });
}
