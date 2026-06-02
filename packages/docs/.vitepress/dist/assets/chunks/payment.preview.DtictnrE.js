const e={minHeight:340,markup:String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="payment-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field" data-formie-field data-formie-field-handle="payment" data-formie-field-type="payment">
                                            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                                                <label class="formie-label formie-field-label" data-formie-label data-formie-field-label>Payment details</label>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <div class="formie-summary-blocks">
                                                            <div class="formie-summary-block">
                                                                <strong class="formie-summary-heading">Amount due</strong>
                                                                <div>$125.00</div>
                                                            </div>
                                                            <div class="formie-summary-block">
                                                                <div data-formie-stripe-elements-placeholder>
                                                                    <input class="formie-input" type="text" placeholder="Card number">
                                                                </div>
                                                            </div>
                                                            <div class="formie-subfield-row">
                                                                <input class="formie-input" type="text" placeholder="MM / YY">
                                                                <input class="formie-input" type="text" placeholder="CVC">
                                                            </div>
                                                            <div data-formie-paypal-button>PayPal button surface</div>
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
    `};export{e as default};
