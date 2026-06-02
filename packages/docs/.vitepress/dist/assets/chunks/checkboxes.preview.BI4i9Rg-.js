const e={minHeight:320,markup:String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="checkboxes-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field" data-formie-field data-formie-field-handle="services" data-formie-field-type="checkboxes" data-formie-max-options="2">
                                            <fieldset class="formie-field-layout formie-checkboxes-field-layout formie-layout-vertical formie-field-layout-label-above" data-formie-field-layout data-formie-checkboxes-field-layout data-formie-layout="vertical" data-formie-label-position="above">
                                                <legend class="formie-label formie-field-label formie-checkboxes-field-label" data-formie-label data-formie-field-label data-formie-checkboxes-field-label>Services</legend>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <input type="hidden" name="fields[services]" value="">
                                                        <div class="formie-field-options formie-checkboxes-options formie-layout-vertical" data-formie-field-options data-formie-checkboxes-options data-formie-layout="vertical">
                                                            <div class="formie-field-option formie-checkbox-option" data-formie-field-option data-formie-checkbox-option>
                                                                <input id="services-design" class="formie-input formie-checkbox-input" type="checkbox" name="fields[services][]" value="design" checked data-formie-input data-formie-checkbox-input data-formie-input-id="services-design" data-formie-input-type="checkbox">
                                                                <label class="formie-field-option-label formie-checkbox-option-label" for="services-design" data-formie-field-option-label data-formie-checkbox-option-label>Design</label>
                                                            </div>
                                                            <div class="formie-field-option formie-checkbox-option" data-formie-field-option data-formie-checkbox-option>
                                                                <input id="services-development" class="formie-input formie-checkbox-input" type="checkbox" name="fields[services][]" value="development" data-formie-input data-formie-checkbox-input data-formie-input-id="services-development" data-formie-input-type="checkbox">
                                                                <label class="formie-field-option-label formie-checkbox-option-label" for="services-development" data-formie-field-option-label data-formie-checkbox-option-label>Development</label>
                                                            </div>
                                                            <div class="formie-field-option formie-checkbox-option" data-formie-field-option data-formie-checkbox-option>
                                                                <input id="services-seo" class="formie-input formie-checkbox-input" type="checkbox" name="fields[services][]" value="seo" data-formie-input data-formie-checkbox-input data-formie-input-id="services-seo" data-formie-input-type="checkbox">
                                                                <label class="formie-field-option-label formie-checkbox-option-label" for="services-seo" data-formie-field-option-label data-formie-checkbox-option-label>SEO</label>
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
    `};export{e as default};
