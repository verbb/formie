<template>
    <div class="fui-toggle-block" :class="{ 'has-errors': hasErrors }">
        <div class="fui-tb-header">
            <FormKit
                v-if="showEnabled"
                :id="enabledHandle"
                type="lightswitch"
                :extra-small="true"
                :name="enabledHandle"
            />

            <span class="fui-tb-header-title">{{ $attrs.blockLabel }}</span>

            <FormKit
                v-if="showToggle"
                :id="collapsedHandle"
                type="collapse"
                :name="collapsedHandle"
            />
        </div>

        <slide-up-down :active="!isCollapsed" :duration="300">
            <div class="fui-tb-body">
                <slot></slot>
            </div>
        </slide-up-down>
    </div>
</template>

<script>
import { mapState } from 'vuex';

import SlideUpDown from '@components/SlideUpDown.vue';

export default {
    name: 'ToggleBlock',

    components: {
        SlideUpDown,
    },

    props: {
        hasErrors: {
            type: Boolean,
            default: false,
        },
    },

    computed: {
        ...mapState({
            editingField: (state) => { return state.formie.editingField; },
        }),

        enabledHandle() {
            return `${this.$attrs.blockHandle}Enabled`;
        },

        collapsedHandle() {
            return `${this.$attrs.blockHandle}Collapsed`;
        },

        isEnabled() {
            // Force state when disabled
            if (!this.showEnabled) {
                return true;
            }

            // We don't seemingly have access to the form model in a custom input, so reference it from the field
            if (this.editingField && this.editingField.field) {
                return this.editingField.field.settings[this.enabledHandle];
            }

            return true;
        },

        isCollapsed() {
            // Force state when disabled
            if (!this.showToggle) {
                return false;
            }

            // We don't seemingly have access to the form model in a custom input, so reference it from the field
            if (this.editingField && this.editingField.field) {
                return this.editingField.field.settings[this.collapsedHandle];
            }

            return false;
        },

        showToggle() {
            if (this.$attrs.showToggle !== undefined) {
                return this.$attrs.showToggle;
            }

            return true;
        },

        showEnabled() {
            if (this.$attrs.showEnabled !== undefined) {
                return this.$attrs.showEnabled;
            }

            return true;
        },
    },

    watch: {
        isEnabled(newValue) {
            // Changling the enabled state should also trigger a collapse
            if (this.editingField && this.editingField.field) {
                this.editingField.field.settings[this.collapsedHandle] = !newValue;
            }
        },
    },

};

</script>
