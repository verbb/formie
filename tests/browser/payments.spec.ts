import { test, expect } from '@playwright/test';

test('canonical redirect action supplies the browser redirect fallback without beta fields', async ({ page }) => {
    await page.route('**/synthetic-payment-response', route => route.fulfill({ contentType: 'application/json', body: JSON.stringify({
        success: false, payment: { status: 'requiresAction', action: { type: 'redirect', url: 'https://example.test/synthetic-checkout' } },
    }) }));
    await page.goto('/browser-fixture?adapter=react');
    const result = await page.evaluate(async () => {
        const form = document.createElement('form');
        form.action = '/synthetic-payment-response';
        return (window as any).paymentResponseBoundary.submitForm(form, new FormData());
    });
    expect(result.redirect).toEqual({ url: 'https://example.test/synthetic-checkout', target: 'same-tab' });
    expect(result.payment.status).toBe('requiresAction');
});

test('canonical payment decision drives one confirmation event and a neutral notice without beta response fields', async ({ page }) => {
    await page.route('**/synthetic-payment-response', route => route.fulfill({ contentType: 'application/json', body: JSON.stringify({
        success: false, outcome: 'paymentActionRequired', errors: {},
        payment: { status: 'requiresAction', message: 'Confirm the synthetic payment.', action: { type: 'confirm', event: 'formie:payment:stripe:confirm', payload: { clientSecret: 'synthetic-not-a-secret' } } },
    }) }));
    await page.goto('/browser-fixture?adapter=react');
    const result = await page.evaluate(async () => {
        const form = document.createElement('form');
        form.action = '/synthetic-payment-response';
        document.body.append(form);
        const observed: unknown[] = [];
        form.addEventListener('formie:payment:stripe:confirm', (event) => observed.push((event as CustomEvent).detail));
        const boundary = (window as any).paymentResponseBoundary;
        const result = await boundary.submitForm(form, new FormData());
        boundary.applySubmitResultState(form, result, 'submit');
        boundary.applySubmitResultUi(form, result);
        return { observed, text: form.textContent, loading: result.keepSubmitLoading, payment: result.payment, errors: result.formErrors };
    });
    expect(result.observed).toEqual([{ data: { clientSecret: 'synthetic-not-a-secret' } }]);
    expect(result.text).toContain('Confirm the synthetic payment.');
    expect(result.loading).toBe(true);
    expect(result.errors).toBeUndefined();
});

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
