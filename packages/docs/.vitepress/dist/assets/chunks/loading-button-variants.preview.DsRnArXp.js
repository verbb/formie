const t={minHeight:180,markup:String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="loading-button-variants-demo">
            <div class="formie-page-buttons" data-formie-page-buttons data-formie-buttons-position="left">
                <div class="formie-button-container">
                    <button type="button" class="formie-button formie-loading" data-formie-loading="true" data-formie-loading-indicator="spinner">Default</button>
                    <button type="button" class="formie-button formie-button-primary formie-loading" data-formie-loading="true" data-formie-loading-indicator="spinner">Primary</button>
                    <button type="button" class="formie-button formie-button-secondary formie-loading" data-formie-loading="true" data-formie-loading-indicator="spinner">Secondary</button>
                    <button type="button" class="formie-button formie-button-ghost formie-loading" data-formie-loading="true" data-formie-loading-indicator="spinner">Ghost</button>
                </div>
            </div>
        </form>
    `};export{t as default};
