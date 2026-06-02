import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

type LoadingExample = {
    colorToken: string;
    label: string;
    sizeToken: string;
};

const loadingExamples: LoadingExample[] = [
    { label: 'Small / Primary', sizeToken: '--formie-space-3', colorToken: '--formie-color-primary' },
    { label: 'Default / Neutral', sizeToken: '--formie-space-4', colorToken: '--formie-color-text-muted' },
    { label: 'Large / Success', sizeToken: '--formie-space-6', colorToken: '--formie-color-success' },
    { label: 'Large / Danger', sizeToken: '--formie-space-6', colorToken: '--formie-color-danger' },
];

const preview: FormiePreviewSourceDefinition = {
    minHeight: 430,
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="loading-sizes-colors-demo">
            <div class="preview-gallery-stack-block">
                <p class="preview-gallery-note">
                    Size and color are token-driven through <span class="preview-gallery-inline-code">--formie-loading-size</span> and <span class="preview-gallery-inline-code">--formie-loading-color</span>.
                </p>
                <div class="preview-gallery-stack-block">
                    ${loadingExamples.map((item) => `
                        <div class="preview-gallery-card" style="display:flex; align-items:center; gap:12px;">
                            <span
                                class="formie-loading"
                                aria-label="${item.label}"
                                role="status"
                                style="display:inline-block; width:48px; height:48px; --formie-loading-size: var(${item.sizeToken}); --formie-loading-margin-top: calc(var(--formie-loading-size) * -0.5); --formie-loading-margin-left: calc(var(--formie-loading-size) * -0.5); --formie-loading-color: var(${item.colorToken});"
                            ></span>
                            <strong>${item.label}</strong>
                        </div>
                    `).join('')}
                </div>
            </div>
        </form>
    `,
};

export default preview;
