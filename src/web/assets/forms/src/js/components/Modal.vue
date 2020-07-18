<template>
    <portal :to="to">
        <transition name="fui-modal-fade">
            <div v-if="created" class="modal-shade fui-modal-shade" :class="{ hidden: !visible }">
                <div class="fui-modal-shade-close" @click.prevent="hideModal"></div>

                <div class="modal fui-modal" :class="[modalClass, { 'has-header': showHeader, 'has-footer': showFooter }]" role="dialog" aria-labelledby="modalTitle" aria-describedby="modalDescription">
                    <header v-if="showHeader" id="modalTitle" class="fui-modal-header">
                        <slot name="header"></slot>
                    </header>

                    <section id="modalDescription" class="fui-modal-body">
                        <slot name="body"></slot>
                    </section>

                    <footer v-if="showFooter" class="footer fui-modal-footer">
                        <slot name="footer"></slot>
                    </footer>

                    <div class="resizehandle"></div>
                </div>
            </div>
        </transition>
    </portal>
</template>

<script>

export default {
    name: 'Modal',

    props: {
        to: {
            type: String,
            default: 'modals',
        },

        modalClass: {
            type: [String, Array],
            default: '',
        },

        isVisible: {
            type: Boolean,
            default: false,
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
            created: false,
            visible: false,
        };
    },

    mounted() {
        // Allow props to set the state immediately
        if (this.isVisible) {
            this.visible = true;
            this.created = true;
        }
    },

    methods: {
        showModal() {
            this.visible = true;
            this.created = true;
        },

        hideModal() {
            this.created = false;
            this.visible = false;

            this.$emit('close');
        },

        createModal() {
            // Control the rendered state of this modal. We sometimes want to trigger the rendering of content
            // for things like form validation, which won't work if the contents are hidden with v-if.
            // This method is usually called before validation.
            this.created = true;

            // Return a promise for the next DOM update, now that created has been set. This will take a little
            // bit of effort the first time, for larger fields, but will be much faster the next round.
            // We can listen on the callback to know when we're done with the render.
            return this.$nextTick();
        },

        destroyModal() {
            this.created = false;
            this.visible = false;

            this.$emit('destroy');
        },
    },
};

</script>

<style lang="scss">

.fui-modal-fade-enter,
.fui-modal-fade-leave-active {
    opacity: 0;
}

.fui-modal-fade-enter-active,
.fui-modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.fui-modal-shade {
    display: flex;
    justify-content: center;
    align-items: center;
}

.fui-modal-shade-close {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
}

.fui-modal-header {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    background-color: #f3f7fc;
    box-shadow: inset 0 -1px 0 rgba(51, 64, 77, 0.1);
    padding: 10px 24px;
    display: flex;
    align-items: center;
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
    background-image: url("data:image/svg+xml,%3Csvg aria-hidden='true' role='img' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3E%3Cpath fill='currentColor' d='M207.6 256l107.72-107.72c6.23-6.23 6.23-16.34 0-22.58l-25.03-25.03c-6.23-6.23-16.34-6.23-22.58 0L160 208.4 52.28 100.68c-6.23-6.23-16.34-6.23-22.58 0L4.68 125.7c-6.23 6.23-6.23 16.34 0 22.58L112.4 256 4.68 363.72c-6.23 6.23-6.23 16.34 0 22.58l25.03 25.03c6.23 6.23 16.34 6.23 22.58 0L160 303.6l107.72 107.72c6.23 6.23 16.34 6.23 22.58 0l25.03-25.03c6.23-6.23 6.23-16.34 0-22.58L207.6 256z'%3E%3C/path%3E%3C/svg%3E");
    opacity: 0.4;

    &:hover {
        opacity: 0.8;
    }
}

.fui-modal-body {
    height: 100%;
    position: relative;
    overflow: auto;
}

.has-header .fui-modal-body {
    top: 50px;
    height: calc(100% - 62px);
}

.has-header.has-footer .fui-modal-body {
    height: calc(100% - 112px);
}

.fui-modal-content {
    padding: 24px;
}

.fui-modal-footer {
    bottom: 0;
    left: 0;
    margin: 0;
    position: absolute;
    width: 100%;
}

</style>
