import { expect } from '@playwright/test';
import type { PaymentProvider } from './types';

const HOSTED_TOKENIZATION = 'https://www3.moneris.com/HPPtoken/**';

// Moneris Hosted Tokenization: a card form in Moneris's frame that answers the parent's `tokenize` message with a
// data key, or a response code when the card is missing.
const FAKE_FRAME = `<!doctype html>
<input data-fake-moneris="pan" autocomplete="cc-number">
<script>
window.addEventListener('message', function (event) {
    if (event.data !== 'tokenize') return;
    var pan = document.querySelector('[data-fake-moneris="pan"]').value;
    var reply = pan ? { responseCode: ['001'], dataKey: 'ot-parity-' + pan.slice(-4) } : { responseCode: ['943'] };
    event.source.postMessage(JSON.stringify(reply), event.origin);
});
</script>`;

// The frame loads with the page, before this config can serve an HTML document for it, so `mounted` routes the
// frame and reloads. Submitting asks the frame to tokenize and posts the data key.
export const moneris: PaymentProvider = {
    name: 'Moneris',
    // Keeps the first load off the network until the frame route below is in place.
    scripts: { [HOSTED_TOKENIZATION]: '' },
    mounted: async (field, page) => {
        await page.route(HOSTED_TOKENIZATION, (route) => route.fulfill({ contentType: 'text/html', body: FAKE_FRAME }));
        await page.reload();
        await expect(field.frameLocator('iframe[data-formie-moneris-frame]').locator('[data-fake-moneris="pan"]')).toHaveCount(1, { timeout: 15000 });
    },
    interact: async (field) => {
        await field.frameLocator('iframe[data-formie-moneris-frame]').locator('[data-fake-moneris="pan"]').fill('4242424242424242');
    },
    submits: 1,
    observe: (page) => page.evaluate(() => document.querySelector<HTMLIFrameElement>('#host iframe[data-formie-moneris-frame]')?.src ?? null),
};
