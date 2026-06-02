function t(o,i,a){return`
        <div class="preview-gallery-stack-block">
            <p class="preview-gallery-note"><strong>${o}</strong></p>
            <div class="formie-page-buttons" data-formie-page-buttons data-formie-buttons-position="${i}">
                <div class="formie-button-container">
                    ${a}
                </div>
            </div>
        </div>
    `}const e=`
    <button type="button" class="formie-button formie-button-back formie-button-secondary" data-formie-action="back">Back</button>
    <button type="button" class="formie-button formie-button-submit formie-button-primary" data-formie-action="submit">Submit</button>
    <button type="button" class="formie-button formie-button-save formie-button-secondary" data-formie-action="save">Save</button>
`,r={minHeight:1050,markup:String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="buttons-positions-demo">
            <div class="preview-gallery-stack-block">
                ${t("Left","left",e)}
                ${t("Right","right",e)}
                ${t("Center","center",e)}
                ${t("Left / right split","left-right",`
                    <button type="button" class="formie-button formie-button-back formie-button-secondary" data-formie-action="back">Back</button>
                    <button type="button" class="formie-button formie-button-submit formie-button-primary" data-formie-action="submit">Next</button>
                `)}
                ${t("Save right","save-right",e)}
                ${t("Save left","save-left",e)}
                ${t("Right with save left","right-save-left",e)}
                ${t("Center with save left","center-save-left",e)}
                ${t("Center with save right","center-save-right",e)}
            </div>
        </form>
    `};export{r as default};
