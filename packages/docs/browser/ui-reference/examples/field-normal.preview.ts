import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

const preview: FormiePreviewSourceDefinition = {
    minHeight: 260,
    markup: String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="field-normal-demo">
            <div
                class="formie-field"
                data-formie-field
                data-formie-field-handle="email"
                data-formie-field-type="single-line-text"
                data-formie-input-id="field-normal-email"
                data-formie-field-has-error="true"
            >
                <div
                    class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above"
                    data-formie-field-layout
                    data-formie-label-position="above"
                    data-formie-instructions-position="above"
                >
                    <label
                        class="formie-label formie-field-label"
                        for="field-normal-email"
                        data-formie-label
                        data-formie-field-label
                    >
                        Email address
                    </label>

                    <div class="formie-field-content" data-formie-field-content>
                        <div
                            class="formie-instructions"
                            id="field-normal-email-instructions"
                            data-formie-instructions
                        >
                            We will use this to send your confirmation email.
                        </div>

                        <div class="formie-field-control" data-formie-field-control>
                            <input
                                id="field-normal-email"
                                type="email"
                                class="formie-input formie-single-line-text-input formie-input-error"
                                name="fields[email]"
                                placeholder="jane@example.com"
                                aria-invalid="true"
                                aria-describedby="field-normal-email-instructions field-normal-email-errors"
                                data-formie-input
                                data-formie-single-line-text-input
                                data-formie-input-id="field-normal-email"
                                data-formie-input-type="email"
                                data-formie-input-error-state
                            />
                        </div>

                        <ul
                            class="formie-field-errors"
                            id="field-normal-email-errors"
                            data-formie-field-errors
                        >
                            <li class="formie-field-error" data-formie-field-error>
                                Enter a valid email address.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    `,
};

export default preview;
