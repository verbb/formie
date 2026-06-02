import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

const preview: FormiePreviewSourceDefinition = {
    minHeight: 360,
    modules: [
        {
            id: 'rich-text',
            type: 'field',
            targets: [
                {
                    targetType: 'field',
                    targetId: 'projectBrief',
                },
            ],
            config: {
                options: {
                    buttons: ['bold', 'italic', 'ulist', 'link'],
                },
            },
        },
    ],
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="multi-line-rich-text-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field" data-formie-field data-formie-field-handle="projectBrief" data-formie-field-type="multi-line-text" data-formie-input-id="demo-project-brief-rich">
                                            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                                                <label class="formie-label formie-field-label" for="demo-project-brief-rich" data-formie-label data-formie-field-label>Project brief</label>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <div class="formie-rich-text" data-formie-rich-text></div>
                                                        <textarea id="demo-project-brief-rich" class="formie-textarea formie-input" name="fields[projectBrief]" placeholder="Write the brief" data-formie-input data-formie-multi-line-text-input data-formie-input-id="demo-project-brief-rich" data-formie-input-type="textarea" hidden><p><strong>Launch goals</strong></p><p>We need a concise project brief for the marketing site refresh.</p><ul><li>Clarify the audience</li><li>List required pages</li><li>Call out launch constraints</li></ul></textarea>
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
