<template>
    <component :is="'div'">
        <div class="btn add icon dashed" @click="showModal">{{ 'Add existing fields' | t('formie') }}</div>

        <modal ref="modal" :modal-class="['fui-edit-field-modal', 'fui-existing-item-modal']">
            <template slot="header">
                <h3 class="fui-modal-title">{{ 'Add Existing Field' | t('formie') }}</h3>

                <div class="fui-dialog-close" @click.prevent="hideModal"></div>
            </template>

            <template slot="body">
                <div v-if="existingFields.length" class="fui-modal-content-wrap">
                    <div class="fui-modal-sidebar sidebar">
                        <nav v-if="filteredExistingFields.length">
                            <ul>
                                <li v-if="existingFields.length">
                                    <a :class="{ 'sel': selectedKey === existingFields[0].key }" @click.prevent="selectTab(existingFields[0].key)">
                                        <span class="label">{{ existingFields[0].label }}</span>
                                    </a>
                                </li>

                                <li class="heading"><span>{{ 'Forms' | t('formie') }}</span></li>

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
                            <p>{{ 'No fields found.' | t('formie') }}</p>
                        </div>
                    </div>
                </div>

                <div v-else class="fui-modal-content-wrap">
                    <div class="fui-modal-content">
                        <p>{{ 'No existing fields to select.' | t('formie') }}</p>
                    </div>
                </div>
            </template>

            <template slot="footer">
                <div class="buttons left">
                    <div class="spinner hidden"></div>
                    <div class="btn" role="button" @click.prevent="hideModal">{{ 'Cancel' | t('app') }}</div>
                </div>

                <div v-if="filteredExistingFields.length" class="buttons right">
                    <span class="hidden">{{ '"Add as a new field" to make a new field from a copy the original field. Or, "Add as a synced field" to keep the field synchronized to the original field, reflecting any changes to that field.' | t('formie') }}</span>

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
                                <a @click.prevent="addSynced">
                                    {{ syncedText }}
                                </a>
                            </li>
                        </ul>
                    </menu-btn>

                    <div class="spinner hidden"></div>
                </div>
            </template>
        </modal>
    </component>
</template>

<script>
import { mapState } from 'vuex';
import findIndex from 'lodash/findIndex';
import { newId } from '../utils/string';

import Modal from './Modal.vue';
import MenuBtn from './MenuBtn.vue';
import ExistingField from './ExistingField.vue';

export default {
    name: 'ExistingFieldModal',

    components: {
        Modal,
        MenuBtn,
        ExistingField,
    },

    data() {
        return {
            pageIndex: 0,
            search: '',
            selectedKey: '',
            selectedFields: [],
        };
    },

    computed: {
        ...mapState({
            existingFields: state => state.formie.existingFields,
            form: state => state.form,
        }),

        totalSelected() {
            return this.selectedFields.length;
        },

        filteredExistingFields() {
            return this.existingFields.reduce((acc, form) => {
                const pages = form.pages.reduce((acc, page) => {
                    const fields = page.fields.filter(field => {
                        const inLabel = field.label.toLowerCase().includes(this.search.toLowerCase());
                        const inHandle = field.handle.toLowerCase().includes(this.search.toLowerCase());

                        return inLabel || inHandle;
                    });

                    return !fields.length ? acc : acc.concat(Object.assign({}, page, { fields }));
                }, []);

                return !pages.length ? acc : acc.concat(Object.assign({}, form, { pages }));
            }, []);
        },

        submitText() {
            if (this.totalSelected > 1) {
                return this.$options.filters.t('Add {num} as new fields', 'formie', { num: this.totalSelected });
            } else if (this.totalSelected > 0) {
                return this.$options.filters.t('Add {num} as new field', 'formie', { num: this.totalSelected });
            } else {
                return this.$options.filters.t('Add as new field', 'formie');
            }
        },

        syncedText() {
            if (this.totalSelected > 1) {
                return this.$options.filters.t('Add {num} as synced fields', 'formie', { num: this.totalSelected });
            } else if (this.totalSelected > 0) {
                return this.$options.filters.t('Add {num} as synced field', 'formie', { num: this.totalSelected });
            } else {
                return this.$options.filters.t('Add as synced field', 'formie');
            }
        },
    },

    created() {
        if (this.existingFields.length) {
            this.selectedKey = this.existingFields[0].key;
        }

        this.$events.on('formie:page-selected', pageIndex => {
            this.pageIndex = pageIndex;
        });
    },

    methods: {
        showModal() {
            this.$refs.modal.showModal();
        },

        hideModal() {
            this.selectedFields = [];
            this.$refs.modal.hideModal();
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

        addFields() {
            for (const field of this.selectedFields) {
                const config = {
                    label: field.label,
                    handle: field.handle,
                    settings: field.settings,
                };

                const newField = this.$store.getters['fieldtypes/newField'](field.type, config);
                const rowCount = this.form.pages[this.pageIndex].rows.length;

                const payload = {
                    pageIndex: this.pageIndex,
                    rowIndex: rowCount,
                    data: {
                        id: newId(),
                        fields: [
                            newField,
                        ],
                    },
                };

                this.$store.dispatch('form/appendRow', payload);
            }

            this.hideModal();
        },

        addSynced() {
            for (const field of this.selectedFields) {
                const rowCount = this.form.pages[this.pageIndex].rows.length;

                const newField = {
                    id: `sync:${field.id}`,
                    label: field.label,
                    handle: field.handle,
                    type: field.type,
                    isSynced: true,
                    settings: field.settings,
                };

                const payload = {
                    pageIndex: this.pageIndex,
                    rowIndex: rowCount,
                    data: {
                        id: newId(),
                        fields: [
                            newField,
                        ],
                    },
                };

                this.$store.dispatch('form/appendRow', payload);
            }

            this.hideModal();
        },
    },
};

</script>

<style lang="scss">

.fui-modal-footer .info {
    margin: 8px 10px 0 0;
}

</style>
