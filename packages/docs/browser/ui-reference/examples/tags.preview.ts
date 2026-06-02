import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

const preview: FormiePreviewSourceDefinition = {
    minHeight: 220,
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="tags-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field" data-formie-field data-formie-field-handle="topics" data-formie-field-type="tags" data-formie-input-id="demo-topics">
                                            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                                                <label class="formie-label formie-field-label" for="demo-topics" data-formie-label data-formie-field-label>Topics</label>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <input id="demo-topics" type="text" class="formie-input" name="fields[topics]" value="Design, Development, SEO" placeholder="Design, Development, SEO" data-formie-input data-formie-input-id="demo-topics" data-formie-input-type="text" data-formie-tags='["Design","Development","SEO"]'>
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
