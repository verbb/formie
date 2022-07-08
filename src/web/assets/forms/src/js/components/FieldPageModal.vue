<template>
    <modal ref="modal" v-model="showModal" modal-class="fui-edit-pages-modal" @click-outside="onModalCancel">
        <template #header>
            <h3 class="fui-modal-title">{{ t('formie', 'Edit Pages') }}</h3>

            <div class="fui-dialog-close" @click.prevent="onModalCancel"></div>
        </template>

        <template #body>
            <FormKitForm ref="fieldForm" @submit="submitHandler">
                <div class="fui-pages-wrap">
                    <div class="fui-pages-sidebar">
                        <draggable
                            :list="pages"
                            :class="{ 'is-dragging': dragging }"
                            class="fui-pages-sidebar-items"
                            handle=".move.icon"
                            animation="150"
                            ghost-class="vue-admin-table-drag"
                            item-key="id"
                            @start="dragging = true"
                            @end="dragging = false"
                        >
                            <template #item="{ element }">
                                <div class="fui-pages-sidebar-item" :class="{ 'is-active': selectedPage === element.id, 'has-error': !isEmpty(element.errors) }" @click.prevent="selectPage(element.id)">
                                    <div class="fui-pages-sidebar-item-name">
                                        <h4>
                                            {{ element.label }}
                                            <span v-if="!isEmpty(element.errors)" data-icon="alert" aria-label="Error"></span>
                                        </h4>
                                    </div>

                                    <a class="move icon" title="Reorder"></a>
                                </div>
                            </template>
                        </draggable>

                        <button type="button" class="btn add icon" @click.prevent="newPage">{{ t('formie', 'New Page') }}</button>
                    </div>

                    <div class="fui-pages-pane">
                        <div v-for="(page) in pages" v-show="selectedPage === page.id" :key="page.id">
                            <FormKit
                                :value="get(page, 'label')"
                                type="text"
                                input-class="text fullwidth"
                                autocomplete="off"
                                :label="t('formie', 'Page Label')"
                                :help="t('formie', 'The label for this page.')"
                                validation="required"
                                :required="true"
                                :error="get(page.errors, 'name.0')"
                                @input="set(page, 'label', $event)"
                            />

                            <FormKit
                                v-model="page.settings.enablePageConditions"
                                type="lightswitch"
                                label-position="before"
                                :label="t('formie', 'Enable Conditions')"
                                :help="t('formie', 'Whether to enable conditional logic to control how this page is shown.')"
                            />

                            <!-- eslint-disable-next-line -->
                            <FormKit v-if="page.settings.enablePageConditions" v-model="page.settings.pageConditions" type="fieldConditions" descriptionText="this page if" :isPageModal="true" :page="page" />

                            <div v-if="pages.length > 1">
                                <hr>

                                <a class="error delete" @click.prevent="deletePage(page)">{{ t('app', 'Delete') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </FormKitForm>
        </template>

        <template #footer>
            <div class="buttons right">
                <div class="btn" role="button" @click.prevent="onModalCancel">{{ t('app', 'Close') }}</div>
                <div class="btn submit" role="button" @click.prevent="savePages">{{ t('app', 'Apply') }}</div>
                <div class="spinner hidden"></div>
            </div>
        </template>
    </modal>
</template>

<script>
import { mapState } from 'vuex';
import Draggable from 'vuedraggable';
import { get, set, isEmpty } from 'lodash-es';
import { newId } from '@utils/string';

import Modal from '@components/Modal.vue';

export default {
    name: 'FieldPageModal',

    components: {
        Modal,
        Draggable,
    },

    props: {
        showModal: {
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
            pages: (state) => { return state.form.pages; },
        }),
    },

    created() {
        // Store this so we can cancel changes.
        this.originalPages = this.clone(this.pages);

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

        closeModal() {
            // Close the modal programatically, which will fire `@closed`
            this.$refs.modal.close();
        },

        onModalCancel() {
            // Restore original state and exit
            this.$store.state.form.pages = this.originalPages;

            this.closeModal();
        },

        deletePage(page) {
            const confirmationMessage = Craft.escapeHtml(Craft.t('formie', 'Are you sure you want to delete “{name}”? This will also delete all fields for this page, and cannot be undone.', { name: page.label }));

            if (confirm(confirmationMessage)) {
                const index = this.pages.indexOf(page);

                this.pages.splice(index, 1);
            }
        },

        newPage() {
            const newPageId = newId();

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
            this.closeModal();
        },

        savePages() {
            // Validate the form - this will prevent firing `submitHandler()` if it fails
            this.$refs.fieldForm.submit();
        },
    },
};

</script>

<style lang="scss">

.fui-edit-pages-modal .fui-modal-wrap {
    width: 60%;
    min-height: 400px;
}

.fui-edit-pages-modal .formkit-form {
    min-height: 100%;
    display: flex;
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
