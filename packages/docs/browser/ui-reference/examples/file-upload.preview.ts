import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

const preview: FormiePreviewSourceDefinition = {
    minHeight: 320,
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="file-upload-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div
                                            class="formie-field"
                                            data-formie-field
                                            data-formie-field-handle="attachments"
                                            data-formie-field-type="file-upload"
                                            data-formie-input-id="demo-attachments"
                                        >
                                            <div
                                                class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above"
                                                data-formie-field-layout
                                                data-formie-label-position="above"
                                                data-formie-instructions-position="above"
                                            >
                                                <label
                                                    class="formie-label formie-field-label"
                                                    for="demo-attachments"
                                                    data-formie-label
                                                    data-formie-field-label
                                                >
                                                    Attachments
                                                </label>

                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <input
                                                            id="demo-attachments"
                                                            type="file"
                                                            class="formie-input formie-file-input"
                                                            name="fields[attachments][]"
                                                            data-formie-input
                                                            data-formie-file-input
                                                            data-formie-input-id="demo-attachments"
                                                            data-formie-input-type="file"
                                                            data-formie-file-upload-key="demo-attachments"
                                                            data-formie-file-limit="3"
                                                            data-formie-size-max-limit="10"
                                                        />

                                                        <input
                                                            type="hidden"
                                                            name="fields[attachments]"
                                                            value=""
                                                            data-formie-file-upload-anchor="true"
                                                        />

                                                        <input
                                                            type="hidden"
                                                            name="fields[attachments][]"
                                                            value="101"
                                                            data-formie-file-upload-asset-id="true"
                                                        />

                                                        <input
                                                            type="hidden"
                                                            name="fields[attachments][]"
                                                            value="102"
                                                            data-formie-file-upload-asset-id="true"
                                                        />

                                                        <div class="formie-field-note formie-file-summary" data-formie-file-summary>
                                                            <ul class="formie-file-summary-container" data-formie-file-summary-container>
                                                                <li class="formie-file-summary-item" data-formie-file-summary-item>project-brief.pdf</li>
                                                                <li class="formie-file-summary-item" data-formie-file-summary-item>site-map-v2.pdf</li>
                                                            </ul>
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
