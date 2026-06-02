const a={minHeight:620,markup:String.raw`
        <style>
            .field-anatomy-preview {
                --anatomy-border: rgba(37, 99, 235, 0.35);
                --anatomy-label-bg: rgb(239, 246, 255);
                --anatomy-label-color: rgb(30, 64, 175);

                padding: 8px;
            }

            .field-anatomy-preview [data-anatomy] {
                position: relative;
                margin: 18px 0 0;
                padding: 20px 12px 12px;
                border: 1px dashed var(--anatomy-border);
                border-radius: 8px;
                background:
                    linear-gradient(rgba(239, 246, 255, 0.18), rgba(239, 246, 255, 0.18)),
                    transparent;
            }

            .field-anatomy-preview [data-anatomy]::before {
                content: attr(data-anatomy);
                position: absolute;
                inset-block-start: -11px;
                inset-inline-start: 10px;
                z-index: 1;
                padding: 2px 6px;
                border: 1px solid var(--anatomy-border);
                border-radius: 5px;
                background: var(--anatomy-label-bg);
                color: var(--anatomy-label-color);
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 11px;
                line-height: 1.3;
                white-space: nowrap;
            }

            .field-anatomy-preview .formie-field-label[data-anatomy],
            .field-anatomy-preview .formie-instructions[data-anatomy],
            .field-anatomy-preview .formie-field-error[data-anatomy],
            .field-anatomy-preview .formie-input[data-anatomy] {
                display: block;
                margin-top: 18px;
            }

            .field-anatomy-preview .formie-input[data-anatomy] {
                width: 100%;
            }

            .field-anatomy-preview .formie-field-errors {
                list-style: none;
            }

        </style>

        <form class="formie-form field-anatomy-preview" data-formie data-formie-form data-formie-handle="field-anatomy-demo">
            <div
                class="formie-field"
                data-formie-field
                data-formie-field-handle="email"
                data-formie-field-type="single-line-text"
                data-formie-input-id="field-anatomy-email"
                data-formie-field-has-error="true"
                data-anatomy="data-formie-field"
            >
                <div
                    class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above"
                    data-formie-field-layout
                    data-formie-label-position="above"
                    data-formie-instructions-position="above"
                    data-anatomy="data-formie-field-layout"
                >
                    <label
                        class="formie-label formie-field-label"
                        for="field-anatomy-email"
                        data-formie-label
                        data-formie-field-label
                        data-anatomy="data-formie-label"
                    >
                        Email address
                    </label>

                    <div
                        class="formie-field-content"
                        data-formie-field-content
                        data-anatomy="data-formie-field-content"
                    >
                        <div
                            class="formie-instructions"
                            id="field-anatomy-email-instructions"
                            data-formie-instructions
                            data-anatomy="data-formie-instructions"
                        >
                            We will use this to send your confirmation email.
                        </div>

                        <div
                            class="formie-field-control"
                            data-formie-field-control
                            data-anatomy="data-formie-field-control"
                        >
                            <input
                                id="field-anatomy-email"
                                type="email"
                                class="formie-input formie-single-line-text-input formie-input-error"
                                name="fields[email]"
                                placeholder="jane@example.com"
                                aria-invalid="true"
                                aria-describedby="field-anatomy-email-instructions field-anatomy-email-errors"
                                data-formie-input
                                data-formie-single-line-text-input
                                data-formie-input-id="field-anatomy-email"
                                data-formie-input-type="email"
                                data-formie-input-error-state
                                data-anatomy="data-formie-input"
                            />
                        </div>

                        <ul
                            class="formie-field-errors"
                            id="field-anatomy-email-errors"
                            data-formie-field-errors
                            data-anatomy="data-formie-field-errors"
                        >
                            <li
                                class="formie-field-error"
                                data-formie-field-error
                                data-anatomy="data-formie-field-error"
                            >
                                Enter a valid email address.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    `};export{a as default};
