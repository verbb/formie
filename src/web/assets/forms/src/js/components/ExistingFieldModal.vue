<template>
    <div class="btn dashed add icon" @click="openModal">{{ t('formie', 'Add existing fields') }}</div>

    <modal ref="modal" v-model="showModal" :modal-class="['fui-edit-field-modal', 'fui-existing-item-modal']" @click-outside="closeModal">
        <template #header>
            <h3 class="fui-modal-title">{{ t('formie', 'Add Existing Field') }}</h3>

            <button class="fui-dialog-close" @click.prevent="closeModal"></button>
        </template>

        <template #body>
            <div v-if="error" class="fui-error-pane error">
                <div class="fui-error-content">
                    <span data-icon="alert"></span>

                    <span class="error" v-html="errorMessage"></span>
                </div>
            </div>

            <div v-else-if="loading" class="fui-loading fui-loading-lg" style="height: 100%;"></div>

            <div v-else-if="mounted && existingFields.length" class="fui-modal-content-wrap">
                <div class="fui-modal-sidebar sidebar">
                    <nav v-if="filteredExistingFields.length">
                        <ul>
                            <li v-if="existingFields.length">
                                <a :class="{ 'sel': selectedKey === existingFields[0].key }" @click.prevent="selectTab(existingFields[0].key)">
                                    <span class="label">{{ existingFields[0].label }}</span>
                                </a>
                            </li>

                            <li class="heading"><span>{{ t('formie', 'Forms') }}</span></li>

                            <li v-for="(form, index) in existingFields" :key="index">
                                <a v-if="index > 0" :class="{ 'sel': selectedKey === form.key }" @click.prevent="selectTab(form.key)">
                                    <span class="label">{{ form.label }}</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>

                <div class="fui-modal-content">
                    <div class="toolbar flex flex-nowrap">
                        <div class="flex-grow texticon search icon clearable">
                            <input v-model="search" class="text fullwidth" type="text" autocomplete="off" placeholder="Search">
                            <div class="clear hidden" title="Clear"></div>
                        </div>
                    </div>

                    <div v-if="filteredExistingFields.length">
                        <div v-for="(form, formIndex) in filteredExistingFields" :key="formIndex" :class="{ hidden: selectedKey !== form.key }">
                            <div v-for="(page, pIndex) in form.pages" :key="pIndex">
                                <div class="fui-existing-item-heading-wrap">
                                    <div class="fui-existing-item-heading">{{ page.label }}</div>
                                </div>

                                <div class="fui-row small-padding">
                                    <existing-field
                                        v-for="(field, fieldIndex) in page.fields"
                                        :key="fieldIndex"
                                        :selected="isFieldSelected(field)"
                                        v-bind="field"
                                        @selected="fieldSelected"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else>
                        <p>{{ t('formie', 'No fields found.') }}</p>
                    </div>
                </div>
            </div>

            <div v-else class="fui-modal-content-wrap">
                <div class="fui-modal-content">
                    <p>{{ t('formie', 'No existing fields to select.') }}</p>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="buttons left">
                <div class="spinner hidden"></div>
                <button class="btn" role="button" @click.prevent="closeModal">{{ t('app', 'Cancel') }}</button>
            </div>

            <div v-if="filteredExistingFields.length" class="buttons right">
                <span class="hidden">{{ t('formie', '"Add as a new field" to make a new field from a copy the original field. Or, "Add as a synced field" to keep the field synchronized to the original field, reflecting any changes to that field.') }}</span>

                <menu-btn :disabled="totalSelected === 0">
                    <template #primary="{ disabled }">
                        <input
                            type="submit"
                            :value="submitText"
                            :disabled="disabled"
                            class="btn submit"
                            :class="{ 'disabled': disabled }"
                            @click.prevent="addFields"
                        >
                    </template>

                    <ul>
                        <li>
                            <a href="#" @click.prevent="addSynced">
                                {{ syncedText }}
                            </a>
                        </li>
                    </ul>
                </menu-btn>

                <div class="spinner hidden"></div>
            </div>
        </template>
    </modal>
</template>

<script>
import { mapState } from 'vuex';
import { findIndex } from 'lodash-es';
import { newId } from '@utils/string';
import { clone } from '@utils/object';
import { clonedFieldSettings } from '@utils/fields';

import Modal from '@components/Modal.vue';
import MenuBtn from '@components/MenuBtn.vue';
import ExistingField from '@components/ExistingField.vue';

