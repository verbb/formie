import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

const preview: FormiePreviewSourceDefinition = {
    minHeight: 300,
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="radio-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field" data-formie-field data-formie-field-handle="budgetRange" data-formie-field-type="radio">
                                            <fieldset class="formie-field-layout formie-radio-field-layout formie-layout-vertical formie-field-layout-label-above" data-formie-field-layout data-formie-radio-field-layout data-formie-layout="vertical" data-formie-label-position="above">
                                                <legend class="formie-label formie-field-label formie-radio-field-label" data-formie-label data-formie-field-label data-formie-radio-field-label>Budget range</legend>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <div class="formie-field-options formie-radio-options formie-layout-vertical" data-formie-field-options data-formie-radio-options data-formie-layout="vertical">
                                                            <div class="formie-field-option formie-radio-option" data-formie-field-option data-formie-radio-option>
                                                                <input id="budget-1" class="formie-input formie-radio-input" type="radio" name="fields[budgetRange]" value="small" checked data-formie-input data-formie-radio-input data-formie-input-id="budget-1" data-formie-input-type="radio">
                                                                <label class="formie-field-option-label formie-radio-option-label" for="budget-1" data-formie-field-option-label data-formie-radio-option-label>Under $5,000</label>
                                                            </div>
                                                            <div class="formie-field-option formie-radio-option" data-formie-field-option data-formie-radio-option>
                                                                <input id="budget-2" class="formie-input formie-radio-input" type="radio" name="fields[budgetRange]" value="mid" data-formie-input data-formie-radio-input data-formie-input-id="budget-2" data-formie-input-type="radio">
                                                                <label class="formie-field-option-label formie-radio-option-label" for="budget-2" data-formie-field-option-label data-formie-radio-option-label>$5,000 to $20,000</label>
                                                            </div>
                                                            <div class="formie-field-option formie-radio-option" data-formie-field-option data-formie-radio-option>
                                                                <input id="budget-3" class="formie-input formie-radio-input" type="radio" name="fields[budgetRange]" value="large" data-formie-input data-formie-radio-input data-formie-input-id="budget-3" data-formie-input-type="radio">
                                                                <label class="formie-field-option-label formie-radio-option-label" for="budget-3" data-formie-field-option-label data-formie-radio-option-label>Over $20,000</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </fieldset>
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
