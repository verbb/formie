import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

const preview: FormiePreviewSourceDefinition = {
    minHeight: 420,
    modules: [
        {
            id: 'calculations',
            type: 'field',
            targets: [{ targetType: 'field', targetId: 'estimateTotal' }],
            config: {
                options: {
                    formula: {
                        expression: 'a + b',
                        variables: {
                            a: {
                                sourceKey: 'fieldA',
                            },
                            b: {
                                sourceKey: 'fieldB',
                            },
                        },
                    },
                    formatting: 'number',
                    decimals: 0,
                },
            },
        },
    ],
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="calculations-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field" data-formie-field data-formie-field-handle="fieldA" data-formie-field-type="single-line-text" data-formie-input-id="demo-field-a">
                                            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                                                <label class="formie-label formie-field-label" for="demo-field-a" data-formie-label data-formie-field-label>Field A</label>
                                                <div id="demo-field-a-instructions" class="formie-instructions" data-formie-instructions>Enter the first number used in the calculation.</div>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <input id="demo-field-a" type="text" class="formie-input formie-single-line-text-input" name="fields[fieldA]" value="10" placeholder="10" aria-describedby="demo-field-a-instructions" data-formie-input data-formie-single-line-text-input data-formie-input-id="demo-field-a" data-formie-input-type="text">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field" data-formie-field data-formie-field-handle="fieldB" data-formie-field-type="single-line-text" data-formie-input-id="demo-field-b">
                                            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                                                <label class="formie-label formie-field-label" for="demo-field-b" data-formie-label data-formie-field-label>Field B</label>
                                                <div id="demo-field-b-instructions" class="formie-instructions" data-formie-instructions>Enter the second number used in the calculation.</div>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <input id="demo-field-b" type="text" class="formie-input formie-single-line-text-input" name="fields[fieldB]" value="15" placeholder="15" aria-describedby="demo-field-b-instructions" data-formie-input data-formie-single-line-text-input data-formie-input-id="demo-field-b" data-formie-input-type="text">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field" data-formie-field data-formie-field-handle="estimateTotal" data-formie-field-type="calculations" data-formie-input-id="demo-estimate-total">
                                            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                                                <label class="formie-label formie-field-label" for="demo-estimate-total" data-formie-label data-formie-field-label>Estimated total</label>
                                                <div id="demo-estimate-total-instructions" class="formie-instructions" data-formie-instructions>This value updates live using the formula: Field A + Field B.</div>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <input id="demo-estimate-total" type="text" class="formie-input formie-calculations-input" name="fields[estimateTotal]" value="25" readonly aria-describedby="demo-estimate-total-instructions" data-formie-input data-formie-calculation-input data-formie-input-id="demo-estimate-total" data-formie-input-type="text">
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
