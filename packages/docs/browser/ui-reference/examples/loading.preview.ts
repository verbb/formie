import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

const preview: FormiePreviewSourceDefinition = {
    minHeight: 210,
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="loading-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field" data-formie-field data-formie-field-type="summary">
                                            <div class="formie-field-layout" data-formie-field-layout data-formie-label-position="above">
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <div style="display:flex; gap:16px; align-items:center;">
                                                            <span class="formie-loading" role="status" aria-label="Loading" style="display:inline-block; width:40px; height:40px; --formie-loading-color: var(--formie-color-primary);"></span>
                                                            <span class="formie-loading" role="status" aria-label="Loading" style="display:inline-block; width:40px; height:40px; --formie-loading-color: var(--formie-color-success);"></span>
                                                            <span class="formie-loading" role="status" aria-label="Loading" style="display:inline-block; width:40px; height:40px; --formie-loading-color: var(--formie-color-danger);"></span>
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
