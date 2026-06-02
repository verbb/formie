const e={minHeight:390,modules:[{id:"repeater",type:"field",targets:[{targetType:"field",targetId:"experience"}]}],markup:String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="repeater-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    <div class="formie-row" data-formie-row data-formie-field-count="1">
                                        <div class="formie-field formie-repeater-field" data-formie-field data-formie-field-handle="experience" data-formie-field-type="repeater">
                                            <fieldset class="formie-field-layout formie-repeater-field-layout" data-formie-field-layout data-formie-repeater-field-layout data-formie-template-id="experience-template">
                                                <legend class="formie-label formie-field-label formie-repeater-field-label" data-formie-label data-formie-field-label data-formie-repeater-field-label>Previous roles</legend>
                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <div class="formie-nested-field-container formie-repeater-container" data-formie-nested-field-container data-formie-repeater-container data-formie-template-id="experience-template">
                                                            <div class="formie-nested-field formie-repeater-item" data-formie-nested-field data-formie-repeater-item="0" data-formie-repeater-item-id="0">
                                                                <fieldset class="formie-repeater-item-wrapper" data-formie-repeater-item-wrapper>
                                                                    <button type="button" class="formie-button formie-button-icon formie-repeater-remove-button" data-formie-icon="close" data-formie-remove-button data-formie-repeater-remove="experience" aria-label="Remove row" title="Remove row">Remove</button>
                                                                    <div class="formie-nested-field-rows">
                                                                        <div class="formie-nested-field-row" data-formie-nested-field-row>
                                                                            <div class="formie-field formie-field-nested" data-formie-field data-formie-field-handle="company">
                                                                                <input class="formie-input" type="text" name="fields[experience][0][company]" placeholder="Company">
                                                                            </div>
                                                                            <div class="formie-field formie-field-nested" data-formie-field data-formie-field-handle="role">
                                                                                <input class="formie-input" type="text" name="fields[experience][0][role]" placeholder="Role">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </fieldset>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="formie-button formie-button-back formie-repeater-add-button" data-formie-add-button data-formie-repeater-add="experience" data-formie-template-id="experience-template">Add another</button>
                                                        <template id="experience-template" data-formie-template-id="experience-template" data-formie-repeater-template="experience">
                                                            <div class="formie-nested-field formie-repeater-item" data-formie-nested-field data-formie-repeater-item="__ROW__" data-formie-repeater-item-id="__ROW__">
                                                                <fieldset class="formie-repeater-item-wrapper" data-formie-repeater-item-wrapper>
                                                                    <button type="button" class="formie-button formie-button-icon formie-repeater-remove-button" data-formie-icon="close" data-formie-remove-button data-formie-repeater-remove="experience" aria-label="Remove row" title="Remove row">Remove</button>
                                                                    <div class="formie-nested-field-rows">
                                                                        <div class="formie-nested-field-row" data-formie-nested-field-row>
                                                                            <div class="formie-field formie-field-nested" data-formie-field data-formie-field-handle="company">
                                                                                <input class="formie-input" type="text" name="fields[experience][__ROW__][company]" placeholder="Company">
                                                                            </div>
                                                                            <div class="formie-field formie-field-nested" data-formie-field data-formie-field-handle="role">
                                                                                <input class="formie-input" type="text" name="fields[experience][__ROW__][role]" placeholder="Role">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </fieldset>
                                                            </div>
                                                        </template>
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
