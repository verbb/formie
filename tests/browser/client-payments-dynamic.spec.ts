import { test, expect, type Page } from '@playwright/test';

// A Stripe Payment field whose amount and currency come from other fields must read the visitor's answers in
// client-rendered forms as it does in the server-rendered form. Stripe.js and the server's payment decision are faked at
// the network boundary. Everything between them is the real code.
type Surface = 'server' | 'vue' | 'react' | 'web-components';

const SURFACES: Surface[] = ['server', 'vue', 'react', 'web-components'];

const FAKE_STRIPE = `
window.__stripeElements = [];
window.Stripe = function () {
    return {
        elements: function (options) {
            window.__stripeElements.push({ call: 'elements', amount: options.amount, currency: options.currency });
            return {
                create: function (type) {
                    return {
                        mount: function (element) {
                            var fake = document.createElement('div');
                            fake.setAttribute('data-fake-stripe', type);
                            element.appendChild(fake);
                        },
                        destroy: function () {},
                    };
                },
                submit: function () { return Promise.resolve({}); },
                update: function (options) {
                    window.__stripeElements.push({ call: 'update', amount: options.amount, currency: options.currency });
                },
            };
        },
        confirmPayment: function () { return Promise.resolve({ paymentIntent: { id: 'pi_parity', status: 'succeeded' } }); },
        confirmSetup: function () { return Promise.resolve({ setupIntent: { id: 'seti_parity' } }); },
    };
};`;

function surfaceUrl(surface: Surface, form = 'parityPayStripeDynamic'): string {
    return surface === 'server'
        ? `/browser-fixture?adapter=vue&product=server&form=${form}`
        : `/browser-fixture?adapter=${surface}&form=${form}`;
}

async function payDynamic(page: Page, surface: Surface) {
    let submits = 0;

    await page.route('https://js.stripe.com/**', (route) => route.fulfill({ contentType: 'text/javascript', body: FAKE_STRIPE }));
    await page.route(/submissions(%2F|\/)submit/, async (route) => {
        submits += 1;
        const client = surface !== 'server';
        const base = client ? { isFinalPage: true, errors: { form: [], fields: {} }, messages: { notice: null, error: null } } : { errors: {} };
        const decision = { status: 'requiresAction', message: 'Confirm the payment.', action: { type: 'confirm', event: 'formie:payment:stripe:confirm', payload: { clientSecret: 'pi_parity_secret' } } };
        const body = submits === 1
            ? { ...base, success: false, outcome: 'paymentActionRequired', payment: decision }
            : { ...base, success: true, outcome: 'completed', submissionUid: 'parity-payment', ...(client ? { completion: { behavior: 'message', url: null, target: 'same-tab', message: 'Payment complete.', hideForm: false } } : { submitActionMessage: 'Payment complete.' }) };
        await route.fulfill({ contentType: 'application/json', body: JSON.stringify(body) });
    });

    await page.goto(surfaceUrl(surface));

    const host = page.locator('#host');
    await host.locator('[data-formie-field-handle="payerName"] input').first().fill('Ada');
    await host.locator('[data-formie-field-handle="customAmount"] input').first().fill('25');
    await host.locator('[data-formie-field-handle="customAmount"] input').first().blur();

    const field = host.locator('[data-formie-field-handle="pay"]').first();
    await expect(field.locator('[data-fake-stripe="payment"]')).toHaveCount(1, { timeout: 15000 });
    const usd = await page.evaluate(() => (window as any).__stripeElements.at(-1));

    await host.locator('[data-formie-field-handle="payCurrency"] select').first().selectOption('NOK');
    await host.locator('[data-formie-field-handle="customAmount"] input').first().fill('40');
    await host.locator('[data-formie-field-handle="customAmount"] input').first().blur();
    await expect.poll(() => page.evaluate(() => (window as any).__stripeElements.at(-1)), { timeout: 10000 }).toEqual({ call: 'update', amount: 4000, currency: 'nok' });

    await host.locator('button[type="submit"]').last().click();
    await expect.poll(() => submits, { timeout: 15000 }).toBe(2);

    return { usd, error: await field.locator('[data-payment-error]').count() };
}

const FAKE_PAYPAL = `
window.paypal = {
    Buttons: function (options) {
        return {
            render: function (element) {
                var button = document.createElement('button');
                button.type = 'button';
                button.setAttribute('data-fake-paypal', 'button');
                button.textContent = 'PayPal';
                button.addEventListener('click', function () {
                    var actions = { order: {
                        create: function (config) { window.__paypalOrder = config; return Promise.resolve('ORDER-PARITY'); },
                        authorize: function () { return Promise.resolve({ purchase_units: [{ payments: { authorizations: [{ id: 'AUTH-PARITY' }] } }] }); },
                    } };
                    Promise.resolve().then(function () { return options.createOrder({}, actions); }).then(function (orderID) {
                        return options.onApprove({ orderID: orderID }, actions);
                    }).catch(function (error) { window.__paypalError = String(error && error.message || error); options.onError(error); });
                });
                element.appendChild(button);
                return { close: function () {} };
            },
        };
    },
};`;

async function orderDynamic(page: Page, surface: Surface) {
    await page.route('https://www.paypal.com/sdk/js**', (route) => route.fulfill({ contentType: 'text/javascript', body: FAKE_PAYPAL }));
    await page.goto(surfaceUrl(surface, 'parityPayPayPalDynamic'));

    const host = page.locator('#host');
    await host.locator('[data-formie-field-handle="customAmount"] input').first().fill('25');
    await host.locator('[data-formie-field-handle="customAmount"] input').first().blur();

    const field = host.locator('[data-formie-field-handle="pay"]').first();
    await expect(field.locator('[data-fake-paypal="button"]')).toHaveCount(1, { timeout: 15000 });
    await field.locator('[data-fake-paypal="button"]').click();
    await expect.poll(() => page.evaluate(() => (window as any).__paypalOrder ?? (window as any).__paypalError ?? null), { timeout: 10000 }).not.toBeNull();

    return page.evaluate(() => ({ order: (window as any).__paypalOrder ?? null, error: (window as any).__paypalError ?? null }));
}

test.describe('client-rendered payments with a dynamic amount and currency', () => {
    for (const surface of SURFACES) {
        test(`PayPal builds its order from the amount field (${surface})`, async ({ page }) => {
            test.setTimeout(90000);
            const result = await orderDynamic(page, surface);

            expect(result.error).toBeNull();
            expect(result.order?.purchase_units?.[0]?.amount).toMatchObject({ currency_code: 'AUD', value: '25' });
        });
    }

    for (const surface of SURFACES) {
        test(`Stripe reads the amount and currency fields (${surface})`, async ({ page }) => {
            test.setTimeout(90000);
            const result = await payDynamic(page, surface);

            expect(result.usd).toMatchObject({ amount: 2500, currency: 'usd' });
            expect(result.error).toBe(0);
        });
    }
});
