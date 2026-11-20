import { test, expect, type Page } from '@playwright/test';
import { addressFinder } from './address-providers/address-finder';
import { google } from './address-providers/google';
import { loqate } from './address-providers/loqate';
import { placeKit } from './address-providers/place-kit';
import type { AddressProvider } from './address-providers/types';

// Address auto-complete in client-rendered forms must attach the same provider and save the same address as the
// server-rendered form. Provider SDKs are faked at the network boundary, because the test app can't reach them.
type Surface = 'server' | 'vue' | 'react' | 'web-components';

const SURFACES: Surface[] = ['server', 'vue', 'react', 'web-components'];
const PROVIDERS: AddressProvider[] = [google, placeKit, loqate, addressFinder];

function surfaceUrl(surface: Surface, form: string): string {
    return surface === 'server'
        ? `/browser-fixture?adapter=vue&product=server&form=${form}`
        : `/browser-fixture?adapter=${surface}&form=${form}`;
}

async function fillAddress(page: Page, surface: Surface, provider: AddressProvider) {
    const errors: string[] = [];
    page.setDefaultTimeout(8000);
    page.on('pageerror', (error) => errors.push(error.message || String(error)));
    page.on('console', (message) => {
        if (message.type() === 'error') errors.push(message.text());
    });

    for (const [url, response] of Object.entries(provider.routes)) {
        await page.route(url, (route) => route.fulfill({ status: 200, contentType: response.contentType, body: response.body }));
    }

    await page.goto(surfaceUrl(surface, `parityAddress${provider.name}`));
    const field = page.locator('#host [data-formie-field-handle="home"]').first();
    await provider.mounted(field, page);
    await page.locator('#host [data-formie-field-handle="visitorName"] input').fill('Ada');
    await provider.pick(field, page);
    // Faster submissions look accepted but are kept as spam.
    await page.waitForTimeout(3500);

    const response = page.waitForResponse((r) => r.request().method() === 'POST' && /submissions(%2F|\/)submit/.test(r.url()), { timeout: 15000 });
    await page.locator('#host button[type="submit"]').last().click();
    const body = await (await response).json().catch(() => ({}));
    const uid = typeof body.submissionUid === 'string' ? body.submissionUid : null;
    const saved = uid ? await (await page.request.get(`/browser-parity-saved?uid=${uid}`)).json() : null;

    return { address: saved?.values?.home ?? null, failure: uid ? null : JSON.stringify(body.errors ?? body).slice(0, 300), errors };
}

test.describe('client-rendered address auto-complete', () => {
    for (const provider of PROVIDERS) {
        test(`${provider.name} works like the server-rendered form`, async ({ browser }) => {
            test.setTimeout(180000);
            const results: Record<string, Awaited<ReturnType<typeof fillAddress>>> = {};

            for (const surface of SURFACES) {
                const page = await browser.newPage({ ignoreHTTPSErrors: true, baseURL: test.info().project.use.baseURL });
                results[surface] = await fillAddress(page, surface, provider);
                await page.close();
            }

            expect(results.server.failure, 'the server-rendered form must save').toBeNull();
            expect(results.server.errors, 'the fake must work on the server-rendered form').toEqual([]);
            // A picked address fills more than the first line, so an empty or partial baseline means the fake didn't work.
            expect(Object.values(results.server.address ?? {}).filter((part) => typeof part === 'string' && part !== '').length).toBeGreaterThan(2);

            for (const surface of ['vue', 'react', 'web-components'] as const) {
                expect(results[surface], surface).toEqual(results.server);
            }
        });
    }
});
