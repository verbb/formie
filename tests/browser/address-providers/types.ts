import type { Page, Locator } from '@playwright/test';

// One address auto-complete provider in tests/browser/client-address.spec.ts. The spec runs it on the server-rendered
// form and in each client adapter, and expects the adapters to save the same address as the server-rendered form.
export type AddressProvider = {
    // The `parityAddress{name}` form from tests/runtime/browser-parity-fixture.php.
    name: string;
    // Fake SDK scripts and API responses, by URL glob. The test app can't reach the providers.
    routes: Record<string, { contentType: string; body: string }>;
    // Resolves once the provider has attached to the auto-complete input in the Address field.
    mounted: (field: Locator, page: Page) => Promise<void>;
    // What the visitor does: search in the auto-complete input and pick a suggestion.
    pick: (field: Locator, page: Page) => Promise<void>;
};
