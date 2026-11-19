import type { Page, Locator } from '@playwright/test';

// One payment provider in tests/browser/client-payments.spec.ts. The spec runs it on the server-rendered form and in
// each client adapter, and expects the adapters to post the same payment inputs as the server-rendered form.
export type PaymentDecision = {
    status: string;
    message?: string;
    action?: { type: string; url?: string | null; event?: string | null; payload?: Record<string, unknown> };
};

export type PaymentProvider = {
    // The `parityPay{name}` form from tests/runtime/browser-parity-fixture.php.
    name: string;
    // Fake SDK scripts, by URL glob. The test app can't reach the providers.
    scripts: Record<string, string>;
    // Resolves once the provider's UI has mounted in the payment field.
    mounted: (field: Locator, page: Page) => Promise<void>;
    // What the visitor does in the provider's UI before submitting, such as filling card fields.
    interact?: (field: Locator, page: Page) => Promise<void>;
    // The server's answer to the first submit, when the provider continues from it (3DS, checkout redirect).
    decision?: PaymentDecision;
    // How many submits the flow makes: 1 without a decision, 2 when the module confirms and resubmits.
    submits: number;
    // Read from the page after the flow, for values the provider SDK received (such as the client secret).
    observe?: (page: Page) => Promise<unknown>;
};
