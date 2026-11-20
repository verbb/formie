import { test, expect, type Locator, type Page } from '@playwright/test';

// An Address field's State / Province follows the chosen country in client-rendered forms as in server-rendered ones:
// a dropdown of the country's subdivisions named after them, hidden for countries that don't use them. Address fields
// can also preselect the country from the visitor's IP address.
const SURFACES = ['server', 'vue', 'react', 'web-components'] as const;

// Server-rendered sub-fields are named after their Address field, as `home.state`.
function part(page: Page, address: string, handle: string): Locator {
    return page.locator(`[data-formie-field-handle="${address}.${handle}"], [data-formie-field-handle="${address}"] [data-formie-field-handle="${handle}"]`).first();
}

async function open(page: Page, surface: string) {
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message || String(error)));
    // The visitor's IP address is in New Zealand.
    await page.route('**/country-from-ip**', route => route.fulfill({ json: { countryCode: 'NZ', countryName: 'New Zealand' } }));
    await page.route('**/address%2Fcountry-from-ip**', route => route.fulfill({ json: { countryCode: 'NZ', countryName: 'New Zealand' } }));
    await page.goto(surface === 'server'
        ? '/browser-fixture?adapter=vue&product=server&form=adapterAddressState'
        : `/browser-fixture?adapter=${surface}&form=adapterAddressState`);
    await expect(page.locator('button[type="submit"]').first()).toBeVisible();

    return errors;
}

// Server-rendered forms hide the part with a theme class, which the fixture page doesn't style, so they're checked by
// the module's marker instead.
async function expectHidden(surface: string, state: Locator) {
    if (surface === 'server') await expect(state).toHaveAttribute('data-formie-address-state-hidden', '');
    else await expect(state).toBeHidden();
}

async function chooseCountry(page: Page, address: string, code: string) {
    await part(page, address, 'country').locator('select').selectOption(code);
}

for (const surface of SURFACES) {
    test(`${surface}: shows the country's subdivisions for State / Province`, async ({ page }) => {
        const errors = await open(page, surface);
        const state = part(page, 'homeAddress', 'state');

        // Without a country there's nothing to choose from.
        await expectHidden(surface, state);

        await chooseCountry(page, 'homeAddress', 'AU');
        await expect(state.locator('label').first()).toContainText('State');
        await expect(state.locator('select option', { hasText: 'Victoria' })).toHaveCount(1);
        await state.locator('select').selectOption({ label: 'Victoria' });

        // Singapore addresses have no state.
        await chooseCountry(page, 'homeAddress', 'SG');
        await expectHidden(surface, state);

        await chooseCountry(page, 'homeAddress', 'JP');
        await expect(state.locator('label').first()).toContainText('Prefecture');
        await expect(state.locator('select option', { hasText: 'Hokkaido' })).toHaveCount(1);

        expect(errors).toEqual([]);
    });

    test(`${surface}: preselects the country from the visitor's IP address`, async ({ page }) => {
        const errors = await open(page, surface);

        await expect(part(page, 'postalAddress', 'country').locator('select')).toHaveValue('NZ');
        await expect(part(page, 'homeAddress', 'country').locator('select')).toHaveValue('');
        expect(errors).toEqual([]);
    });

    if (surface === 'server') continue;

    test(`${surface}: saves the chosen subdivision and skips a hidden State / Province`, async ({ page }) => {
        const errors = await open(page, surface);
        const submit = page.locator('#host button[type="submit"]');

        await chooseCountry(page, 'homeAddress', 'AU');
        await part(page, 'homeAddress', 'state').locator('select').selectOption({ label: 'Victoria' });

        // The searchable dropdown filters the country's subdivisions as the visitor types.
        await chooseCountry(page, 'postalAddress', 'AU');
        const postalState = part(page, 'postalAddress', 'state');
        await postalState.locator('.ts-control').click();
        await page.keyboard.type('Queens');
        await page.locator('.ts-dropdown .option', { hasText: 'Queensland' }).click();

        await page.waitForTimeout(3000);
        let response = page.waitForResponse(r => r.request().method() === 'POST' && /client(%2F|\/)submissions(%2F|\/)submit/.test(r.url()));
        await submit.click();
        let uid = (await (await response).json()).submissionUid;
        let saved = await (await page.request.get(`/browser-parity-saved?uid=${uid}`)).json();

        expect(saved.values.homeAddress).toMatchObject({ country: 'AU', state: 'Victoria' });
        expect(saved.values.postalAddress).toMatchObject({ country: 'AU', state: 'Queensland' });

        // A required State / Province hidden for Singapore doesn't stop the form, and its old answer is cleared.
        await page.goto(`/browser-fixture?adapter=${surface}&form=adapterAddressState`);
        await chooseCountry(page, 'homeAddress', 'AU');
        await part(page, 'homeAddress', 'state').locator('select').selectOption({ label: 'Victoria' });
        await chooseCountry(page, 'homeAddress', 'SG');
        await expect(part(page, 'homeAddress', 'state')).toBeHidden();

        await page.waitForTimeout(3000);
        response = page.waitForResponse(r => r.request().method() === 'POST' && /client(%2F|\/)submissions(%2F|\/)submit/.test(r.url()));
        await submit.click();
        uid = (await (await response).json()).submissionUid;
        saved = await (await page.request.get(`/browser-parity-saved?uid=${uid}`)).json();

        expect(saved.values.homeAddress.country).toBe('SG');
        expect(saved.values.homeAddress.state ?? '').toBe('');
        expect(errors).toEqual([]);
    });
}
