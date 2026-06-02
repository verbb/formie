import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

const preview: FormiePreviewSourceDefinition = {
    minHeight: 260,
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="multi-line-text-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field" data-formie-field data-formie-field-handle="projectBrief" data-formie-field-type="multi-line-text" data-formie-input-id="demo-project-brief">
                                            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                                                <label class="formie-label formie-field-label" for="demo-project-brief" data-formie-label data-formie-field-label>Project brief</label>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <textarea id="demo-project-brief" class="formie-textarea formie-input" name="fields[projectBrief]" rows="5" placeholder="Tell us about the project goals, scope, and audience." data-formie-input data-formie-input-id="demo-project-brief" data-formie-input-type="textarea"></textarea>
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
