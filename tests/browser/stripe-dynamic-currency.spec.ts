import { test, expect } from '@playwright/test';
import { build } from 'esbuild';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import type { stripeModule } from '../../packages/formie-browser/src/js/modules/payments/stripe';
import type { BrowserModuleInstance, ModuleSetupContext } from '../../packages/formie-browser/src/js/contracts/modules';

// A Stripe Payment field whose currency comes from a Dropdown (#3156). The server can't know the currency's
// minor units, so it sends a decimal amount and the module converts it for the chosen currency.
declare global {
    interface Window {
        stripeDynamicCurrency: { stripeModule: typeof stripeModule };
        stripeTest: {
            setup: (options: Record<string, unknown>) => Promise<BrowserModuleInstance>;
            calls: { call: string; amount: unknown; currency: unknown }[];
        };
    }
}

let script: string;

test.beforeAll(async () => {
    const imports = JSON.parse(readFileSync('packages/formie-browser/package.json', 'utf8')).imports;
    const result = await build({
        stdin: {
            contents: `export { stripeModule } from './packages/formie-browser/src/js/modules/payments/stripe';`,
            resolveDir: process.cwd(),
        },
        bundle: true,
        format: 'iife',
        globalName: 'stripeDynamicCurrency',
        write: false,
        loader: { '.css': 'text' },
        alias: Object.fromEntries(Object.entries(imports).map(([key, value]) => [
            key.replace('/*', ''),
            path.resolve('packages/formie-browser', (typeof value === 'string' ? value : (value as { default: string }).default).replace('/*', '')),
        ])),
    });
    script = result.outputFiles[0].text;
});

test.beforeEach(async ({ page }) => {
    await page.setContent(`<form action="https://payments.example.test/submit">
        <select name="fields[currency]">
            <option value="GBP" selected>GBP</option>
            <option value="NOK">NOK</option>
            <option value="JPY">JPY</option>
            <option value="MGA">MGA</option>
            <option value="ISK">ISK</option>
            <option value="KWD">KWD</option>
            <option value="BHD">BHD</option>
        </select>
        <input type="number" name="fields[customAmount]" value="15">
        <input type="number" name="fields[amount2]" value="20">
        <select name="fields[cur]">
            <option value="NOK" selected>NOK</option>
            <option value="GBP">GBP</option>
        </select>
        <div data-formie-field data-formie-field-handle="payment" data-formie-field-type="payment">
            <div data-formie-field-control>
                <div data-formie-stripe-elements-placeholder></div>
                <div data-formie-stripe-elements></div>
                <input type="hidden" name="fields[payment][stripePaymentIntentId]">
            </div>
        </div>
    </form>`);

    // Stripe.js stand-in that records the amount and currency the Payment Element is given.
    await page.addScriptTag({ content: `
        window.Stripe = function () {
            return {
                elements: function (options) {
                    window.stripeTest.calls.push({ call: 'elements', amount: options.amount, currency: options.currency });
                    return {
                        create: function () { return { mount: function () {}, destroy: function () {} }; },
                        update: function (next) { window.stripeTest.calls.push({ call: 'update', amount: next.amount, currency: next.currency }); },
                        submit: function () { return Promise.resolve({}); },
                    };
                },
            };
        };
    ` });
    await page.addScriptTag({ content: script });
    await page.evaluate(() => {
        const form = document.querySelector('form')!;

        window.stripeTest = {
            calls: [],
            setup: async (options) => {
                const context = {
                    formId: 'issue-3156',
                    form,
                    root: form,
                    target: form.querySelector<HTMLElement>('[data-formie-field-handle="payment"]')!,
                    scope: 'field',
                    state: {},
                    surface: 'server-rendered',
                    entryKey: 'payment',
                    options: {
                        handle: 'stripe',
                        publishableKey: 'pk_test_3156',
                        paymentType: 'single',
                        ...options,
                    },
                    on: () => () => {},
                    emit: async () => {},
                } as unknown as ModuleSetupContext;

                return (await window.stripeDynamicCurrency.stripeModule.setup(context))!;
            },
        };
    });
});

