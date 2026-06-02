const e={minHeight:430,markup:String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="address-demo">
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
                                            data-formie-field-handle="address"
                                            data-formie-field-type="address"
                                            data-formie-input-id="demo-address-autocomplete"
                                        >
                                            <div
                                                class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above"
                                                data-formie-field-layout
                                                data-formie-label-position="above"
                                                data-formie-instructions-position="above"
                                            >
                                                <label
                                                    class="formie-label formie-field-label"
                                                    for="demo-address-autocomplete"
                                                    data-formie-label
                                                    data-formie-field-label
                                                >
                                                    Address
                                                </label>

                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <div class="formie-subfield-rows" data-formie-subfield-rows>
                                                            <div class="formie-subfield-row" data-formie-subfield-row>
                                                                <div class="formie-field formie-field-nested" data-formie-field data-formie-field-handle="addressAutocomplete">
                                                                    <div class="formie-autocomplete-wrapper">
                                                                        <input
                                                                            id="demo-address-autocomplete"
                                                                            type="text"
                                                                            class="formie-input"
                                                                            placeholder="Search address"
                                                                            data-formie-address-autocomplete-input
                                                                        />
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="formie-subfield-row" data-formie-subfield-row>
                                                                <div class="formie-field formie-field-nested" data-formie-field data-formie-field-handle="address1">
                                                                    <input type="text" class="formie-input" placeholder="Address line 1" data-formie-address-line1-input />
                                                                </div>
                                                            </div>

                                                            <div class="formie-subfield-row" data-formie-subfield-row>
                                                                <div class="formie-field formie-field-nested" data-formie-field data-formie-field-handle="address2">
                                                                    <input type="text" class="formie-input" placeholder="Address line 2" data-formie-address-line2-input />
                                                                </div>
                                                            </div>

                                                            <div class="formie-subfield-row" data-formie-subfield-row>
                                                                <div class="formie-field formie-field-nested" data-formie-field data-formie-field-handle="city">
                                                                    <input type="text" class="formie-input" placeholder="City" data-formie-address-city-input />
                                                                </div>
                                                                <div class="formie-field formie-field-nested" data-formie-field data-formie-field-handle="state">
                                                                    <input type="text" class="formie-input" placeholder="State / Province" data-formie-address-state-input />
                                                                </div>
                                                            </div>

                                                            <div class="formie-subfield-row" data-formie-subfield-row>
                                                                <div class="formie-field formie-field-nested" data-formie-field data-formie-field-handle="zip">
                                                                    <input type="text" class="formie-input" placeholder="Postal code" data-formie-address-zip-input />
                                                                </div>
                                                                <div class="formie-field formie-field-nested" data-formie-field data-formie-field-handle="country">
                                                                    <input type="text" class="formie-input" placeholder="Country" data-formie-address-country-input />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <a href="#" class="formie-address-location" data-formie-address-location onclick="event.preventDefault()">Use current location</a>
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