export default {
    name: 'ExistingFieldModal',

    components: {
        Modal,
        MenuBtn,
        ExistingField,
    },

    data() {
        return {
            error: false,
            errorMessage: '',
            loading: true,
            showModal: false,
            pageIndex: 0,
            search: '',
            selectedKey: '',
            selectedFields: [],
        };
    },

    computed: {
        ...mapState({
            existingFields: (state) => { return state.formie.existingFields; },
            form: (state) => { return state.form; },
        }),

        totalSelected() {
            return this.selectedFields.length;
        },

        filteredExistingFields() {
            const syncedFields = [];

            return this.existingFields.reduce((acc, form) => {
                const pages = form.pages.reduce((acc, page) => {
                    const fields = page.fields.filter((field) => {
                        const inLabel = field.settings.label.toLowerCase().includes(this.search.toLowerCase());
                        const inHandle = field.settings.handle.toLowerCase().includes(this.search.toLowerCase());

                        // When selecting all forms, ensure we filter out duplicate synced fields
                        if (this.selectedKey === '*') {
                            if (field.isSynced && !syncedFields.includes(field.handle)) {
                                syncedFields.push(field.handle);

                                return false;
                            }
                        }

                        return inLabel || inHandle;
                    });

                    return !fields.length ? acc : acc.concat({ ...page, fields });
                }, []);

                return !pages.length ? acc : acc.concat({ ...form, pages });
            }, []);
        },

        submitText() {
            if (this.totalSelected > 1) {
                return Craft.t('formie', 'Add {num} as new fields', { num: this.totalSelected });
            } if (this.totalSelected > 0) {
                return Craft.t('formie', 'Add {num} as new field', { num: this.totalSelected });
            }

            return Craft.t('formie', 'Add as new field');
        },

        syncedText() {
            if (this.totalSelected > 1) {
                return Craft.t('formie', 'Add {num} as synced fields', { num: this.totalSelected });
            } if (this.totalSelected > 0) {
                return Craft.t('formie', 'Add {num} as synced field', { num: this.totalSelected });
            }

            return Craft.t('formie', 'Add as synced field');
        },
    },

    created() {
        if (this.existingFields.length) {
            this.selectedKey = this.existingFields[0].key;
        }

        this.$events.on('formie:page-selected', (pageIndex) => {
            this.pageIndex = pageIndex;
        });
    },

    methods: {
        openModal() {
            this.showModal = true;
            this.loading = true;

            // Fetch existing fields via Ajax for performance
            if (!this.existingFields.length) {
                this.fetchExistingFields();
            } else {
                // For a large amount of fields, the modal will stutter when loading, so add a little delay
                // to ensure the modal opens, then loads the fields, to help with a nice UX.
                setTimeout(() => {
                    this.mounted = true;
                    this.loading = false;
                }, 100);
            }
        },

        closeModal() {
            this.selectedFields = [];

            this.showModal = false;
        },

        selectTab(key) {
            this.selectedKey = key;
        },

        isFieldSelected(field) {
            return findIndex(this.selectedFields, { id: field.id }) > -1;
        },

        fieldSelected(field, selected) {
            if (selected) {
                this.selectedFields.push(field);
            } else {
                const index = findIndex(this.selectedFields, { id: field.id });

                if (index > -1) {
                    this.selectedFields.splice(index, 1);
                }
            }
        },

        fetchExistingFields() {
            this.error = false;
            this.errorMessage = '';
            this.loading = true;

            const data = { formId: this.form.id };

            Craft.sendActionRequest('POST', 'formie/forms/get-existing-fields', { data }).then((response) => {
                this.loading = false;

                if (response.data.error) {
                    throw new Error(response.data.error);
                }

                // Update the store so we don't need to fetch again
                if (response.data) {
                    this.$store.dispatch('formie/setExistingFields', response.data);

                    // Auto-select the first tab ("All Forms")
                    this.selectTab('*');
                }

                this.mounted = true;
            }).catch((error) => {
                this.loading = false;
                this.error = true;

                this.errorMessage = error;

                if (error.response.data.error) {
                    this.errorMessage += `<br><code>${error.response.data.error}</code>`;
                }
            });
        },

        addFields() {
            for (const field of this.selectedFields) {
                const config = {
                    settings: clonedFieldSettings(field),
                };

                const newField = this.$store.getters['fieldtypes/newField'](field.type, config);

                this.addField(newField);
            }

            this.closeModal();
        },

        addSynced() {
            for (const field of this.selectedFields) {
                const config = {
                    isSynced: true,
                    settings: clonedFieldSettings(field),
                };

                config.settings.syncId = field.id;

                const newField = this.$store.getters['fieldtypes/newField'](field.type, config);

                this.addField(newField);
            }

            this.closeModal();
        },

        addField(newField) {
            // Construct the key path for the new page manually
            const destinationPath = ['pages', this.pageIndex, 'rows'];

            // Find the last row on the destination page and use that as the ID
            const destinationRows = this.$store.getters['form/valueByKeyPath'](destinationPath);

            // Figure out how to append it, by getting the last item index
            if (destinationRows && Array.isArray(destinationRows) && destinationRows.length) {
                destinationPath.push(destinationRows.length);
            } else {
                destinationPath.push(0);
            }

            const newRow = {
                __id: newId(),
                fields: [newField],
            };

            this.$store.dispatch('form/addField', {
                destinationPath,
                value: newRow,
            });

            this.$events.emit('formie:add-field');
        },
    },
};

</script>

<style lang="scss">

.fui-modal-footer .info {
    margin: 8px 10px 0 0;
}

.fui-existing-item-modal .fui-error-pane {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    z-index: 1;
}

.fui-existing-item-modal .fui-error-pane {
    align-items: center;
    justify-content: center;
    display: flex;

    [data-icon] {
        display: block;
        font-size: 3em;
        margin-bottom: 0.5rem;
    }
}

.fui-existing-item-modal .fui-error-content {
    text-align: center;
    width: 90%;
}

</style>
