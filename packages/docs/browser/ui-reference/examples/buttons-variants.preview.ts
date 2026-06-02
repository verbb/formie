import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

function renderButtonGroup(label: string, buttons: string): string {
    return `
        <div class="preview-gallery-stack-block">
            <p class="preview-gallery-note"><strong>${label}</strong></p>
            <div class="formie-page-buttons" data-formie-page-buttons data-formie-buttons-position="left">
                <div class="formie-button-container">
                    ${buttons}
                </div>
            </div>
        </div>
    `;
}

const preview: FormiePreviewSourceDefinition = {
    minHeight: 560,
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="buttons-variants-demo">
            <div class="preview-gallery-stack-block">
                ${renderButtonGroup('Default', `
                    <button type="button" class="formie-button">Default button</button>
                    <button type="button" class="formie-button">Review details</button>
                    <button type="button" class="formie-button" disabled>Disabled</button>
                `)}
                ${renderButtonGroup('Primary', `
                    <button type="button" class="formie-button formie-button-primary">Primary button</button>
                    <button type="button" class="formie-button formie-button-primary">Save changes</button>
                    <button type="button" class="formie-button formie-button-primary" disabled>Disabled</button>
                `)}
                ${renderButtonGroup('Secondary', `
                    <button type="button" class="formie-button formie-button-secondary">Secondary button</button>
                    <button type="button" class="formie-button formie-button-secondary">Review details</button>
                    <button type="button" class="formie-button formie-button-secondary" disabled>Disabled</button>
                `)}
                ${renderButtonGroup('Ghost', `
                    <button type="button" class="formie-button formie-button-ghost">Ghost button</button>
                    <button type="button" class="formie-button formie-button-ghost">Skip for now</button>
                    <button type="button" class="formie-button formie-button-ghost" disabled>Disabled</button>
                `)}
            </div>
        </form>
    `,
};

export default preview;
