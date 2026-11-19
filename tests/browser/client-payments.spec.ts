import { test, expect, type Page, type Request } from '@playwright/test';
import type { PaymentProvider } from './payment-providers/types';
import { bpoint } from './payment-providers/bpoint';
import { eway } from './payment-providers/eway';
import { goCardless } from './payment-providers/go-cardless';
import { mollie } from './payment-providers/mollie';
import { moneris } from './payment-providers/moneris';
import { opayo } from './payment-providers/opayo';
import { paddle } from './payment-providers/paddle';
import { paypal } from './payment-providers/paypal';
import { payway } from './payment-providers/payway';
import { square } from './payment-providers/square';
import { stripe } from './payment-providers/stripe';

// Payment fields in client-rendered forms must mount the same provider UI and post the same payment data as the
// server-rendered form. Provider SDKs and the server's payment decisions are faked at the network boundary, because
// the test app can't reach the providers. Everything between them is the real code.
type Surface = 'server' | 'vue' | 'react' | 'web-components';

const SURFACES: Surface[] = ['server', 'vue', 'react', 'web-components'];
const PROVIDERS: PaymentProvider[] = [stripe, paypal, square, payway, paddle, eway, bpoint, opayo, moneris, mollie, goCardless];

function surfaceUrl(surface: Surface, form: string): string {
    return surface === 'server'
        ? `/browser-fixture?adapter=vue&product=server&form=${form}`
        : `/browser-fixture?adapter=${surface}&form=${form}`;
}

// The payment inputs a submit carried, from a server-rendered form post or a client submit's browser data.
function paymentInputs(request: Request, surface: Surface): Record<string, string> {
    if (surface === 'server') {
        const body = request.postDataBuffer()?.toString('utf8') ?? '';
        const entries = [...body.matchAll(/name="(fields\[pay\][^"]*)"\r\n\r\n([^\r]*)/g)];
        return Object.fromEntries(entries.map(([, name, value]) => [name, value]));
    }

    const browserData = (JSON.parse(request.postData() || '{}').browserData ?? {}) as Record<string, string>;
    return Object.fromEntries(Object.entries(browserData).filter(([name]) => name.startsWith('fields[pay]')));
}

function fulfilment(surface: Surface, provider: PaymentProvider, submit: number) {
    const client = surface !== 'server';
    const base = client ? { isFinalPage: true, errors: { form: [], fields: {} }, messages: { notice: null, error: null } } : { errors: {} };

    if (provider.decision && submit === 1) {
        return { ...base, success: false, outcome: 'paymentActionRequired', payment: provider.decision };
    }

    return { ...base, success: true, outcome: 'completed', submissionUid: 'parity-payment', ...(client ? { completion: { behavior: 'message', url: null, target: 'same-tab', message: 'Payment complete.', hideForm: false } } : {}) };
}

async function pay(page: Page, surface: Surface, provider: PaymentProvider) {
    const errors: string[] = [];
    const requests: Request[] = [];
    page.on('pageerror', (error) => errors.push(error.message || String(error)));

    for (const [url, body] of Object.entries(provider.scripts)) {
        await page.route(url, (route) => route.fulfill({ contentType: 'text/javascript', body }));
    }

    await page.route(/submissions(%2F|\/)submit/, async (route) => {
        requests.push(route.request());
        await route.fulfill({ contentType: 'application/json', body: JSON.stringify(fulfilment(surface, provider, requests.length)) });
    });

    await page.goto(surfaceUrl(surface, `parityPay${provider.name}`));
    const field = page.locator('#host [data-formie-field-handle="pay"]').first();
    await provider.mounted(field, page);
    await page.locator('#host [data-formie-field-handle="payerName"] input').fill('Ada');
    await provider.interact?.(field, page);
    await page.locator('#host button[type="submit"]').last().click();
    await expect.poll(() => requests.length, { timeout: 15000 }).toBe(provider.submits);

    return {
        observed: (await provider.observe?.(page)) ?? null,
        submits: requests.map((request) => paymentInputs(request, surface)),
        errors,
    };
}

test.describe('client-rendered payments', () => {
    for (const provider of PROVIDERS) {
        test(`${provider.name} works like the server-rendered form`, async ({ browser }) => {
            test.setTimeout(180000);
            const results: Record<string, Awaited<ReturnType<typeof pay>>> = {};

            for (const surface of SURFACES) {
                const page = await browser.newPage({ ignoreHTTPSErrors: true, baseURL: test.info().project.use.baseURL });
                results[surface] = await pay(page, surface, provider);
                await page.close();
            }

            expect(results.server.errors, 'the fake must work on the server-rendered form').toEqual([]);

            for (const surface of ['vue', 'react', 'web-components'] as const) {
                expect(results[surface], surface).toEqual(results.server);
            }
        });
    }
});
