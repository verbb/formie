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

    // https://github.com/verbb/formie/issues/3139
    test(`${adapter}: marks required fields`, async ({ page }) => {
        const { host, errors } = await openForm(page, adapter);
        const wrapper = (handle: string) => host.locator(`[data-formie-field-handle="${handle}"]`).first();

        for (const handle of ['visitorName', 'message', 'enquiry', 'contactMethod', 'newsletter']) {
            await expect(wrapper(handle)).toHaveAttribute('data-formie-field-required', 'true');
        }

        for (const handle of ['visitorEmail', 'topics']) {
            await expectNoAttribute(wrapper(handle), 'data-formie-field-required');
        }

        const name = host.getByRole('textbox', { name: 'Visitor name', exact: true });
        await expect(name).toHaveAttribute('aria-required', 'true');
        await expect(host.getByRole('textbox', { name: 'Message', exact: true })).toHaveAttribute('aria-required', 'true');
        await expect(host.getByLabel('Enquiry', { exact: true })).toHaveAttribute('aria-required', 'true');
        await expect(host.getByRole('radiogroup', { name: 'Contact method', exact: true })).toHaveAttribute('aria-required', 'true');
        await expect(host.getByRole('checkbox', { name: 'Yes, send me updates.', exact: true })).toHaveAttribute('aria-required', 'true');
        await expectNoAttribute(host.getByRole('textbox', { name: 'Visitor email', exact: true }), 'aria-required');
        await expectNoAttribute(host.getByRole('checkbox', { name: 'News', exact: true }), 'aria-required');

        // Native `required` would show the browser's own validation bubbles ahead of Formie's.
        await expectNoAttribute(name, 'required');
        expect(errors).toEqual([]);
    });

    if (adapter === 'web-components') {
        continue;
    }

    test(`${adapter}: passes the field to slot components`, async ({ page }) => {
        const { host, errors } = await openForm(page, adapter, '&custom=label');

        await expect(host.locator('[data-slot-field="visitorName"]')).toHaveText('Visitor name');
        await expect(host.getByRole('textbox', { name: 'Visitor name', exact: true })).toBeVisible();
        expect(errors).toEqual([]);
    });

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

for (const adapter of ['react', 'vue', 'web-components']) {
    test(`${adapter}: renders single and multiple value controls as configured`, async ({ page }) => {
        const errors: string[] = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`/browser-fixture?adapter=${adapter}&form=valueCountContract`);
        const host = page.locator('#host');
        const multiple = (label: string) => host.getByLabel(label, { exact: true }).evaluate((element) => (element as HTMLSelectElement | HTMLInputElement).multiple);

        await expect(host.getByRole('combobox', { name: 'Enquiry', exact: true })).toBeVisible();
        await expect(host.getByRole('listbox', { name: 'Interests', exact: true })).toBeVisible();
        expect(await multiple('Enquiry')).toBe(false);
        expect(await multiple('Interests')).toBe(true);
        expect(await multiple('Photo')).toBe(false);
        expect(await multiple('Documents')).toBe(true);

        // A single dropdown keeps one chosen value.
        await host.getByLabel('Enquiry', { exact: true }).selectOption('other');
        await expect(host.getByLabel('Enquiry', { exact: true })).toHaveValue('other');
        expect(errors).toEqual([]);
    });
}
