function t(o,n){return`
        <div class="preview-gallery-stack-block">
            <p class="preview-gallery-note"><strong>${o}</strong></p>
            <div class="formie-page-buttons" data-formie-page-buttons data-formie-buttons-position="left">
                <div class="formie-button-container">
                    ${n}
                </div>
            </div>
        </div>
    `}const e={minHeight:560,markup:String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="buttons-variants-demo">
            <div class="preview-gallery-stack-block">
                ${t("Default",`
                    <button type="button" class="formie-button">Default button</button>
                    <button type="button" class="formie-button">Review details</button>
                    <button type="button" class="formie-button" disabled>Disabled</button>
                `)}
                ${t("Primary",`
                    <button type="button" class="formie-button formie-button-primary">Primary button</button>
                    <button type="button" class="formie-button formie-button-primary">Save changes</button>
                    <button type="button" class="formie-button formie-button-primary" disabled>Disabled</button>
                `)}
                ${t("Secondary",`
                    <button type="button" class="formie-button formie-button-secondary">Secondary button</button>
                    <button type="button" class="formie-button formie-button-secondary">Review details</button>
                    <button type="button" class="formie-button formie-button-secondary" disabled>Disabled</button>
                `)}
                ${t("Ghost",`
                    <button type="button" class="formie-button formie-button-ghost">Ghost button</button>
                    <button type="button" class="formie-button formie-button-ghost">Skip for now</button>
                    <button type="button" class="formie-button formie-button-ghost" disabled>Disabled</button>
                `)}
            </div>
        </form>
    `};export{e as default};
