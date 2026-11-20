import { test, expect, type Page } from '@playwright/test';

// Captchas protect client-rendered forms as they protect server-rendered ones. Each provider's script is faked here and
// issues one token, and the test site's fake verification accepts only that token (browser-captcha-routes.php), so a
// submission passes only when the provider's token reached the server.
const SURFACES = ['server', 'vue', 'react', 'web-components'] as const;
const TOKEN = 'browser-captcha-token';

// reCAPTCHA (standard and Enterprise), hCaptcha and Turnstile share a render, execute, getResponse and reset shape.
// Visible widgets show a checkbox that completes the challenge when clicked.
function fakeWidgetApi(token: string) {
    return `function (onComplete) {
        var widgets = [];
        function complete(id) { var widget = widgets[id]; if (!widget) return; widget.done = true; if (widget.options.callback) widget.options.callback(${JSON.stringify(token)}); }
        return {
            ready: function (callback) { callback(); },
            render: function (container, options) {
                var id = widgets.length;
                widgets.push({ options: options || {}, done: false });
                if ((options || {}).size !== 'invisible' && onComplete !== 'auto') {
                    var box = document.createElement('button');
                    box.type = 'button';
                    box.textContent = 'I am human';
                    box.addEventListener('click', function () { complete(id); });
                    container.appendChild(box);
                }
                if (onComplete === 'auto') setTimeout(function () { complete(id); }, 10);
                return id;
            },
            execute: function (id) { if (typeof id === 'number') setTimeout(function () { complete(id); }, 10); return Promise.resolve(${JSON.stringify(token)}); },
            getResponse: function (id) { return widgets[id] && widgets[id].done ? ${JSON.stringify(token)} : ''; },
            reset: function (id) { if (widgets[id]) widgets[id].done = false; },
            remove: function () {},
        };
    }`;
}

function scripts(token: string): Record<string, { url: RegExp; body: string }> {
    return {
        recaptcha: {
            url: /(recaptcha\.net\/recaptcha\/api\.js|google\.com\/recaptcha\/enterprise\.js)/,
            body: `(function () { var make = ${fakeWidgetApi(token)}; window.grecaptcha = make(); window.grecaptcha.enterprise = make(); })();`,
        },
        hcaptcha: {
            url: /js\.hcaptcha\.com\/1\/api\.js/,
            body: `(function () { var make = ${fakeWidgetApi(token)}; window.hcaptcha = make(); var onload = new URL(document.currentScript.src).searchParams.get('onload'); if (onload && window[onload]) window[onload](); })();`,
        },
        turnstile: {
            url: /challenges\.cloudflare\.com\/turnstile/,
            body: `(function () { var make = ${fakeWidgetApi(token)}; window.turnstile = make('auto'); })();`,
        },
        captchaEu: {
            url: /captcha\.eu\/sdk\.js/,
            body: `(function () { window.KROT = { init: function () {}, setup: function () {}, WidgetV2: { render: function () {} }, on: function (name, callback) { if (name === 'CPT_OK') setTimeout(function () { callback({ detail: { token: ${JSON.stringify(token)} } }); }, 10); } }; })();`,
        },
    };
}

type Provider = { name: string; form: string; script?: string; recaptcha?: string; solve?: (page: Page) => Promise<void> };

const clickCheckbox = async (page: Page) => page.getByRole('button', { name: 'I am human' }).click();

const PROVIDERS: Provider[] = [
    { name: 'reCAPTCHA v3', form: 'captchaRecaptcha', script: 'recaptcha', recaptcha: 'v3' },
    { name: 'reCAPTCHA v2 checkbox', form: 'captchaRecaptcha', script: 'recaptcha', recaptcha: 'v2_checkbox', solve: clickCheckbox },
    { name: 'reCAPTCHA v2 invisible', form: 'captchaRecaptcha', script: 'recaptcha', recaptcha: 'v2_invisible' },
    { name: 'reCAPTCHA Enterprise', form: 'captchaRecaptcha', script: 'recaptcha', recaptcha: 'enterprise' },
    { name: 'hCaptcha', form: 'captchaHcaptcha', script: 'hcaptcha', solve: clickCheckbox },
    { name: 'Turnstile', form: 'captchaTurnstile', script: 'turnstile' },
    { name: 'Captcha.eu', form: 'captchaCaptchaEu', script: 'captchaEu' },
    { name: 'Question', form: 'captchaQuestion', solve: async (page) => page.getByRole('textbox', { name: 'What colour is a clear sky?' }).fill('Blue') },
];

async function submit(page: Page, surface: string, provider: Provider, token = TOKEN) {
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message || String(error)));

    if (provider.recaptcha) {
        expect(await (await page.request.get(`/browser-captcha-setup?recaptcha=${provider.recaptcha}`)).text()).toBe('ok');
    }

    if (provider.script) {
        const script = scripts(token)[provider.script];
        await page.route(script.url, route => route.fulfill({ contentType: 'text/javascript', body: script.body }));
    }

    await page.goto(surface === 'server'
        ? `/browser-fixture?adapter=vue&product=server&form=${provider.form}`
        : `/browser-fixture?adapter=${surface}&form=${provider.form}`);
    await page.getByRole('textbox', { name: 'Visitor name' }).fill('Ada');
    await provider.solve?.(page);
    // Faster submissions are treated as spam whatever the captcha says.
    await page.waitForTimeout(3000);

    const response = page.waitForResponse(r => r.request().method() === 'POST' && /submissions(%2F|\/)submit/.test(r.url()));
    await page.locator('button[type="submit"]').last().click();
    const body = await (await response).json();
    const saved = body.submissionUid ? await (await page.request.get(`/browser-parity-saved?uid=${body.submissionUid}`)).json() : null;

    return { body, saved, errors };
}

for (const provider of PROVIDERS) {
    test.describe(provider.name, () => {
        for (const surface of SURFACES) {
            test(`${surface}: submits with a passing captcha`, async ({ page }) => {
                const { body, saved, errors } = await submit(page, surface, provider);

                expect(saved, JSON.stringify(body).slice(0, 500)).not.toBeNull();
                expect(saved.isSpam).toBe(false);
                expect(saved.values.visitorName).toBe('Ada');
                expect(errors).toEqual([]);
            });
        }
    });
}

// The fake verification only accepts the expected token, so these pass because the token reached the server.
for (const surface of ['server', 'vue'] as const) {
    test(`${surface}: a token the provider didn't issue is caught`, async ({ page }) => {
        const { saved } = await submit(page, surface, PROVIDERS.find(provider => provider.name === 'Turnstile')!, 'some-other-token');

        expect(saved?.isSpam ?? true).toBe(true);
    });
}

// A wrong answer to the Question captcha is shown with the question, and nothing is saved.
for (const surface of SURFACES) {
    test(`${surface}: a wrong answer to the security question is shown`, async ({ page }) => {
        const question = PROVIDERS.find(provider => provider.name === 'Question')!;
        const { body, saved } = await submit(page, surface, { ...question, solve: async (target) => target.getByRole('textbox', { name: 'What colour is a clear sky?' }).fill('Green') });

        expect(saved, JSON.stringify(body).slice(0, 300)).toBeNull();
        await expect(page.getByText('Incorrect answer. Please try again.').first()).toBeVisible();
    });
}
