<template>
    <div class="fui-field-block fui-submit-block" :class="{ 'has-errors': false }" @click.prevent="openModal">
        <div class="fui-edit-overlay" @click.prevent="editField"></div>

        <div v-if="settings.showSaveButton" class="flex" :style="cssAlignment">
            <div class="flex">
                <a v-if="!isFirstButton && settings.showBackButton" href="#" class="btn">{{ settings.backButtonLabel }}</a>

                <a href="#" class="btn submit">{{ settings.submitButtonLabel }}</a>
            </div>

            <div class="flex">
                <a v-if="settings.saveButtonStyle === 'link'" href="#" class="fui-btn-link">{{ settings.saveButtonLabel }}</a>
                <a v-else href="#" class="btn submit">{{ settings.saveButtonLabel }}</a>
            </div>
        </div>

        <div v-else class="flex" :style="cssAlignment">
            <a v-if="!isFirstButton && settings.showBackButton" href="#" class="btn">{{ settings.backButtonLabel }}</a>

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
                        key: 'showBackButton',
                    },
                    {
                        $formkit: 'text',
                        inputClass: 'text fullwidth',
                        autocomplete: 'off',
                        label: Craft.t('formie', 'Back Button Label'),
                        help: Craft.t('formie', 'The label for the back submit button.'),
                        name: 'backButtonLabel',
                        id: 'backButtonLabel',
                        key: 'backButtonLabel',
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
                            key: 'submitButtonLabel',
                            validation: 'required',
                            required: true,
                        },
                        {
                            $formkit: 'lightswitch',
                            label: Craft.t('formie', 'Show Save Button'),
                            help: Craft.t('formie', 'Whether to show the save button, allowing users to save progress on a submission to return later.'),
                            name: 'showSaveButton',
                            id: 'showSaveButton',
                            key: 'showSaveButton',
                        },
                        {
                            $formkit: 'text',
                            inputClass: 'text fullwidth',
                            autocomplete: 'off',
                            label: Craft.t('formie', 'Save Button Label'),
                            help: Craft.t('formie', 'The label for the save submit button.'),
                            name: 'saveButtonLabel',
                            id: 'saveButtonLabel',
                            key: 'saveButtonLabel',
                            if: '$get(showSaveButton).value',
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
                            label: Craft.t('formie', 'Submit Buttons Position'),
                            help: Craft.t('formie', 'How the submit buttons should be positioned.'),
                            name: 'buttonsPosition',
                            id: 'buttonsPosition',
                            key: 'buttonsPosition',
                            options: this.buttonsPosition,
                        },
                        {
                            $formkit: 'select',
                            label: Craft.t('formie', 'Save Button Style'),
                            help: Craft.t('formie', 'Select the style for the save button.'),
                            name: 'saveButtonStyle',
                            id: 'saveButtonStyle',
                            key: 'saveButtonStyle',
                            if: '$get(showSaveButton).value',
                            options: [
                                { label: Craft.t('formie', 'Link'), value: 'link' },
                                { label: Craft.t('formie', 'Button'), value: 'button' },
                            ],
                        },
                        {
                            $formkit: 'text',
                            inputClass: 'text fullwidth',
                            autocomplete: 'off',
                            label: Craft.t('formie', 'CSS Classes'),
                            help: Craft.t('formie', 'Add classes that will be output on submit button container.'),
                            name: 'cssClasses',
                            id: 'cssClasses',
                            key: 'cssClasses',
                        },
                        {
                            $formkit: 'table',
                            label: Craft.t('formie', 'Container Attributes'),
                            help: Craft.t('formie', 'Add attributes to be outputted on this submit button’s container.'),
                            validation: 'min:0',
                            generateValue: false,
                            name: 'containerAttributes',
                            id: 'containerAttributes',
                            key: 'containerAttributes',
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
                            key: 'inputAttributes',
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
                            key: 'enableNextButtonConditions',
                        },
                        {
                            $formkit: 'fieldConditions',
                            name: 'nextButtonConditions',
                            id: 'nextButtonConditions',
                            key: 'nextButtonConditions',
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
                            key: 'enableJsEvents',
                        },
                        {
                            $formkit: 'table',
                            label: Craft.t('formie', 'Google Tag Manager Event Data'),
                            help: Craft.t('formie', 'Add event data to be sent to Google Tag Manager.'),
                            validation: 'min:0',
                            generateValue: false,
                            name: 'jsGtmEventOptions',
                            id: 'jsGtmEventOptions',
                            key: 'jsGtmEventOptions',
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
            ];

            if (this.settings.showSaveButton) {
                positions.push({ label: Craft.t('formie', 'Right (Save on Left)'), value: 'right-save-left' });
                positions.push({ label: Craft.t('formie', 'Center (Save on Left)'), value: 'center-save-left' });
                positions.push({ label: Craft.t('formie', 'Center (Save on Right)'), value: 'center-save-right' });
                positions.push({ label: Craft.t('formie', 'Save on Right'), value: 'save-right' });
                positions.push({ label: Craft.t('formie', 'Save on Left'), value: 'save-left' });
            } else {
                positions.push({ label: Craft.t('formie', 'Center'), value: 'center' });

                if (this.settings.showBackButton) {
                    positions.push({ label: Craft.t('formie', 'Left & Right'), value: 'left-right' });
                }
            }

            return positions;
        },

        cssAlignment() {
            if (this.settings.buttonsPosition === 'right') {
                return { 'justify-content': 'flex-end' };
            }

            if (this.settings.buttonsPosition === 'center') {
                return { 'justify-content': 'center' };
            }

            if (this.settings.buttonsPosition === 'left-right') {
                return { 'justify-content': 'space-between' };
            }

            if (this.settings.buttonsPosition === 'right-save-left') {
                return { 'justify-content': 'flex-start', 'flex-direction': 'row-reverse' };
            }

            if (this.settings.buttonsPosition === 'center-save-left') {
                return { 'justify-content': 'center', 'flex-direction': 'row-reverse' };
            }

            if (this.settings.buttonsPosition === 'center-save-right') {
                return { 'justify-content': 'center' };
            }

            if (this.settings.buttonsPosition === 'save-right') {
                return { 'justify-content': 'space-between' };
            }

            if (this.settings.buttonsPosition === 'save-left') {
                return { 'justify-content': 'space-between', 'flex-direction': 'row-reverse' };
            }

            return { 'justify-content': 'normal' };
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

<style lang="scss">

.fui-btn-link {
    color: #e12d38;
    padding: 0 0.5rem;
}

</style>
