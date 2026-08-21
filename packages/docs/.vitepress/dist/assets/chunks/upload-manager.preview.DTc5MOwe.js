const a={minHeight:360,markup:String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="upload-manager-demo">
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
                                            data-formie-field-handle="attachments"
                                            data-formie-field-type="file-upload"
                                            data-formie-input-id="demo-attachments"
                                        >
                                            <div
                                                class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above"
                                                data-formie-field-layout
                                                data-formie-label-position="above"
                                                data-formie-instructions-position="above"
                                            >
                                                <label
                                                    class="formie-label formie-field-label"
                                                    for="demo-attachments-status"
                                                    data-formie-label
                                                    data-formie-field-label
                                                >
                                                    Attachments
                                                </label>

                                                <div class="formie-field-content" data-formie-field-content>
                                                    <div class="formie-field-control" data-formie-field-control>
                                                        <input
                                                            type="hidden"
                                                            name="fields[attachments]"
                                                            value=""
                                                            data-formie-file-upload-anchor="true"
                                                        />

                                                        <input
                                                            type="hidden"
                                                            name="fields[attachments][]"
                                                            value="101"
                                                            data-formie-file-upload-asset-id="true"
                                                        />

                                                        <div class="formie-upload-manager" data-formie-upload-manager-root>
                                                            <div
                                                                class="formie-upload-manager-dropzone"
                                                                data-formie-upload-manager
                                                                data-formie-upload-key="demo-attachments"
                                                                data-formie-input-id="demo-attachments"
                                                                data-formie-input-type="upload-manager"
                                                                data-formie-file-limit="3"
                                                                data-formie-size-max-limit="10"
                                                                tabindex="0"
                                                            >
                                                                <p class="formie-upload-manager-prompt">Drop files here or browse to upload.</p>
                                                                <button type="button" class="formie-button formie-upload-manager-browse-button" data-formie-upload-manager-browse>
                                                                    Browse files
                                                                </button>
                                                            </div>

                                                            <input
                                                                type="file"
                                                                class="formie-sr-only"
                                                                multiple
                                                                data-formie-upload-manager-input
                                                                data-formie-validation-skip
                                                                tabindex="-1"
                                                            />

                                                            <input
                                                                id="demo-attachments-status"
                                                                type="text"
                                                                class="formie-sr-only"
                                                                value="uploaded"
                                                                readonly
                                                                tabindex="-1"
                                                                aria-hidden="true"
                                                                data-formie-input
                                                                data-formie-upload-manager-status
                                                            />

                                                            <ul class="formie-upload-manager-list" data-formie-upload-manager-list>
                                                                <li class="formie-upload-manager-item" data-formie-upload-manager-item="true">
                                                                    <span class="formie-upload-manager-filename" data-formie-upload-manager-filename="true">project-brief.pdf</span>
                                                                    <div class="formie-upload-manager-progress" data-formie-upload-manager-progress="true" hidden>
                                                                        <div class="formie-upload-manager-progress-track" data-formie-upload-manager-progress-track="true">
                                                                            <div class="formie-upload-manager-progress-bar" data-formie-upload-manager-progress-bar="true"></div>
                                                                        </div>
                                                                        <span class="formie-upload-manager-progress-label" data-formie-upload-manager-progress-label="true"></span>
                                                                    </div>
                                                                    <div class="formie-upload-manager-sort-controls" data-formie-upload-manager-sort-controls="true">
                                                                        <button type="button" class="formie-upload-manager-sort-button" data-formie-upload-manager-sort="up" aria-label="Move up" disabled></button>
                                                                        <button type="button" class="formie-upload-manager-sort-button" data-formie-upload-manager-sort="down" aria-label="Move down" disabled></button>
                                                                    </div>
                                                                    <div class="formie-upload-manager-actions" data-formie-upload-manager-actions="true">
                                                                        <button type="button" class="formie-button formie-button-icon formie-upload-manager-remove-button" data-formie-upload-manager-remove="true" aria-label="Remove file" title="Remove file"></button>
                                                                    </div>
                                                                </li>
                                                            </ul>
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
    `};export{a as default};
