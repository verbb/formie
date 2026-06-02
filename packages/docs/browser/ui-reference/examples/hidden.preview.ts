import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

const preview: FormiePreviewSourceDefinition = {
    minHeight: 140,
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="hidden-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-field-note">Hidden fields do not render visible controls, but they still participate in the form DOM and runtime.</div>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field formie-field-hidden" data-formie-field data-formie-field-handle="campaignId" data-formie-field-type="hidden">
                                            <input type="hidden" name="fields[campaignId]" value="spring-launch" data-formie-hidden-input>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </form>
    `,
};

export default preview;
