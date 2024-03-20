<template>
    <vue-final-modal
        v-bind="$attrs"
        :modal-id="id"
        :z-index-fn="zIndexFn"
        :esc-to-close="true"
        class="fui-modal fui-editor-modal"
        content-class="fui-modal-wrap fui-editor-modal-wrap"
        overlay-class="fui-modal-overlay fui-editor-modal-overlay"
        content-transition="vfm-fade"
        overlay-transition="vfm-fade"
        :focus-trap="focusTrapOptions"
        @opened="opened"
    >
        <div class="fui-modal-header fui-editor-modal-header">
            <slot name="title"></slot>
            <div class="fui-dialog-close fui-editor-modal-close" @click.prevent="$emit('update:modelValue', false)"></div>
        </div>

        <div ref="modalBody" class="fui-modal-body fui-editor-modal-body">
            <slot></slot>
        </div>

        <div class="fui-modal-footer fui-editor-modal-footer">
            <div class="buttons left">
                <div class="spinner hidden"></div>
            </div>

            <div class="buttons right">
                <div role="button" class="btn" tabindex="0" @click.prevent="$emit('cancel')">
                    {{ t('formie', cancelButton) }}
                </div>

                <div role="button" class="btn submit" @click.prevent="$emit('confirm')">
                    {{ t('formie', confirmButton) }}
                </div>
            </div>
        </div>
    </vue-final-modal>
</template>

<script>
import { VueFinalModal } from 'vue-final-modal';

export default {
    name: 'MenuBarModal',

    components: {
        VueFinalModal,
    },

    inheritAttrs: false,

    props: {
        cancelButton: {
            type: String,
            default: 'Cancel',
        },

        confirmButton: {
            type: String,
            default: 'Confirm',
        },
    },

    emits: ['update:modelValue', 'confirm', 'cancel'],

    data() {
        return {
            id: this.$id('modal'),

            focusTrapOptions: {
                allowOutsideClick: true,
            },
        };
    },

    methods: {
        zIndexFn({ index }) {
            return 100 + 2 * index;
        },

        opened() {
            this.$nextTick().then(() => {
                setTimeout(() => {
                    if (this.$refs.modalBody) {
                        const $firstText = this.$refs.modalBody.querySelector('input[type="text"]');

                        if ($firstText) {
                            $firstText.focus();
                        }
                    }
                });
            }, 50);
        },
    },
};

</script>

<style lang="scss">

.fui-modal-wrap.fui-editor-modal-wrap {
    width: 90vw;
    height: 90vh;
    max-width: 650px;
    max-height: 450px;
}

.fui-editor-modal-header {
    font-weight: 600;
    font-size: 15px;
    line-height: 30px;
}

.fui-editor-modal-body {
    height: 100%;
    overflow: auto;
    padding: 24px;
}

</style>
