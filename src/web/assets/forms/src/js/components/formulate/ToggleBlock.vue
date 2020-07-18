<template>
    <div v-if="!showOnlyIfEnabled" class="fui-toggle-block" :class="{ 'has-errors': hasErrors }">
        <div class="fui-tb-header">
            <FormulateInput
                v-if="showEnabled"
                type="lightswitch"
                classes="extra-small"
                :name="context.attributes.blockHandle + 'Enabled'"
                @input="toggleEnabled"
            />

            <span class="fui-tb-header-title">{{ context.attributes.blockLabel }}</span>

            <FormulateInput
                v-if="showToggle"
                ref="collapse"
                type="collapse"
                :name="context.attributes.blockHandle + 'Collapsed'"
                @input="toggleCollapse"
            />
        </div>

        <transition-expand>
            <div v-show="!isCollapsed">
                <div class="fui-tb-body">
                    <slot></slot>
                </div>
            </div>
        </transition-expand>
    </div>
</template>

<script>
import TransitionExpand from '../TransitionExpand.vue';

export default {
    name: 'ToggleBlock',

    components: {
        TransitionExpand,
    },

    props: {
        context: {
            type: Object,
            required: true,
        },

        fireCreatedEvents: {
            type: Boolean,
            default: true,
        },

        hasErrors: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            rendered: false,
            isCollapsed: false,
            isEnabled: true,
        };
    },

    computed: {
        showToggle() {
            if (this.context.attributes.showToggle !== undefined) {
                return this.context.attributes.showToggle;
            }

            return true;
        },

        showEnabled() {
            if (this.context.attributes.showEnabled !== undefined) {
                return this.context.attributes.showEnabled;
            }

            return true;
        },

        showOnlyIfEnabled() {
            if (this.context.attributes.showOnlyIfEnabled && this.$editingField) {
                return !this.$editingField.field.settings[this.context.attributes.blockHandle + 'Enabled'];
            }

            return false;
        },
    },

    created() {
        // Setup defaults from the model. We don't have access to them in this case with Formulate...
        // I'd like a much nicer way to handle this!
        if (this.$editingField) {
            if (this.showEnabled) {
                this.isEnabled = this.$editingField.field.settings[this.context.attributes.blockHandle + 'Enabled'];
            }

            if (this.showToggle) {
                this.isCollapsed = this.$editingField.field.settings[this.context.attributes.blockHandle + 'Collapsed'];
            }
        }
    },

    mounted() {
        this.rendered = true;
    },

    methods: {
        toggleEnabled(value) {
            // The input seems to trigger too quickly on-load
            if (!this.rendered) {
                return;
            }

            this.isEnabled = value;
            this.isCollapsed = !this.isEnabled;

            // Update the settings model. Seemingly need a delay here, not _quite_ sure why...
            setTimeout(() => {
                this.$set(this.$editingField.field.settings, this.context.attributes.blockHandle + 'Collapsed', this.isCollapsed);
            }, 100);
        },

        toggleCollapse(value) {
            // The input seems to trigger too quickly on-load
            if (!this.rendered) {
                return;
            }

            this.isCollapsed = value;
        },
    },

};

</script>

