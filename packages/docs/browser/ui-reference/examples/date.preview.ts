import type { FormiePreviewSourceDefinition } from '../../../.vitepress/theme/components/previewSources';

type DateModuleConfig = {
    dateFormat: string;
    getIsDate?: boolean;
    getIsDateTime?: boolean;
    getIsTime?: boolean;
    timeFormat: string;
};

function renderDatePickerField(config: {
    handle: string;
    inputId: string;
    label: string;
    placeholder: string;
    value: string;
}): string {
    return `
        <div class="formie-field" data-formie-field data-formie-field-handle="${config.handle}" data-formie-field-type="date" data-formie-input-id="${config.inputId}">
            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                <label class="formie-label formie-field-label" for="${config.inputId}" data-formie-label data-formie-field-label>${config.label}</label>
                <div class="formie-field-content" data-formie-field-content>
                    <div class="formie-field-control" data-formie-field-control>
                        <input
                            id="${config.inputId}"
                            type="text"
                            class="formie-input"
                            name="fields[${config.handle}][datetime]"
                            value="${config.value}"
                            placeholder="${config.placeholder}"
                            data-formie-input
                            data-formie-date-datepicker-input
                            data-formie-input-id="${config.inputId}"
                            data-formie-input-type="date"
                        >
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderSubfieldInput(config: {
    handle: string;
    inputHandle: string;
    inputId: string;
    inputType: string;
    placeholder: string;
    value: string;
}): string {
    return `
        <div class="formie-field formie-field-nested" data-formie-field data-formie-field-handle="${config.inputHandle}">
            <input
                id="${config.inputId}"
                type="${config.inputType}"
                class="formie-input"
                name="fields[${config.handle}][${config.inputHandle}]"
                value="${config.value}"
                placeholder="${config.placeholder}"
                data-formie-input
                data-formie-input-id="${config.inputId}"
                data-formie-input-type="${config.inputType}"
            >
        </div>
    `;
}

function renderSubfieldSelect(config: {
    handle: string;
    inputHandle: string;
    inputId: string;
    options: Array<{ label: string; value: string; selected?: boolean }>;
}): string {
    return `
        <div class="formie-field formie-field-nested" data-formie-field data-formie-field-handle="${config.inputHandle}">
            <select
                id="${config.inputId}"
                class="formie-select formie-input"
                name="fields[${config.handle}][${config.inputHandle}]"
                data-formie-input
                data-formie-input-id="${config.inputId}"
                data-formie-input-type="select"
            >
                ${config.options.map((option) => {
                    return `<option value="${option.value}" ${option.selected ? 'selected' : ''}>${option.label}</option>`;
                }).join('')}
            </select>
        </div>
    `;
}

function renderFieldsetField(config: {
    handle: string;
    label: string;
    rows: string[];
}): string {
    return `
        <div class="formie-field" data-formie-field data-formie-field-handle="${config.handle}" data-formie-field-type="date">
            <fieldset class="formie-field-layout formie-date-field-layout formie-subfield-fieldset" data-formie-field-layout data-formie-date-field-layout data-formie-subfield-fieldset>
                <legend class="formie-label formie-field-label formie-date-field-label" data-formie-label data-formie-field-label data-formie-date-field-label>${config.label}</legend>
                <div class="formie-field-content" data-formie-field-content>
                    <div class="formie-field-control" data-formie-field-control>
                        <div class="formie-subfield-rows" data-formie-subfield-rows>
                            ${config.rows.join('\n')}
                        </div>
                    </div>
                </div>
            </fieldset>
        </div>
    `;
}

function renderSubfieldRow(fields: string[]): string {
    return `
        <div class="formie-subfield-row" data-formie-subfield-row>
            ${fields.join('\n')}
        </div>
    `;
}

function renderPreviewForm(fieldsMarkup: string[]): string {
    return `
        <form class="formie-form" data-formie data-formie-form data-formie-handle="date-demo">
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

function datePickerModule(handle: string, options: DateModuleConfig) {
    return {
        id: 'date-picker',
        type: 'field' as const,
        targets: [
            {
                targetType: 'field' as const,
                targetId: handle,
            },
        ],
        config: {
            options: {
                locale: 'en',
                ...options,
            },
        },
    };
}

const preview: FormiePreviewSourceDefinition = {
    minHeight: 1500,
    modules: [
        datePickerModule('advancedDate', {
            dateFormat: 'Y-m-d',
            getIsDate: true,
            timeFormat: 'H:i',
        }),
        datePickerModule('advancedTime', {
            dateFormat: 'Y-m-d',
            getIsTime: true,
            timeFormat: 'H:i',
        }),
        datePickerModule('advancedDateTime', {
            dateFormat: 'Y-m-d',
            getIsDateTime: true,
            timeFormat: 'H:i',
        }),
    ],
    markup: renderPreviewForm([
        renderFieldsetField({
            handle: 'simpleDate',
            label: 'Calendar (native date input)',
            rows: [
                renderSubfieldRow([
                    renderSubfieldInput({
                        handle: 'simpleDate',
                        inputHandle: 'date',
                        inputId: 'simple-date-date',
                        inputType: 'date',
                        placeholder: 'YYYY-MM-DD',
                        value: '2026-04-15',
                    }),
                ]),
            ],
        }),
        renderFieldsetField({
            handle: 'simpleDateTime',
            label: 'Calendar (native date + time inputs)',
            rows: [
                renderSubfieldRow([
                    renderSubfieldInput({
                        handle: 'simpleDateTime',
                        inputHandle: 'date',
                        inputId: 'simple-date-time-date',
                        inputType: 'date',
                        placeholder: 'YYYY-MM-DD',
                        value: '2026-04-15',
                    }),
                    renderSubfieldInput({
                        handle: 'simpleDateTime',
                        inputHandle: 'time',
                        inputId: 'simple-date-time-time',
                        inputType: 'time',
                        placeholder: 'HH:MM',
                        value: '14:30',
                    }),
                ]),
            ],
        }),
        renderDatePickerField({
            handle: 'advancedDate',
            inputId: 'advanced-date',
            label: 'Flatpickr (`datePicker`, date only)',
            placeholder: 'Select a date',
            value: '2026-04-15 00:00:00',
        }),
        renderDatePickerField({
            handle: 'advancedTime',
            inputId: 'advanced-time',
            label: 'Flatpickr (`datePicker`, time only)',
            placeholder: 'Select a time',
            value: '2026-04-15 14:30:00',
        }),
        renderDatePickerField({
            handle: 'advancedDateTime',
            inputId: 'advanced-date-time',
            label: 'Flatpickr (`datePicker`, date + time)',
            placeholder: 'Select a date and time',
            value: '2026-04-15 14:30:00',
        }),
        renderFieldsetField({
            handle: 'dropdownDateTime',
            label: 'Dropdowns',
            rows: [
                renderSubfieldRow([
                    renderSubfieldSelect({
                        handle: 'dropdownDateTime',
                        inputHandle: 'year',
                        inputId: 'dropdown-date-time-year',
                        options: [
                            { label: '2025', value: '2025' },
                            { label: '2026', value: '2026', selected: true },
                            { label: '2027', value: '2027' },
                        ],
                    }),
                    renderSubfieldSelect({
                        handle: 'dropdownDateTime',
                        inputHandle: 'month',
                        inputId: 'dropdown-date-time-month',
                        options: [
                            { label: 'April', value: '4', selected: true },
                            { label: 'May', value: '5' },
                            { label: 'June', value: '6' },
                        ],
                    }),
                    renderSubfieldSelect({
                        handle: 'dropdownDateTime',
                        inputHandle: 'day',
                        inputId: 'dropdown-date-time-day',
                        options: [
                            { label: '14', value: '14' },
                            { label: '15', value: '15', selected: true },
                            { label: '16', value: '16' },
                        ],
                    }),
                ]),
                renderSubfieldRow([
                    renderSubfieldSelect({
                        handle: 'dropdownDateTime',
                        inputHandle: 'hour',
                        inputId: 'dropdown-date-time-hour',
                        options: [
                            { label: '13', value: '13' },
                            { label: '14', value: '14', selected: true },
                            { label: '15', value: '15' },
                        ],
                    }),
                    renderSubfieldSelect({
                        handle: 'dropdownDateTime',
                        inputHandle: 'minute',
                        inputId: 'dropdown-date-time-minute',
                        options: [
                            { label: '00', value: '00' },
                            { label: '30', value: '30', selected: true },
                            { label: '45', value: '45' },
                        ],
                    }),
                ]),
            ],
        }),
        renderFieldsetField({
            handle: 'inputDateTime',
            label: 'Inputs',
            rows: [
                renderSubfieldRow([
                    renderSubfieldInput({
                        handle: 'inputDateTime',
                        inputHandle: 'year',
                        inputId: 'input-date-time-year',
                        inputType: 'text',
                        placeholder: 'Year',
                        value: '2026',
                    }),
                    renderSubfieldInput({
                        handle: 'inputDateTime',
                        inputHandle: 'month',
                        inputId: 'input-date-time-month',
                        inputType: 'text',
                        placeholder: 'Month',
                        value: '04',
                    }),
                    renderSubfieldInput({
                        handle: 'inputDateTime',
                        inputHandle: 'day',
                        inputId: 'input-date-time-day',
                        inputType: 'text',
                        placeholder: 'Day',
                        value: '15',
                    }),
                ]),
                renderSubfieldRow([
                    renderSubfieldInput({
                        handle: 'inputDateTime',
                        inputHandle: 'hour',
                        inputId: 'input-date-time-hour',
                        inputType: 'text',
                        placeholder: 'Hour',
                        value: '14',
                    }),
                    renderSubfieldInput({
                        handle: 'inputDateTime',
                        inputHandle: 'minute',
                        inputId: 'input-date-time-minute',
                        inputType: 'text',
                        placeholder: 'Minute',
                        value: '30',
                    }),
                ]),
            ],
        }),
    ]),
};

export default preview;
