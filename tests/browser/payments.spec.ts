import { test, expect } from '@playwright/test';

test('Opayo session initialization uses scoped authority and custom CSRF without sending form or card data', async ({ page }) => {
    let body = '';
    await page.route('**/actions/formie/payment-sessions/initialize', async route => {
        body = route.request().postData() || '';
        await route.fulfill({ contentType: 'application/json', body: JSON.stringify({ merchantSessionKey: 'test-session-key' }) });
    });
    await page.goto('/browser-fixture?adapter=react');
    await page.waitForFunction(() => typeof (window as any).mountPaymentBoundary === 'function');
    const mounted = await page.evaluate(async () => {
        let key = '';
        (window as any).sagepayCheckout = (options: any) => {
            key = options.merchantSessionKey;
            return { tokenise() {}, destroy() {} };
        };
        const form = document.createElement('form');
        form.action = '/payment-session-fixture';
        form.innerHTML = `<input data-formie-csrf type="hidden" name="CUSTOM_CSRF" value="csrf-proof">
            <input name="privateAnswer" value="private-answer-must-stay-local">
            <input name="cardNumber" value="synthetic-card-must-stay-local">
            <div data-formie-field-type="payment"><div data-formie-opayo-drop-in></div></div>`;
        document.body.append(form);
        const module = await (window as any).mountPaymentBoundary(form);
        await module.destroy();
        form.remove();
        return key;
    });
    expect(mounted).toBe('test-session-key');
    expect(body).toContain('formie/payment-sessions/initialize');
    expect(body).toContain('CUSTOM_CSRF');
    expect(body).toContain('csrf-proof');
    expect(body).toContain('scoped-session-token');
    expect(body).not.toContain('privateAnswer');
    expect(body).not.toContain('cardNumber');
    expect(body).not.toContain('must-stay-local');
});
