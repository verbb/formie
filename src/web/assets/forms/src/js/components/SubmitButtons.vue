<template>
    <div class="fui-field-block fui-submit-block" :class="{ 'has-errors': false }" @click.prevent="openModal">
        <div class="fui-edit-overlay" @click.prevent="editField"></div>

        <div class="flex" :style="{ 'justify-content': cssAlignment }">
            <div v-if="!isFirstButton && settings.showBackButton">
                <a href="#" class="btn submit">{{ settings.backButtonLabel }}</a>
            </div>

            <a href="#" class="btn submit">{{ settings.submitButtonLabel }}</a>
        </div>

        <field-edit-modal
            v-if="showModal"
            v-model:showModal="showModal"
            v-model:field="field"
            :field-ref="this"
            :fields-schema="fieldsSchema"
            :tabs-schema="tabsSchema"
            :can-delete="false"
            :show-field-type="false"
            @closed="onModalClosed"
        />
    </div>
</template>

<script>
import { mapState } from 'vuex';

import FieldEditModal from '@components/FieldEditModal.vue';

export default {
    name: 'SubmitButtons',

    components: {
        FieldEditModal,
    },

    props: {
        pageId: {
            type: [String, Number],
            default: '',
        },

        pageIndex: {
            type: Number,
            default: 0,
        },
    },

    data() {
        return {
            id: Math.random(),
            showModal: false,
            originalField: null,
            submitButton: true,
        };
    },

    computed: {
        ...mapState({
            pages: (state) => { return state.form.pages; },
            form: (state) => { return state.form; },
        }),

        field: {
            get() {
                // Create a mock field to make editing easier
                return this.pages[this.pageIndex];
            },

            set(newValue) {
                const payload = {
                    pageIndex: this.pageIndex,
                    data: newValue.settings,
                };

                this.$store.dispatch('form/addPageSettings', payload);
            },
        },

        settings() {
            return this.field.settings;
        },

        isFirstButton() {
            return this.pageIndex === 0;
        },

        tabsSchema() {
            return [
                {
                    label: Craft.t('formie', 'General'),
                    fields: [
                        'submitButtonLabel',
                        'showBackButton',
                        'backButtonLabel',
                    ],
                },
                {
                    label: Craft.t('formie', 'Appearance'),
                    fields: [
                        'buttonsPosition',
                        'cssClasses',
                    ],
                },
                {
                    label: Craft.t('formie', 'Conditions'),
                    fields: [
                        'enableConditions',
                        'conditions',
                    ],
                },
                {
                    label: Craft.t('formie', 'Advanced'),
                    fields: [
                        'enableJsEvents',
                        'jsEvents',
                    ],
                },
            ];
        },

        fieldsSchema() {
            let fields = [];

            if (!this.isFirstButton) {
                fields = [
                    {
                        $formkit: 'lightswitch',
                        label: Craft.t('formie', 'Show Back Button'),
                        help: Craft.t('formie', 'Whether to show the back button, to go back to a previous page.'),
                        name: 'showBackButton',
                        id: 'showBackButton',
                    },
                    {
                        $formkit: 'text',
                        inputClass: 'text fullwidth',
                        autocomplete: 'off',
                        label: Craft.t('formie', 'Back Button Label'),
                        help: Craft.t('formie', 'The label for the back submit button.'),
                        name: 'backButtonLabel',
                        id: 'backButtonLabel',
                        if: '$get(showBackButton).value',
                        validation: 'required',
                        required: true,
                    },
                ];
            }

            const fieldsSchema = [
                {
                    $cmp: 'TabPanel',
                    attrs: {
                        'data-tab-panel': 'General',
                    },
                    children: [
                        {
                            $formkit: 'text',
                            inputClass: 'text fullwidth',
                            autocomplete: 'off',
                            label: Craft.t('formie', 'Button Label'),
                            help: Craft.t('formie', 'The label for the submit button.'),
                            name: 'submitButtonLabel',
                            id: 'submitButtonLabel',
                            validation: 'required',
                            required: true,
                        },
                        ...fields,
                    ],
                },
                {
                    $cmp: 'TabPanel',
                    attrs: {
                        'data-tab-panel': 'General',
                    },
                    children: [
                        {
                            $formkit: 'select',
                            label: Craft.t('formie', 'Button Positions'),
                            help: Craft.t('formie', 'How the buttons should be positioned.'),
                            name: 'buttonsPosition',
                            id: 'buttonsPosition',
                            options: this.buttonsPosition,
                        },
                        {
                            $formkit: 'text',
                            inputClass: 'text fullwidth',
                            autocomplete: 'off',
                            label: Craft.t('formie', 'CSS Classes'),
                            help: Craft.t('formie', 'Add classes that will be output on submit button container.'),
                            name: 'cssClasses',
                            id: 'cssClasses',
                        },
                        {
                            $formkit: 'table',
                            label: Craft.t('formie', 'Container Attributes'),
                            help: Craft.t('formie', 'Add attributes to be outputted on this submit button’s container.'),
                            validation: 'min:0',
                            generateValue: false,
                            name: 'containerAttributes',
                            id: 'containerAttributes',
                            newRowDefaults: {
                                label: '',
                                value: '',
                            },
                            columns: [
                                {
                                    type: 'label',
                                    label: 'Name',
                                    class: 'singleline-cell textual',
                                },
                                {
                                    type: 'value',
                                    label: 'Value',
                                    class: 'singleline-cell textual',
                                },
                            ],
                        },
                        {
                            $formkit: 'table',
                            label: Craft.t('formie', 'Input Attributes'),
                            help: Craft.t('formie', 'Add attributes to be outputted on this submit button’s input.'),
                            validation: 'min:0',
                            generateValue: false,
                            name: 'inputAttributes',
                            id: 'inputAttributes',
                            newRowDefaults: {
                                label: '',
                                value: '',
                            },
                            columns: [
                                {
                                    type: 'label',
                                    label: 'Name',
                                    class: 'singleline-cell textual',
                                },
                                {
                                    type: 'value',
                                    label: 'Value',
                                    class: 'singleline-cell textual',
                                },
                            ],
                        },
                    ],
                },
                {
                    $cmp: 'TabPanel',
                    attrs: {
                        'data-tab-panel': 'General',
                    },
                    children: [
                        {
                            $formkit: 'lightswitch',
                            labelPosition: 'before',
                            label: Craft.t('formie', 'Enable Conditions'),
                            help: Craft.t('formie', 'Whether to enable conditional logic to control how the next button is shown.'),
                            name: 'enableNextButtonConditions',
                            id: 'enableNextButtonConditions',
                        },
                        {
                            $formkit: 'fieldConditions',
                            name: 'nextButtonConditions',
                            id: 'nextButtonConditions',
                            descriptionText: 'the next button if',
                            if: '$get(enableNextButtonConditions).value',
                        },
                    ],
                },
                {
                    $cmp: 'TabPanel',
                    attrs: {
                        'data-tab-panel': 'General',
                    },
                    children: [
                        {
                            $formkit: 'lightswitch',
                            labelPosition: 'before',
                            label: Craft.t('formie', 'Enable JavaScript Events'),
                            help: Craft.t('formie', 'Whether to enable management of JavaScript events when this button is pressed.'),
                            name: 'enableJsEvents',
                            id: 'enableJsEvents',
                        },
                        {
                            $formkit: 'table',
                            label: Craft.t('formie', 'Google Tag Manager Event Data'),
                            help: Craft.t('formie', 'Add event data to be sent to Google Tag Manager.'),
                            validation: 'min:0',
                            generateValue: false,
                            name: 'jsGtmEventOptions',
                            id: 'jsGtmEventOptions',
                            if: '$get(enableJsEvents).value',
                            newRowDefaults: {
                                label: '',
                                value: '',
                            },
                            initialValue: [{
                                label: 'event',
                                value: 'formPageSubmission',
                            }, {
                                label: 'formId',
                                value: this.form.handle,
                            }, {
                                label: 'pageId',
                                value: this.pageId,
                            }, {
                                label: 'pageIndex',
                                value: this.pageIndex,
                            }],
                            columns: [
                                {
                                    type: 'label',
                                    label: 'Option',
                                    class: 'singleline-cell textual',
                                },
                                {
                                    type: 'value',
                                    label: 'Value',
                                    class: 'singleline-cell textual',
                                },
                            ],
                        },
                    ],
                },
            ];

            return [{
                $cmp: 'TabPanels',
                attrs: {
                    class: 'fui-modal-content',
                },
                children: fieldsSchema,
            }];
        },

        buttonsPosition() {
            const positions = [
                { label: Craft.t('formie', 'Left'), value: 'left' },
                { label: Craft.t('formie', 'Right'), value: 'right' },
                { label: Craft.t('formie', 'Center'), value: 'center' },
            ];

            if (this.settings.showBackButton) {
                positions.push({ label: Craft.t('formie', 'Left & Right'), value: 'left-right' });
            }

            return positions;
        },

        cssAlignment() {
            if (this.settings.buttonsPosition === 'right') {
                return 'flex-end';
            }

            if (this.settings.buttonsPosition === 'center') {
                return 'center';
            }

            if (this.settings.buttonsPosition === 'left-right') {
                return 'space-between';
            }

            return 'normal';
        },
    },

    created() {
        // Store this so we can cancel changes.
        this.originalField = this.clone(this.settings);
    },

    methods: {
        openModal() {
            this.showModal = true;
        },

        onModalClosed() {
            this.showModal = false;
        },

        markAsSaved() {
            // Required for save callback in FieldEditModal
        },
    },

};

</script>