const changeCurrency = async (page: import('@playwright/test').Page, currency: string) => {
    await page.locator('select[name="fields[currency]"]').selectOption(currency);
    // The module debounces field changes by 600ms.
    await page.waitForTimeout(900);
};

test('a fixed amount is converted for the currency chosen in the form', async ({ page }) => {
    const instance = await page.evaluate(() => window.stripeTest.setup({
        amountType: 'fixed',
        currencyType: 'dynamic',
        initialPaymentInformation: { amount: '12.50', currency: 'currency' },
    }).then(() => true));
    expect(instance).toBe(true);

    await changeCurrency(page, 'NOK');
    await changeCurrency(page, 'JPY');

    expect(await page.evaluate(() => window.stripeTest.calls)).toEqual([
        { call: 'elements', amount: 1250, currency: 'gbp' },
        { call: 'update', amount: 1250, currency: 'nok' },
        { call: 'update', amount: 13, currency: 'jpy' },
    ]);
});

test('a dynamic amount follows both the amount and currency fields', async ({ page }) => {
    await page.evaluate(() => window.stripeTest.setup({
        amountType: 'dynamic',
        currencyType: 'dynamic',
        initialPaymentInformation: { amount: 'customAmount', currency: 'currency' },
    }));

    await changeCurrency(page, 'NOK');

    expect(await page.evaluate(() => window.stripeTest.calls)).toEqual([
        { call: 'elements', amount: 1500, currency: 'gbp' },
        { call: 'update', amount: 1500, currency: 'nok' },
    ]);
});

test('a fixed amount with a fixed currency is passed through as Stripe units', async ({ page }) => {
    await page.evaluate(() => window.stripeTest.setup({
        amountType: 'fixed',
        currencyType: 'fixed',
        initialPaymentInformation: { amount: 1050, currency: 'aud' },
    }));

    expect(await page.evaluate(() => window.stripeTest.calls)).toEqual([
        { call: 'elements', amount: 1050, currency: 'aud' },
    ]);
});

test('a dynamic amount field whose handle contains a digit is read from the field', async ({ page }) => {
    await page.evaluate(() => window.stripeTest.setup({
        amountType: 'dynamic',
        currencyType: 'fixed',
        initialPaymentInformation: { amount: 'amount2', currency: 'aud' },
    }));

    expect(await page.evaluate(() => window.stripeTest.calls)).toEqual([
        { call: 'elements', amount: 2000, currency: 'aud' },
    ]);
});

test('a dynamic currency field with a three-letter handle is read from the field', async ({ page }) => {
    await page.evaluate(() => window.stripeTest.setup({
        amountType: 'fixed',
        currencyType: 'dynamic',
        initialPaymentInformation: { amount: '10', currency: 'cur' },
    }));

    expect(await page.evaluate(() => window.stripeTest.calls)).toEqual([
        { call: 'elements', amount: 1000, currency: 'nok' },
    ]);
});

test('a three-decimal currency is converted to thousandths rounded to the nearest ten', async ({ page }) => {
    await page.evaluate(() => window.stripeTest.setup({
        amountType: 'fixed',
        currencyType: 'dynamic',
        initialPaymentInformation: { amount: '1.235', currency: 'currency' },
    }));

    await changeCurrency(page, 'KWD');
    await changeCurrency(page, 'BHD');

    expect(await page.evaluate(() => window.stripeTest.calls)).toEqual([
        { call: 'elements', amount: 124, currency: 'gbp' },
        { call: 'update', amount: 1240, currency: 'kwd' },
        { call: 'update', amount: 1240, currency: 'bhd' },
    ]);
});

test('MGA is zero-decimal and ISK keeps two-decimal units', async ({ page }) => {
    await page.evaluate(() => window.stripeTest.setup({
        amountType: 'fixed',
        currencyType: 'dynamic',
        initialPaymentInformation: { amount: '5000', currency: 'currency' },
    }));

    await changeCurrency(page, 'MGA');
    await changeCurrency(page, 'ISK');

    expect(await page.evaluate(() => window.stripeTest.calls)).toEqual([
        { call: 'elements', amount: 500000, currency: 'gbp' },
        { call: 'update', amount: 5000, currency: 'mga' },
        { call: 'update', amount: 500000, currency: 'isk' },
    ]);
});
