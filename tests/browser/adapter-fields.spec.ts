import { test, expect, type Page } from '@playwright/test';

async function openForm(page: Page, adapter: string, form: string) {
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message || String(error)));
    page.on('console', message => {
        if (message.type() === 'error') errors.push(message.text());
    });
    await page.goto(`/browser-fixture?adapter=${adapter}&form=${form}`);
    const host = page.locator('#host');
    await expect(host.locator('button[type="submit"]')).toBeVisible();

    return { host, errors };
}

function field(page: Page, handle: string) {
    return page.locator(`#host [data-formie-field-handle="${handle}"]`).first();
}

async function submittedValues(page: Page, submit: () => Promise<void>): Promise<Record<string, unknown>> {
    const request = page.waitForRequest(request => request.method() === 'POST' && /client(%2F|\/)submissions(%2F|\/)submit/.test(request.url()));
    await submit();
    const body = JSON.parse((await request).postData() || '{}');

    return body.values ?? body.fields ?? body;
}

for (const adapter of ['react', 'vue', 'web-components']) {
    test(`${adapter}: renders every field type the client definition sends`, async ({ page }) => {
        const { host, errors } = await openForm(page, adapter, 'adapterFields');

        await expect(host).not.toContainText('Unsupported field type');
        await expect(host).not.toContainText('Unknown field type');
        await expect(field(page, 'country').getByRole('combobox', { name: 'Country' })).toBeVisible();
        await expect(field(page, 'year').getByRole('combobox', { name: 'Year' })).toBeVisible();
        await expect(field(page, 'company').getByRole('textbox', { name: 'Company' })).toBeVisible();
        await expect(field(page, 'department').getByRole('combobox', { name: 'Department' })).toBeVisible();
        await expect(field(page, 'relatedEntry').getByRole('combobox', { name: 'Related entry' })).toBeVisible();
        await expect(field(page, 'divider').locator('hr')).toHaveCount(1);

        const schedule = field(page, 'schedule');
        await expect(schedule.getByRole('textbox', { name: 'Task' })).toHaveValue('Plan');
        await expect(schedule.getByRole('checkbox', { name: 'Done' })).not.toBeChecked();

        // A field the visitor can't see can't be filled in, so these values must still submit.
        await field(page, 'visitorName').getByRole('textbox').fill('Ada');
        const values = await submittedValues(page, () => host.locator('button[type="submit"]').click());
        expect(String(values.department ?? '')).toMatch(/^recipient:/);
        expect(values.schedule).toEqual([{ task: 'Plan', done: false, priority: 'low' }]);
        expect(errors).toEqual([]);
    });

    test(`${adapter}: submits element, Date, Group and Table answers the server accepts`, async ({ page }) => {
        const { host, errors } = await openForm(page, adapter, 'adapterFields');
        const related = field(page, 'relatedEntry').getByRole('combobox', { name: 'Related entry' });
        const entryId = await related.evaluate((select: HTMLSelectElement) => [...select.options].map(option => option.value).find(value => value !== '') ?? '');
        expect(entryId).not.toBe('');

        await field(page, 'visitorName').getByRole('textbox').fill('Ada');
        await related.selectOption(entryId);
        await field(page, 'featuredEntry').locator(`input[type="radio"][value="${entryId}"]`).check();
        await field(page, 'year').getByRole('combobox').selectOption('2001');
        await field(page, 'company').getByRole('textbox').fill('Acme');
        await expect(field(page, 'schedule').getByRole('combobox', { name: 'Priority' })).toHaveValue('low');

        const values = await submittedValues(page, () => host.locator('button[type="submit"]').click());
        expect(values.relatedEntry).toEqual([entryId]);
        expect(values.featuredEntry).toEqual([entryId]);
        expect((values.birthday as Record<string, unknown>).year).toBe('2001');
        expect(values.details).toEqual({ company: 'Acme' });
        expect(values.schedule).toEqual([{ task: 'Plan', done: false, priority: 'low' }]);
        // The server rejects answers in the wrong shape, such as a bare element ID.
        await expect(host.getByRole('status')).toContainText('Submission saved.');
        expect(errors).toEqual([]);
    });

    test(`${adapter}: calculates Calculations fields in a Group and in each Repeater row`, async ({ page }) => {
        const { host, errors } = await openForm(page, adapter, 'adapterNestedCalculations');
        const rows = page.locator('#host [data-formie-field-handle="items"] [data-formie-repeater-item]');

        await field(page, 'rate').getByRole('spinbutton').fill('3');
        await field(page, 'hours').getByRole('spinbutton').fill('2');
        await rows.nth(0).locator('[data-formie-field-handle="qty"]').getByRole('spinbutton').fill('4');
        await rows.nth(1).locator('[data-formie-field-handle="qty"]').getByRole('spinbutton').fill('5');

        const groupTotal = field(page, 'groupTotal').getByRole('textbox');
        await expect(groupTotal).toHaveValue('6');
        await expect(groupTotal).toHaveJSProperty('readOnly', true);
        await expect(rows.nth(0).locator('[data-formie-field-handle="rowTotal"]').getByRole('textbox')).toHaveValue('12');
        await expect(rows.nth(1).locator('[data-formie-field-handle="rowTotal"]').getByRole('textbox')).toHaveValue('15');

        const values = await submittedValues(page, () => host.locator('button[type="submit"]').click());
        expect(String((values.details as Record<string, unknown>).groupTotal)).toBe('6');
        expect((values.items as Array<Record<string, unknown>>).map(row => String(row.rowTotal))).toEqual(['12', '15']);
        await expect(host.getByRole('status')).toContainText('Submission saved.');
        expect(errors).toEqual([]);
    });

    test(`${adapter}: calculates Calculations fields as read-only values`, async ({ page }) => {
        const { errors } = await openForm(page, adapter, 'adapterFields');
        const total = field(page, 'total').getByRole('textbox');

        await field(page, 'quantity').getByRole('spinbutton').fill('7');
        await expect(total).toHaveValue('14');
        await expect(total).toHaveJSProperty('readOnly', true);
        expect(errors).toEqual([]);
    });

    test(`${adapter}: shows the dropdown values it submits`, async ({ page }) => {
        const { host, errors } = await openForm(page, adapter, 'adapterFields');
        const colour = field(page, 'colour').getByRole('combobox', { name: 'Colour' });
        const colours = field(page, 'colours').getByRole('listbox', { name: 'Colours' });

        await expect(colour).toHaveJSProperty('multiple', false);
        await expect(colour).toHaveValue('red');
        expect(await colours.evaluate((select: HTMLSelectElement) => [...select.selectedOptions].map(option => option.value))).toEqual(['red', 'blue']);
        await expect(field(page, 'attachment').locator('input[type="file"]')).toHaveJSProperty('multiple', true);

        await field(page, 'visitorName').getByRole('textbox').fill('Ada');
        const values = await submittedValues(page, () => host.locator('button[type="submit"]').click());
        expect(values.colour).toBe('red');
        expect(values.colours).toEqual(['red', 'blue']);
        expect(errors).toEqual([]);
    });

    test(`${adapter}: groups radios per field and per Repeater row`, async ({ page }) => {
        const { errors } = await openForm(page, adapter, 'adapterFields');
        const sizes = field(page, 'size').getByRole('radio');
        const names = await sizes.evaluateAll(radios => radios.map(radio => (radio as HTMLInputElement).name));

        expect(names[0]).toBeTruthy();
        expect(new Set(names).size).toBe(1);

        const rows = page.locator('#host [data-formie-field-handle="people"] [data-formie-repeater-item]');
        await expect(rows).toHaveCount(2);
        const rowNames = await Promise.all([0, 1].map(index => rows.nth(index).getByRole('radio').first().evaluate(radio => (radio as HTMLInputElement).name)));
        expect(rowNames[0]).not.toBe(rowNames[1]);
        expect(rowNames).not.toContain(names[0]);

        await rows.nth(0).getByRole('radio', { name: 'Lead' }).check();
        await rows.nth(1).getByRole('radio', { name: 'Member' }).check();
        await expect(rows.nth(0).getByRole('radio', { name: 'Lead' })).toBeChecked();
        await expect(rows.nth(1).getByRole('radio', { name: 'Member' })).toBeChecked();

        await expect(rows.nth(0).getByRole('button', { name: 'Remove row 1' })).toBeVisible();
        await expect(rows.nth(1).getByRole('button', { name: 'Remove row 2' })).toBeVisible();
        // Hidden visibility applies to Repeater row fields as it does to top-level fields.
        await expect(page.locator('#host [data-formie-field-handle="personSecret"]')).toHaveCount(0);
        expect(errors).toEqual([]);
    });

    test(`${adapter}: names composite fields, hidden labels, autocomplete and the signature pad`, async ({ page }) => {
        const { host, errors } = await openForm(page, adapter, 'adapterFields');

        await expect(host.getByRole('group', { name: 'Full name', exact: true }).getByRole('textbox', { name: 'First Name' })).toBeVisible();
        await expect(host.getByRole('group', { name: 'Home address', exact: true }).getByRole('textbox', { name: 'City' })).toBeVisible();
        await expect(host.getByRole('group', { name: 'Birthday', exact: true }).getByRole('combobox', { name: 'Year' })).toBeVisible();
        await expect(host.getByRole('group', { name: 'Details', exact: true }).getByRole('textbox', { name: 'Company' })).toBeVisible();

        const nickname = host.getByRole('textbox', { name: 'Nickname', exact: true });
        await expect(nickname).toBeVisible();
        const labelBox = await field(page, 'nickname').locator('label').boundingBox();
        expect(labelBox === null || (labelBox.width <= 1 && labelBox.height <= 1)).toBe(true);

        await expect(field(page, 'visitorEmail').getByRole('textbox')).toHaveAttribute('autocomplete', 'email');
        await expect(host.getByRole('group', { name: 'Full name', exact: true }).getByRole('textbox', { name: 'First Name' })).toHaveAttribute('autocomplete', 'given-name');

        const signature = field(page, 'signature');
        await expect(signature.getByRole('img', { name: 'Signature' })).toBeAttached();
        await expect(signature.getByRole('button', { name: 'Clear signature' })).toBeVisible();

        await field(page, 'attachment').locator('input[type="file"]').setInputFiles({ name: 'notes.txt', mimeType: 'text/plain', buffer: Buffer.from('hello') });
        await expect(field(page, 'attachment')).toContainText('notes.txt');
        await expect(field(page, 'attachment').locator('[class*="field-errors"]:not([data-formie-field-errors])')).toHaveCount(0);
        expect(errors).toEqual([]);
    });

    test(`${adapter}: shows a Repeater error once and announces the form error`, async ({ page }) => {
        const { host, errors } = await openForm(page, adapter, 'adapterRequiredRepeater');

        // A single-page form shows its page title too, as server-rendered forms do.
        await expect(host.getByRole('heading', { level: 3, name: 'Page 1' })).toBeVisible();
        await host.locator('button[type="submit"]').click();
        const items = field(page, 'items');
        await expect(items.getByText('Items cannot be blank.')).toHaveCount(1);
        await expect.poll(() => page.evaluate(() => document.activeElement?.closest('[data-formie-field-handle="items"], [role="alert"]') !== null)).toBe(true);
        await expect(host.getByRole('alert')).toContainText('Couldn’t save submission due to errors.');
        expect(errors).toEqual([]);
    });

    test(`${adapter}: shows page titles and progress, moves focus with page changes and announces completion`, async ({ page }) => {
        const { host, errors } = await openForm(page, adapter, 'adapterPages');

        const pageFocused = () => page.evaluate(() => document.activeElement?.matches('#host [data-page-id]') === true);
        const progress = host.getByRole('progressbar', { name: 'Progress' });

        // The form shows its page title and progress, as server-rendered forms do.
        await expect(host.getByRole('heading', { level: 3, name: 'Page 1' })).toBeVisible();
        await expect(progress).toHaveAttribute('aria-valuenow', '0');
        expect(await progress.evaluate((bar) => !!(bar.compareDocumentPosition(document.querySelector('#host [data-page-id]')!) & Node.DOCUMENT_POSITION_FOLLOWING))).toBe(true);

        await field(page, 'stepOne').getByRole('textbox').fill('One');
        await host.locator('button[type="submit"]').click();
        await expect(field(page, 'stepTwo')).toBeVisible();
        await expect.poll(pageFocused).toBe(true);
        await expect(host.getByRole('heading', { level: 3, name: 'Page 2' })).toBeVisible();
        await expect(host.getByRole('region', { name: 'Page 2' })).toBeFocused();
        await expect(progress).toHaveAttribute('aria-valuenow', '50');
        await expect(progress).toContainText('50%');

        await host.getByRole('button', { name: 'Back' }).click();
        await expect(field(page, 'stepOne')).toBeVisible();
        await expect.poll(pageFocused).toBe(true);

        await host.locator('button[type="submit"]').click();
        await expect(field(page, 'stepTwo')).toBeVisible();
        await expect.poll(pageFocused).toBe(true);
        await host.locator('button[type="submit"]').click();
        const notice = host.getByRole('status');
        await expect(notice).toContainText('Submission saved.');
        await expect(notice).toBeFocused();
        expect(errors).toEqual([]);
    });
}

test('react: renders custom field components as components', async ({ page }) => {
    const errors: string[] = [];
    page.on('console', message => {
        if (message.type() === 'error') errors.push(message.text());
    });
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/browser-fixture?adapter=react&form=renderedContract&custom=hooks');
    const host = page.locator('#host');
    const enquiry = host.getByRole('combobox', { name: 'Enquiry' });

    await expect(host.locator('[data-custom-field]').first()).toBeVisible();
    await enquiry.selectOption('other');
    await expect(host.getByRole('textbox', { name: 'Details' })).toBeVisible();
    await enquiry.selectOption('general');
    await expect(host.getByRole('textbox', { name: 'Details' })).toHaveCount(0);
    expect(errors).toEqual([]);
});
