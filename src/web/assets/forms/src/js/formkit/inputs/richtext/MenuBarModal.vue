<template>
    <vue-final-modal
        :ssr="false"
        class="fui-editor-modal-wrap"
        v-bind="$attrs"
        classes="fui-editor-modal-container"
        content-class="fui-editor-modal-content"
        overlay-class="fui-editor-modal-overlay"
        :lock-scroll="false"
        @opened="opened"
    >
        <div class="fui-editor-modal-header">
            <slot name="title"></slot>
            <div class="fui-editor-modal-close" @click.prevent="$emit('update:modelValue', false)"></div>
        </div>

        <div class="fui-editor-modal-body">
            <slot></slot>
        </div>

        <div class="fui-editor-modal-footer footer">
            <div class="spinner hidden"></div>

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

export default {
    name: 'MenuBarModal',

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

    methods: {
        opened() {
            setTimeout(() => {
                this.$nextTick().then(() => {
                    const $firstText = this.$el.querySelector('input[type="text"]');

                    if ($firstText) {
                        $firstText.focus();
                    }
                });
            }, 50);
        },
    },
};

</script>

<style lang="scss">

.fui-editor-modal-wrap,
.fui-editor-modal-overlay {
    position: fixed !important;
}

.fui-editor-modal-container {
    display: flex;
    justify-content: center;
    align-items: center;
}

.fui-editor-modal-content {
    width: 90vw;
    height: 90vh;
    max-width: 650px;
    max-height: 450px;
    display: flex;
    flex-direction: column;
    position: relative;
    border-radius: 5px;
    background-color: #fff;
    box-shadow: 0 25px 100px rgba(31, 41, 51, 0.5);
}

.fui-editor-modal-overlay {
    background-color: rgba(228, 237, 246, 0.65) !important;
}

.fui-editor-modal-header {
    display: flex;
    align-items: center;
    border-radius: 5px 5px 0 0;
    background-color: #f3f7fc;
    box-shadow: inset 0 -1px 0 rgba(51, 64, 77, 0.1);
    padding: 10px 24px;
    font-weight: 600;
    font-size: 15px;
    line-height: 30px;
}

.fui-editor-modal-close {
    width: 20px;
    height: 20px;
    margin-left: auto;
    cursor: pointer;
    background-size: 20px 20px;
    background-repeat: no-repeat;
    transition: all 0.3s ease;
    background-image: url("data:image/svg+xml,%3Csvg aria-hidden='true' role='img' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3E%3Cpath fill='currentColor' d='M207.6 256l107.72-107.72c6.23-6.23 6.23-16.34 0-22.58l-25.03-25.03c-6.23-6.23-16.34-6.23-22.58 0L160 208.4 52.28 100.68c-6.23-6.23-16.34-6.23-22.58 0L4.68 125.7c-6.23 6.23-6.23 16.34 0 22.58L112.4 256 4.68 363.72c-6.23 6.23-6.23 16.34 0 22.58l25.03 25.03c6.23 6.23 16.34 6.23 22.58 0L160 303.6l107.72 107.72c6.23 6.23 16.34 6.23 22.58 0l25.03-25.03c6.23-6.23 6.23-16.34 0-22.58L207.6 256z'%3E%3C/path%3E%3C/svg%3E");
    opacity: 0.4;
}

.fui-editor-modal-body {
    height: 100%;
    overflow: auto;
    padding: 24px;
}

.fui-editor-modal-footer {
    width: 100%;
    margin: 0;
    background-color: #e4edf6;
    border-radius: 0 0 5px 5px;
    padding: 14px 24px;
    box-shadow: inset 0 1px 0 rgba(51, 64, 77, 0.1);
}

</style>
