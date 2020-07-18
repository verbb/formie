<template>
    <div class="btngroup submit">
        <slot name="primary" :disabled="disabled"></slot>

        <div ref="submitBtn" class="btn submit menubtn" :class="{ 'disabled': disabled }"></div>

        <div class="menu">
            <slot></slot>
        </div>
    </div>
</template>

<script>
export default {
    name: 'MenuBtn',

    props: {
        disabled: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            menuBtn: null,
        };
    },

    watch: {
        disabled(newValue) {
            if (newValue) {
                this.menuBtn.disable();
            } else {
                this.menuBtn.enable();
            }
        },
    },

    mounted() {
        this.menuBtn = new Garnish.MenuBtn(this.$refs.submitBtn);

        if (this.disabled) {
            this.menuBtn.disable();
        }
    },
};
</script>
