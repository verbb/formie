import { test, expect, type Locator, type Page } from '@playwright/test';

async function openForm(page: Page, adapter: string, extra = '') {
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(`/browser-fixture?adapter=${adapter}&form=accessibilityContract${extra}`);
    const host = page.locator('#host');
    await expect(host.getByText('Visitor name').first()).toBeVisible();

    return { host, errors };
}

async function expectNoAttribute(locator: Locator, name: string) {
    expect(await locator.getAttribute(name)).toBeNull();
}

for (const adapter of ['react', 'vue', 'web-components']) {
    // https://github.com/verbb/formie/issues/3140
    test(`${adapter}: links field labels to their controls`, async ({ page }) => {
        const { host, errors } = await openForm(page, adapter);

        for (const label of ['Visitor name', 'Visitor email', 'Message', 'Enquiry']) {
            const control = host.getByLabel(label, { exact: true });
            const id = await control.getAttribute('id');

            expect(id).toBeTruthy();
            await expectNoAttribute(control, 'aria-label');
            await expect(host.locator(`label[for="${id}"]`)).toHaveText(label);

            await page.evaluate(() => (document.activeElement as HTMLElement | null)?.blur());
            await expect(control).not.toBeFocused();
            await host.locator(`label[for="${id}"]`).click();
            await expect(control).toBeFocused();
        }

        await expect(host.getByRole('textbox', { name: 'Visitor name', exact: true })).toHaveAccessibleDescription('As it appears on your ID.');

        const contactMethod = host.getByRole('radiogroup', { name: 'Contact method', exact: true });
        await expect(contactMethod).toHaveAccessibleDescription('Pick one.');
        await expect(contactMethod.getByRole('radio', { name: 'Phone', exact: true })).toBeVisible();
        await expect(host.getByRole('group', { name: 'Topics', exact: true }).getByRole('checkbox', { name: 'News', exact: true })).toBeVisible();

        // As in server-rendered forms, the field label names the group and the description names the checkbox.
        const newsletter = host.getByRole('group', { name: 'Newsletter', exact: true });
        await expect(newsletter.getByRole('checkbox', { name: 'Yes, send me updates.', exact: true })).toBeVisible();

        // Errors and instructions both describe the control once validation fails.
        await host.locator('button[type="submit"]').click();
        const name = host.getByRole('textbox', { name: 'Visitor name', exact: true });
        await expect(name).toHaveAttribute('aria-invalid', 'true');
        const errorId = await name.getAttribute('aria-errormessage');
        expect((await name.getAttribute('aria-describedby'))?.split(' ')).toContain(errorId);
        await expect(name).toHaveAccessibleDescription(/As it appears on your ID\./);
        expect(errors).toEqual([]);
    });

    if (adapter === 'web-components') {
        continue;
    }

    test(`${adapter}: keeps control names under a custom Field wrapper`, async ({ page }) => {
        const { host, errors } = await openForm(page, adapter, '&custom=field');

        await expect(host.locator('[data-custom-field="visitorName"]')).toBeVisible();
        await expect(host.getByRole('textbox', { name: 'Visitor name', exact: true })).toBeVisible();
        await expect(host.getByRole('combobox', { name: 'Enquiry', exact: true })).toBeVisible();
        await expect(host.getByRole('radiogroup', { name: 'Contact method', exact: true })).toBeVisible();
        await expect(host.getByRole('group', { name: 'Newsletter', exact: true })).toBeVisible();
        expect(errors).toEqual([]);
    });
}
