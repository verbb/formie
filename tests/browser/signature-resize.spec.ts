import { expect, test, type Page } from '@playwright/test';
import path from 'node:path';

// Signature canvas sizing against the production frontend bundle and default theme. No Craft fixture needed.
const dist = path.resolve('src/web/assets/frontend/dist');
const origin = 'https://signature-resize.example.test';
const manifest = JSON.stringify({ contractVersion: 2, surface: 'server-rendered', entries: [
    { key: 'signature', moduleId: 'formie:signature', kind: 'field', targets: [{ type: 'field', uid: 'signature' }], config: {}, required: true },
] });

declare global {
    interface Window {
        canvasWidthSets: number;
    }
}

async function openForm(page: Page, css = '') {
    // Every assignment to canvas.width is a resize pass that clears the pad.
    await page.addInitScript(() => {
        window.canvasWidthSets = 0;
        const descriptor = Object.getOwnPropertyDescriptor(HTMLCanvasElement.prototype, 'width')!;
        Object.defineProperty(HTMLCanvasElement.prototype, 'width', {
            configurable: true,
            get() {
                return descriptor.get!.call(this);
            },
            set(value) {
                window.canvasWidthSets++;
                descriptor.set!.call(this, value);
            },
        });
    });
    await page.route(`${origin}/**`, async (route) => {
        const url = new URL(route.request().url());
        if (url.pathname.startsWith('/assets/')) {
            await route.fulfill({ path: path.join(dist, url.pathname.slice('/assets/'.length)), contentType: url.pathname.endsWith('.css') ? 'text/css' : 'application/javascript' });
            return;
        }
        await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head>
            <link rel="stylesheet" href="/assets/css/formie-base.css"><link rel="stylesheet" href="/assets/css/formie-theme.css">
            <style>${css}</style></head><body><main id="main">
            <form class="formie-form" data-formie data-formie-handle="contact" data-formie-automatic-submission-state="false" data-formie-modules='${manifest}'>
            <div class="formie-field" data-formie-field data-formie-field-handle="signature" data-formie-field-uid="signature" data-formie-field-type="signature">
            <div class="formie-field-control" data-formie-field-control><input type="hidden" name="fields[signature]" value="" data-formie-signature-input>
            <div class="formie-signature-pad" data-formie-signature-pad><canvas class="formie-signature-canvas" data-formie-signature-canvas></canvas>
            <p class="formie-signature-message" role="status" hidden data-formie-signature-message></p></div>
            <button type="button" class="formie-button formie-signature-remove-button" data-formie-signature-clear aria-label="Clear signature">Clear</button>
            </div></div></form></main>
            <script type="module" src="/assets/js/formie.js" data-formie-startup></script></body></html>` });
    });
    await page.goto(origin);
    await expect(page.locator('style[data-formie-module-style="signature"]')).toHaveCount(1);
    await expect.poll(() => page.evaluate(() => window.canvasWidthSets)).toBeGreaterThan(0);
}

const canvasState = (page: Page) => page.locator('canvas').evaluate((canvas: HTMLCanvasElement) => ({
    height: Math.round(canvas.getBoundingClientRect().height),
    backing: `${canvas.width}x${canvas.height}`,
    widthSets: window.canvasWidthSets,
    value: (document.querySelector('[data-formie-signature-input]') as HTMLInputElement).value,
    ink: (() => {
        const pixels = canvas.getContext('2d')!.getImageData(0, 0, canvas.width, canvas.height).data;
        let count = 0;
        for (let i = 3; i < pixels.length; i += 4) {
            count += pixels[i] > 0 ? 1 : 0;
        }
        return count;
    })(),
}));

async function draw(page: Page, offset: number, midStroke?: () => Promise<void>) {
    const box = (await page.locator('canvas').boundingBox())!;
    await page.mouse.move(box.x + 20 + offset, box.y + 30);
    await page.mouse.down();
    for (let i = 1; i <= 8; i++) {
        await page.mouse.move(box.x + 20 + offset + i * 12, box.y + 30 + (i % 2) * 25, { steps: 2 });
        if (i === 4 && midStroke) {
            await midStroke();
        }
    }
    await page.mouse.up();
}

test.use({ viewport: { width: 1000, height: 900 } });

test('the default theme canvas settles at the token height in one pass', async ({ page }) => {
    await openForm(page);
    await page.waitForTimeout(1500);
    const settled = await canvasState(page);
    expect(settled.height).toBe(128);
    expect(settled.widthSets).toBeLessThanOrEqual(2);
    await page.waitForTimeout(500);
    expect(await canvasState(page)).toEqual(settled);
});

test('a canvas with height auto and a border still settles', async ({ page }) => {
    await openForm(page, '[data-formie-field-type="signature"] .formie-signature-canvas { height: auto; }');
    await page.waitForTimeout(1500);
    const settled = await canvasState(page);
    expect(settled.widthSets).toBeLessThanOrEqual(3);
    await page.waitForTimeout(500);
    expect(await canvasState(page)).toEqual(settled);
});

test('a resize during a stroke keeps the stroke and the pad keeps drawing', async ({ page }) => {
    await openForm(page);
    await page.waitForTimeout(500);
    const before = await canvasState(page);
    await draw(page, 0, async () => {
        await page.locator('#main').evaluate((main) => {
            main.style.maxWidth = '700px';
        });
        await page.waitForTimeout(200);
    });
    await page.waitForTimeout(300);
    const first = await canvasState(page);
    expect(first.value).toMatch(/^data:image\/png;base64,/);
    // The resize waited for the stroke to end, then redrew the signature at the new size.
    expect(first.widthSets).toBeGreaterThan(before.widthSets);
    expect(first.backing).not.toBe(before.backing);
    // The first stroke is still on the pad after that resize.
    expect(first.ink).toBeGreaterThan(0);
    await draw(page, 150);
    await page.waitForTimeout(100);
    const second = await canvasState(page);
    expect(second.value).toMatch(/^data:image\/png;base64,/);
    expect(second.value).not.toBe(first.value);
});

test('a resize that keeps the canvas size does not redraw a signature', async ({ page }) => {
    await openForm(page);
    await page.waitForTimeout(500);
    await draw(page, 0);
    await page.waitForTimeout(300);
    const signed = await canvasState(page);
    await page.setViewportSize({ width: 1000, height: 700 });
    await page.evaluate(() => window.dispatchEvent(new Event('resize')));
    await page.waitForTimeout(300);
    expect(await canvasState(page)).toEqual(signed);
});
