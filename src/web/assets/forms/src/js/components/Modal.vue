<template>
    <vue-final-modal
        :name="id"
        :ssr="false"
        v-bind="$attrs"
        :z-index-auto="true"
        z-index-base="100"
        :esc-to-close="true"
        attach="body"
        :classes="['fui-modal', modalClass]"
        content-class="fui-modal-wrap"
        overlay-class="fui-modal-overlay"
        transition="fui-modal"
        overlay-transition="fui-modal"
    >
        <header v-if="showHeader" id="modalTitle" class="fui-modal-header">
            <slot name="header"></slot>
        </header>

        <section id="modalDescription" class="fui-modal-body">
            <slot name="body"></slot>
        </section>

        <footer v-if="showFooter" class="fui-modal-footer">
            <slot name="footer"></slot>
        </footer>
    </vue-final-modal>
</template>

<script>
import { VueFinalModal, $vfm } from 'vue-final-modal';

export default {
    name: 'Modal',

    components: {
        VueFinalModal,
    },

    props: {
        modalClass: {
            type: [String, Array],
            default: '',
        },

        showHeader: {
            type: Boolean,
            default: true,
        },

        showFooter: {
            type: Boolean,
            default: true,
        },
    },

    data() {
        return {
            id: this.$id('modal'),
        };
    },

    methods: {
        showModal() {
            setTimeout(() => {
                $vfm.show(this.id);
            }, 10);
        },

        close() {
            // Programatically close the modal, so that we can update the `v-model` *after* the transition has occurred.
            // This is because we often use `v-if` for performance above this high-order component, but that won't work well with transitions.
            // Also give it a sec to be ready.
            setTimeout(() => {
                $vfm.hide(this.id);
            }, 10);
        },
    },
};

</script>

<style lang="scss">

.vfm {
    position: fixed !important;
}

.fui-modal-overlay {
    background-color: rgba(123, 135, 147, 0.35) !important;
}

.fui-modal {
    display: flex;
    justify-content: center;
    align-items: center;

    // Fix some colour-banding issues with modal box-shadow which only happens
    // with `position: absolute`.
    position: fixed !important;
}

.fui-modal-wrap {
    position: relative;
    display: flex;
    flex-direction: column;
    margin: 1rem;
    max-height: 100%;
    border-radius: 5px;
    background-color: #fff;
    box-shadow: 0 25px 100px rgba(31, 41, 51, 0.5);
    z-index: 100;
    overflow: hidden;

    width: 66%;
    height: 66%;
    min-width: 600px;
    min-height: 400px;
}

.fui-modal-header {
    width: 100%;
    background-color: #f3f7fc;
    box-shadow: inset 0 -1px 0 rgba(51, 64, 77, 0.1);
    padding: 10px 24px;
    display: flex;
    align-items: center;
    border-top-left-radius: 5px;
    border-top-right-radius: 5px;
}

.fui-modal-title {
    margin: 0;
    padding: 0;
    font-weight: 600;
    font-size: 15px;
    line-height: 30px;
}

.fui-dialog-close {
    width: 20px;
    height: 20px;
    margin-left: auto;
    cursor: pointer;
    background-size: 20px 20px;
    background-repeat: no-repeat;
    transition: all 0.3s ease;
    background-image: url("data:image/svg+xml,%3Csvg aria-hidden='true' role='img' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3E%3Cpath fill='%233f4d5a' d='M207.6 256l107.72-107.72c6.23-6.23 6.23-16.34 0-22.58l-25.03-25.03c-6.23-6.23-16.34-6.23-22.58 0L160 208.4 52.28 100.68c-6.23-6.23-16.34-6.23-22.58 0L4.68 125.7c-6.23 6.23-6.23 16.34 0 22.58L112.4 256 4.68 363.72c-6.23 6.23-6.23 16.34 0 22.58l25.03 25.03c6.23 6.23 16.34 6.23 22.58 0L160 303.6l107.72 107.72c6.23 6.23 16.34 6.23 22.58 0l25.03-25.03c6.23-6.23 6.23-16.34 0-22.58L207.6 256z'%3E%3C/path%3E%3C/svg%3E");
    opacity: 0.6;

    &:hover {
        opacity: 1;
    }
}

.fui-modal-body {
    height: 100%;
    position: relative;
    overflow: auto;
}

.fui-modal-content {
    padding: 24px;
}

.fui-modal-footer {
    width: 100%;
    background-color: #e4edf6;
    box-shadow: inset 0 1px 0 rgba(51, 64, 77, 0.1);
    padding: 10px 24px;
    border-bottom-left-radius: 5px;
    border-bottom-right-radius: 5px;

    & > .buttons {
        margin: 0;
    }
}

//
// Transitions
//

.fui-modal-enter-active,
.fui-modal-leave-active {
    transition: opacity 0.2s ease;
}

.fui-modal-enter-from,
.fui-modal-leave-to {
    opacity: 0;
}

</style>
