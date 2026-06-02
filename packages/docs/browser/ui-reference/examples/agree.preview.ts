import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

const preview: FormiePreviewSourceDefinition = {
    minHeight: 260,
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="agree-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field formie-agree-field" data-formie-field data-formie-field-handle="terms" data-formie-field-type="agree" data-formie-input-id="terms-agree">
                                            <fieldset class="formie-field-layout formie-agree-field-layout formie-layout-vertical formie-field-layout-label-above" data-formie-field-layout data-formie-agree-field-layout data-formie-layout="vertical" data-formie-label-position="above">
                                                <legend class="formie-label formie-field-label formie-agree-field-label" data-formie-label data-formie-field-label data-formie-agree-field-label>Consent</legend>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <div class="formie-field-options formie-agree-options formie-layout-vertical" data-formie-field-options data-formie-agree-options data-formie-layout="vertical">
                                                            <input type="hidden" name="fields[terms]" value="" data-formie-input-type="agree">
                                                            <div class="formie-field-option formie-checkbox-option formie-agree-option" data-formie-field-option data-formie-checkbox-option data-formie-agree-option>
                                                                <input id="terms-agree" class="formie-input formie-checkbox-input formie-agree-input" type="checkbox" name="fields[terms]" value="1" data-formie-input data-formie-checkbox-input data-formie-agree-input data-formie-input-id="terms" data-formie-input-type="agree">
                                                                <label class="formie-field-option-label formie-checkbox-option-label formie-agree-option-label" for="terms-agree" data-formie-field-option-label data-formie-checkbox-option-label data-formie-agree-option-label>I agree to the privacy policy and project contact terms.</label>
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
