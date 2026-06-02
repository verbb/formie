const i={minHeight:250,markup:String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="buttons-loading-demo" data-formie-loading="true">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field" data-formie-field data-formie-field-type="single-line-text">
                                            <div class="formie-field-layout" data-formie-field-layout data-formie-label-position="above">
                                                <label class="formie-label formie-field-label" data-formie-label data-formie-field-label>Email</label>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <input type="email" class="formie-input" placeholder="jane@example.com" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="formie-page-footer" data-formie-page-footer>
                            <div class="formie-page-buttons" data-formie-page-buttons data-formie-buttons-position="left">
                                <div class="formie-button-container" data-formie-button-container data-formie-buttons-position="left">
                                    <button type="submit" name="submitAction" value="submit" class="formie-button formie-button-submit formie-button-primary formie-loading" data-formie-action="submit" data-formie-loading="true" data-formie-loading-indicator="spinner">Submit</button>
                                    <button type="submit" name="submitAction" value="back" class="formie-button formie-button-back formie-loading" data-formie-action="back" data-formie-loading="true" data-formie-loading-indicator="spinner">Back</button>
                                    <button type="button" class="formie-button formie-button-ghost">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </form>
    `};export{i as default};
