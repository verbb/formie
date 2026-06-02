type PreviewOption = {
    label: string;
    value: string;
};

type PreviewDisplayType = 'dropdown' | 'checkboxes' | 'radio';

type ChoiceFieldConfig = {
    fieldType: string;
    handle: string;
    label: string;
    inputIdPrefix: string;
    displayType: PreviewDisplayType;
    options: PreviewOption[];
    selectedValues?: string[];
    multiple?: boolean;
    layout?: 'vertical' | 'horizontal';
};

function escapeAttribute(value: string): string {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('"', '&quot;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;');
}

function escapeHtml(value: string): string {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;');
}

function renderSelectField(config: ChoiceFieldConfig): string {
    const selectedValues = new Set(config.selectedValues || []);
    const multiple = !!config.multiple;
    const name = multiple ? `fields[${config.handle}][]` : `fields[${config.handle}]`;
    const inputId = `${config.inputIdPrefix}-select`;

    return `
        <div class="formie-field" data-formie-field data-formie-field-handle="${escapeAttribute(config.handle)}" data-formie-field-type="${escapeAttribute(config.fieldType)}" data-formie-input-id="${escapeAttribute(inputId)}">
            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                <label class="formie-label formie-field-label" for="${escapeAttribute(inputId)}" data-formie-label data-formie-field-label>${escapeHtml(config.label)}</label>
                <div class="formie-field-content" data-formie-field-content>
                    <div class="formie-field-control" data-formie-field-control>
                        <select
                            id="${escapeAttribute(inputId)}"
                            class="formie-select formie-input"
                            name="${escapeAttribute(name)}"
                            ${multiple ? 'multiple size="4"' : ''}
                            data-formie-input
                            data-formie-input-id="${escapeAttribute(inputId)}"
                            data-formie-input-type="select"
                        >
                            ${config.options.map((option) => {
                                return `
                                    <option value="${escapeAttribute(option.value)}" ${selectedValues.has(option.value) ? 'selected' : ''}>${escapeHtml(option.label)}</option>
                                `;
                            }).join('')}
                        </select>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderChoiceListField(config: ChoiceFieldConfig): string {
    const selectedValues = new Set(config.selectedValues || []);
    const layout = config.layout || 'vertical';
    const isCheckboxes = config.displayType === 'checkboxes';
    const layoutAttr = `data-formie-layout="${escapeAttribute(layout)}"`;
    const fieldLayoutAttr = isCheckboxes ? 'data-formie-checkboxes-field-layout' : 'data-formie-radio-field-layout';
    const fieldLayoutClass = isCheckboxes ? 'formie-checkboxes-field-layout' : 'formie-radio-field-layout';
    const fieldLabelAttr = isCheckboxes ? 'data-formie-checkboxes-field-label' : 'data-formie-radio-field-label';
    const fieldLabelClass = isCheckboxes ? 'formie-checkboxes-field-label' : 'formie-radio-field-label';
    const optionsAttr = isCheckboxes ? 'data-formie-checkboxes-options' : 'data-formie-radio-options';
    const optionsClass = isCheckboxes ? 'formie-checkboxes-options' : 'formie-radio-options';
    const optionAttr = isCheckboxes ? 'data-formie-checkbox-option' : 'data-formie-radio-option';
    const optionClass = isCheckboxes ? 'formie-checkbox-option' : 'formie-radio-option';
    const optionLabelAttr = isCheckboxes ? 'data-formie-checkbox-option-label' : 'data-formie-radio-option-label';
    const optionLabelClass = isCheckboxes ? 'formie-checkbox-option-label' : 'formie-radio-option-label';
    const inputAttr = isCheckboxes ? 'data-formie-checkbox-input' : 'data-formie-radio-input';
    const inputClass = isCheckboxes ? 'formie-checkbox-input' : 'formie-radio-input';
    const inputType = isCheckboxes ? 'checkbox' : 'radio';
    const name = isCheckboxes ? `fields[${config.handle}][]` : `fields[${config.handle}]`;

    return `
        <div class="formie-field" data-formie-field data-formie-field-handle="${escapeAttribute(config.handle)}" data-formie-field-type="${escapeAttribute(config.fieldType)}">
            <fieldset class="formie-field-layout ${fieldLayoutClass} formie-layout-${layout} formie-field-layout-label-above" data-formie-field-layout ${fieldLayoutAttr} ${layoutAttr} data-formie-label-position="above">
                <legend class="formie-label formie-field-label ${fieldLabelClass}" data-formie-label data-formie-field-label ${fieldLabelAttr}>${escapeHtml(config.label)}</legend>
                <div class="formie-field-content" data-formie-field-content>
                    <div class="formie-field-control" data-formie-field-control>
                        ${isCheckboxes ? `<input type="hidden" name="fields[${escapeAttribute(config.handle)}]" value="">` : ''}
                        <div class="formie-field-options ${optionsClass} formie-layout-${layout}" data-formie-field-options ${optionsAttr} ${layoutAttr}>
                            ${config.options.map((option, index) => {
                                const inputId = `${config.inputIdPrefix}-${index + 1}`;

                                return `
                                    <div class="formie-field-option ${optionClass}" data-formie-field-option ${optionAttr}>
                                        <input
                                            id="${escapeAttribute(inputId)}"
                                            class="formie-input ${inputClass}"
                                            type="${inputType}"
                                            name="${escapeAttribute(name)}"
                                            value="${escapeAttribute(option.value)}"
                                            ${selectedValues.has(option.value) ? 'checked' : ''}
                                            data-formie-input
                                            ${inputAttr}
                                            data-formie-input-id="${escapeAttribute(inputId)}"
                                            data-formie-input-type="${inputType}"
                                        >
                                        <label class="formie-field-option-label ${optionLabelClass}" for="${escapeAttribute(inputId)}" data-formie-field-option-label ${optionLabelAttr}>${escapeHtml(option.label)}</label>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                </div>
            </fieldset>
        </div>
    `;
}

export function renderChoiceField(config: ChoiceFieldConfig): string {
    if (config.displayType === 'dropdown') {
        return renderSelectField(config);
    }

    return renderChoiceListField(config);
}

export function renderPreviewForm(handle: string, fieldsMarkup: string[]): string {
    return `
        <form class="formie-form" data-formie data-formie-form data-formie-handle="${escapeAttribute(handle)}">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    ${fieldsMarkup.map((fieldMarkup) => {
                                        return `
                                            <div class="formie-row" data-formie-row data-formie-field-count="1">
                                                ${fieldMarkup}
                                            </div>
                                        `;
                                    }).join('')}
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </form>
    `;
}
