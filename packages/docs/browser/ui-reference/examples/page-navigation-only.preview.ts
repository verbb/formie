import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

const preview: FormiePreviewSourceDefinition = {
    minHeight: 400,
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="page-navigation-demo">
            <div class="formie-form-navigation" data-formie-form-navigation>
                <div class="formie-page-tabs">
                    <div class="formie-tab formie-tab-complete" data-formie-tab data-formie-page-id="page-1" data-formie-tab-complete="true">
                        <a href="#" class="formie-tab-link" data-formie-tab-link onclick="event.preventDefault()">Details</a>
                    </div>
                    <div class="formie-tab formie-tab-current" data-formie-tab data-formie-page-id="page-2">
                        <a href="#" class="formie-tab-link" data-formie-tab-link onclick="event.preventDefault()">Requirements</a>
                    </div>
                    <div class="formie-tab formie-tab-error" data-formie-tab data-formie-page-id="page-3" data-formie-tab-error="true">
                        <a href="#" class="formie-tab-link" data-formie-tab-link onclick="event.preventDefault()">Review</a>
                    </div>
                </div>
            </div>

            <div class="formie-pages" data-formie-pages>
                <section class="formie-page" data-formie-page data-formie-page-id="page-2">
                    <div class="formie-page-container" data-formie-page-container>
                        <div class="formie-page-header" data-formie-page-header>
                            <h3 style="margin:0;">Requirements</h3>
                        </div>
                        <div class="formie-page-body" data-formie-page-body>
                            <div class="formie-field" data-formie-field data-formie-field-handle="requirementsSummary" data-formie-field-type="multi-line-text">
                                <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                                    <label class="formie-label formie-field-label" data-formie-label data-formie-field-label>Requirements summary</label>
                                    <div class="formie-field-content" data-formie-field-content>
                                        <div class="formie-instructions" data-formie-instructions>Describe the most important project requirements.</div>
                                        <div class="formie-field-control" data-formie-field-control>
                                            <textarea class="formie-textarea formie-input" rows="4"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="formie-page-footer" data-formie-page-footer>
                        <div class="formie-page-buttons" data-formie-page-buttons data-formie-buttons-position="left-right">
                            <div class="formie-button-container" data-formie-button-container data-formie-buttons-position="left-right">
                                <button type="submit" name="submitAction" value="submit" class="formie-button formie-button-submit formie-button-primary" data-formie-action="submit">Next</button>
                                <button type="submit" name="submitAction" value="back" class="formie-button formie-button-back" data-formie-action="back">Back</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="formie-page formie-page-hidden" data-formie-page data-formie-page-id="page-3" data-formie-page-hidden hidden aria-hidden="true">
                    <div class="formie-page-container" data-formie-page-container>
                        <div class="formie-page-body" data-formie-page-body>
                            <div class="formie-field" data-formie-field data-formie-field-handle="reviewSummary" data-formie-field-type="summary">
                                <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                                    <label class="formie-label formie-field-label" data-formie-label data-formie-field-label>Review</label>
                                    <div class="formie-field-content" data-formie-field-content>
                                        <div class="formie-instructions" data-formie-instructions>This page is hidden until the user advances to it.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </form>
    `,
};

export default preview;
