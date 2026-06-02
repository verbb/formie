import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

const preview: FormiePreviewSourceDefinition = {
    minHeight: 390,
    modules: [
        {
            id: 'table',
            type: 'field',
            targets: [
                {
                    targetType: 'field',
                    targetId: 'links',
                },
            ],
        },
    ],
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="table-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field formie-table-field" data-formie-field data-formie-field-handle="links" data-formie-field-type="table">
                                            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above formie-table-field-layout" data-formie-field-layout data-formie-table-field-layout data-formie-label-position="above" data-formie-instructions-position="above" data-formie-template-id="links-template">
                                                <label class="formie-label formie-field-label" data-formie-label data-formie-field-label>Useful links</label>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <div class="formie-table-wrapper">
                                                            <table class="formie-table" data-formie-table data-formie-template-id="links-template">
                                                                <thead data-formie-table-header>
                                                                    <tr data-formie-table-header-row>
                                                                        <th data-formie-table-header-column data-formie-table-column-handle="label">Label</th>
                                                                        <th data-formie-table-header-column data-formie-table-column-handle="value">Value</th>
                                                                        <th data-col-remove data-formie-table-header-column data-formie-table-column-handle="remove"></th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody data-formie-table-body>
                                                                    <tr data-formie-table-row data-formie-table-row-id="0">
                                                                        <td data-formie-table-body-column><input class="formie-input" type="text" value="Homepage"></td>
                                                                        <td data-formie-table-body-column><input class="formie-input" type="url" value="https://example.com"></td>
                                                                        <td data-col-remove data-formie-table-body-column><button type="button" class="formie-button formie-button-back formie-table-remove-button" data-formie-table-remove="links">Remove</button></td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                            <button type="button" class="formie-button formie-button-back formie-table-add-button" data-formie-table-add="links" data-formie-template-id="links-template">Add row</button>
                                                            <template id="links-template" data-formie-template-id="links-template" data-formie-table-template="links">
                                                                <td data-formie-table-body-column><input class="formie-input" type="text" value=""></td>
                                                                <td data-formie-table-body-column><input class="formie-input" type="url" value=""></td>
                                                                <td data-col-remove data-formie-table-body-column><button type="button" class="formie-button formie-button-back formie-table-remove-button" data-formie-table-remove="links">Remove</button></td>
                                                            </template>
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
