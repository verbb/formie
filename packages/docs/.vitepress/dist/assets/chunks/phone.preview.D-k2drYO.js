const e={minHeight:420,modules:[{id:"phone-country",type:"field",targets:[{targetType:"field",targetId:"phoneNumberWithCountry"}],config:{options:{countryDefaultValue:"US",countryAllowed:["US","CA","GB"]}}}],markup:String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="phone-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field" data-formie-field data-formie-field-handle="phoneNumber" data-formie-field-type="phone" data-formie-input-id="demo-phone-number">
                                            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                                                <label class="formie-label formie-field-label" for="demo-phone-number" data-formie-label data-formie-field-label>Phone number</label>
                                                <div id="demo-phone-number-instructions" class="formie-instructions" data-formie-instructions>Basic phone input without the country picker enabled.</div>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <input id="demo-phone-number" type="tel" class="formie-input" name="fields[phoneNumber]" value="+1 415 555 0123" placeholder="+1 415 555 0123" aria-describedby="demo-phone-number-instructions" data-formie-input data-formie-phone-input data-formie-input-id="demo-phone-number" data-formie-input-type="tel">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field" data-formie-field data-formie-field-handle="phoneNumberWithCountry" data-formie-field-type="phone" data-formie-input-id="demo-phone-number-country">
                                            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                                                <label class="formie-label formie-field-label" for="demo-phone-number-country" data-formie-label data-formie-field-label>Phone number with country</label>
                                                <div id="demo-phone-number-country-instructions" class="formie-instructions" data-formie-instructions>Country setting enabled. This example boots the <code>phone-country</code> module and keeps the hidden country input in sync.</div>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <input id="demo-phone-number-country" type="tel" class="formie-input" name="fields[phoneNumberWithCountry][value]" value="+1 415 555 0188" placeholder="+1 415 555 0188" aria-describedby="demo-phone-number-country-instructions" data-formie-input data-formie-phone-input data-formie-input-id="demo-phone-number-country" data-formie-input-type="tel">
                                                        <input type="hidden" name="fields[phoneNumberWithCountry][country]" value="US" data-formie-phone-country-input>
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
    `};export{e as default};
