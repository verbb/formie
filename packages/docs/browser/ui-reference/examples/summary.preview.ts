import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

const preview: FormiePreviewSourceDefinition = {
    minHeight: 260,
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="summary-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field" data-formie-field data-formie-field-handle="summary" data-formie-field-type="summary">
                                            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <input type="hidden" value="demo-access-token" data-formie-summary-token>
                                                        <div class="formie-summary-container" data-formie-summary-container>
                                                            <div class="formie-summary-blocks" data-formie-summary-blocks>
                                                                <div class="formie-summary-block">
                                                                    <strong class="formie-summary-heading">Contact details</strong>
                                                                    <div>Jane Appleseed</div>
                                                                    <div>jane@example.com</div>
                                                                </div>
                                                                <div class="formie-summary-block">
                                                                    <strong class="formie-summary-heading">Services</strong>
                                                                    <div>Design, Development</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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
