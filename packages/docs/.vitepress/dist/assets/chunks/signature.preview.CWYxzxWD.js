const e={minHeight:320,modules:[{id:"signature",type:"field",targets:[{targetType:"field",targetId:"signature"}]}],markup:String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="signature-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field" data-formie-field data-formie-field-handle="signature" data-formie-field-type="signature">
                                            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                                                <label class="formie-label formie-field-label" data-formie-label data-formie-field-label>Signature</label>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <div class="formie-signature-pad" data-formie-signature-pad>
                                                            <canvas class="formie-signature-canvas" data-formie-signature-canvas width="640" height="180" style="display:block; width:100%; height:180px;"></canvas>
                                                            <p class="formie-signature-message" data-formie-signature-message data-formie-signature-message-no-canvas="This browser does not support canvas, which is required for signatures." data-formie-signature-message-init-failed="The signature pad could not be loaded. Try refreshing the page." role="status" hidden></p>
                                                        </div>
                                                        <input type="hidden" name="fields[signature]" data-formie-signature-input>
                                                        <button type="button" class="formie-button formie-button-icon formie-signature-remove-button" data-formie-icon="close" data-formie-remove-button data-formie-signature-clear aria-label="Clear signature" title="Clear signature">Clear signature</button>
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
