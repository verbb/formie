<template>
    <component :is="'div'">
        <modal ref="modal" modal-class="fui-edit-pages-modal" :is-visible="visible" @close="onModalCancel">
            <template slot="header">
                <h3 class="fui-modal-title">{{ 'Edit Pages' | t('formie') }}</h3>

                <div class="fui-dialog-close" @click.prevent="onModalCancel"></div>
            </template>

            <template slot="body">
                <FormulateForm ref="fieldForm" class="fui-pages-wrap" @submit="submitHandler">
                    <div class="fui-pages-sidebar">
                        <draggable
                            :list="pages"
                            :class="{ 'is-dragging': dragging }"
                            class="fui-pages-sidebar-items"
                            handle=".move.icon"
                            animation="150"
                            ghost-class="vue-admin-table-drag"
                            @start="dragging = true"
                            @end="dragging = false"
                        >
                            <div v-for="(page) in pages" :key="page.id" class="fui-pages-sidebar-item" :class="{ 'is-active': selectedPage === page.id, 'has-error': !isEmpty(page.errors) }" @click.prevent="selectPage(page.id)">
                                <div class="fui-pages-sidebar-item-name">
                                    <h4>
                                        {{ page.label }}
                                        <span v-if="!isEmpty(page.errors)" data-icon="alert" aria-label="Error"></span>
                                    </h4>
                                </div>

                                <a class="move icon" title="Reorder"></a>
                            </div>
                        </draggable>

                        <button type="button" class="btn add icon" @click.prevent="newPage">{{ 'New Page' | t('formie') }}</button>
                    </div>

                    <div class="fui-pages-pane">
                        <div v-for="(page) in pages" v-show="selectedPage === page.id" :key="page.id">
                            <FormulateInput
                                :value="get(page, 'label')"
                                type="text"
                                input-class="text fullwidth"
                                autocomplete="off"
                                :label="$options.filters.t('Page Label', 'formie')"
                                :help="$options.filters.t('The label for this page.', 'formie')"
                                validation="required"
                                :validation-name="$options.filters.t('Page Label', 'formie')"
                                :required="true"
                                :error="get(page.errors, 'name.0')"
                                @input="set(page, 'label', $event)"
                            />

                            <FormulateInput
                                v-model="page.settings.enablePageConditions"
                                type="lightswitch"
                                label-position="before"
                                :label="$options.filters.t('Enable Conditions', 'formie')"
                                :help="$options.filters.t('Whether to enable conditional logic to control how this page is shown.', 'formie')"
                            />

                            <ToggleGroup conditional="settings.enablePageConditions" :model="page">
                                <!-- eslint-disable-next-line -->
                                <FormulateInput v-model="page.settings.pageConditions" type="fieldConditions" descriptionText="this page if" />
                            </ToggleGroup>

                            <div v-if="pages.length > 1">
                                <hr>

                                <a class="error delete" @click.prevent="deletePage(page)">{{ 'Delete' | t('app') }}</a>
                            </div>
                        </div>
                    </div>
                </FormulateForm>
            </template>

            <template slot="footer">
                <div class="buttons right">
                    <div class="btn" role="button" @click.prevent="onModalCancel">{{ 'Close' | t('app') }}</div>
                    <div class="btn submit" role="button" @click.prevent="savePages">{{ 'Apply' | t('app') }}</div>
                    <div class="spinner hidden"></div>
                </div>
            </template>
        </modal>
    </component>
</template>

<script>
import { mapState } from 'vuex';
import get from 'lodash/get';
import set from 'lodash/set';
import isEmpty from 'lodash/isEmpty';
import { newId } from '../utils/string';

// eslint-disable-next-line
import Draggable from '@vuedraggable';

import Modal from './Modal.vue';
import ToggleGroup from './formulate/ToggleGroup.vue';

export default {
    name: 'FieldPageModal',

    components: {
        Modal,
        Draggable,
        ToggleGroup,
    },

    props: {
        visible: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            originalPages: null,
            dragging: false,
            selectedPage: 0,
        };
    },

    computed: {
        ...mapState({
            pages: state => state.form.pages,
        }),
    },

    created() {
        // Store this so we can cancel changes.
        this.originalPages = clone(this.pages);

        this.selectedPage = this.pages[0].id;
    },

    methods: {
        get(collection, key) {
            return get(collection, key);
        },

        set(collection, key, value) {
            set(collection, key, value);
        },

        isEmpty(object) {
            return isEmpty(object);
        },

        onModalCancel() {
            // Restore original state and exit
            this.$store.state.form.pages = this.originalPages;

            this.$emit('close');
        },

        deletePage(page) {
            const confirmationMessage = Craft.escapeHtml(Craft.t('formie', 'Are you sure you want to delete “{name}”? This will also delete all fields for this page, and cannot be undone.', { name: page.label }));

            if (confirm(confirmationMessage)) {
                var index = this.pages.indexOf(page);

                this.pages.splice(index, 1);
            }
        },

        newPage() {
            var newPageId = newId();

            this.pages.push({
                id: newPageId,
                label: Craft.t('formie', 'New Page'),
                enableConditions: false,
                rows: [],
            });

            this.selectedPage = newPageId;
        },

        selectPage(index) {
            this.selectedPage = index;
        },

        submitHandler() {
            this.$emit('close');
        },

        savePages() {
            // Validate the form - this will prevent firing `submitHandler()` if it fails
            this.$refs.fieldForm.formSubmitted();
        },
    },
};

</script>

<style lang="scss">

.fui-edit-pages-modal.fui-modal {
    width: 60%;
    min-height: 400px;
}

</style>

<style lang="scss" scoped>

.fui-pages-wrap {
    display: flex;
    min-height: 100%;
}

.fui-pages-sidebar {
    width: 200px;
    background: #f3f7fb;
}

.fui-pages-sidebar-items {
    margin-top: -1px;
    padding-top: 1px;
}

.fui-pages-sidebar-item {
    position: relative;
    display: flex;
    align-items: center;
    user-select: none;
    cursor: default;
    min-height: 48px;
    box-sizing: border-box;
    margin-top: -1px;
    padding: 8px 14px;
    border: solid rgba(51, 64, 77, 0.1);
    border-width: 1px 0;
    background-color: #e4edf6;
    cursor: pointer;

    &.is-active {
        background-color: #cdd8e4;
        z-index: 1;
    }

    &.has-error h4 {
        color: #CF1124;
    }
}

.fui-pages-sidebar-item h4 {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 2px;
    font-weight: normal;
    color: #3f4d5a;

    span {
        display: inline-flex;
    }
}

.fui-pages-sidebar-item-name {
    flex: 1;
    overflow: hidden;
}

.fui-pages-sidebar .btn {
    margin: 14px;
}

.fui-pages-pane {
    flex: 1;
    z-index: 1;
    margin-left: -1px;
    border-left: 1px rgba(31, 41, 51, 0.15) solid;
    padding: 20px;
}

</style>
