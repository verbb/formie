import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

function renderButtonExample(label: string, position: string, buttons: string): string {
    return `
        <div class="preview-gallery-stack-block">
            <p class="preview-gallery-note"><strong>${label}</strong></p>
            <div class="formie-page-buttons" data-formie-page-buttons data-formie-buttons-position="${position}">
                <div class="formie-button-container">
                    ${buttons}
                </div>
            </div>
        </div>
    `;
}

const buttonSet = `
    <button type="button" class="formie-button formie-button-back formie-button-secondary" data-formie-action="back">Back</button>
    <button type="button" class="formie-button formie-button-submit formie-button-primary" data-formie-action="submit">Submit</button>
    <button type="button" class="formie-button formie-button-save formie-button-secondary" data-formie-action="save">Save</button>
`;

const preview: FormiePreviewSourceDefinition = {
    minHeight: 1050,
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="buttons-positions-demo">
            <div class="preview-gallery-stack-block">
                ${renderButtonExample('Left', 'left', buttonSet)}
                ${renderButtonExample('Right', 'right', buttonSet)}
                ${renderButtonExample('Center', 'center', buttonSet)}
                ${renderButtonExample('Left / right split', 'left-right', `
                    <button type="button" class="formie-button formie-button-back formie-button-secondary" data-formie-action="back">Back</button>
                    <button type="button" class="formie-button formie-button-submit formie-button-primary" data-formie-action="submit">Next</button>
                `)}
                ${renderButtonExample('Save right', 'save-right', buttonSet)}
                ${renderButtonExample('Save left', 'save-left', buttonSet)}
                ${renderButtonExample('Right with save left', 'right-save-left', buttonSet)}
                ${renderButtonExample('Center with save left', 'center-save-left', buttonSet)}
                ${renderButtonExample('Center with save right', 'center-save-right', buttonSet)}
            </div>
        </form>
    `,
};

export default preview;
