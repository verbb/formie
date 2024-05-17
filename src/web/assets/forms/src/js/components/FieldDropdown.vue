<template>
    <div class="fui-field-actions">
        <button class="fui-field-settings fui-tab-btn action-btn menubtn" role="button" type="button" :data-menu-anchor="$idRef('edit-field-anchor')">
            <div :id="$id('edit-field-anchor')"></div>
        </button>

        <div class="fui-menu menu menu--disclosure" data-align="right">
            <ul class="padded">
                <!-- Use `v-show` not `v-if` as Craft's bindings won't play well here otherwise -->
                <field-dropdown-item v-show="canEdit" icon="edit" action="edit" label="Edit" @clicked="selectOption" />
                <field-dropdown-item v-show="!isRequired && canRequire" icon="asterisk" action="require" label="Make required" @clicked="selectOption" />
                <field-dropdown-item v-show="isRequired && canRequire" icon="asterisk" action="unrequire" label="Make optional" @clicked="selectOption" />
                <field-dropdown-item v-show="canClone" icon="plus" action="clone" label="Clone" @clicked="selectOption" />

                <li>
                    <hr class="padded">
                </li>

                <field-dropdown-item v-show="canDelete" icon="remove" action="delete" label="Delete" classes="error" @clicked="selectOption" />
            </ul>
        </div>
    </div>
</template>

<script>
import FieldDropdownItem from '@components/FieldDropdownItem.vue';

export default {
    name: 'FieldEditTab',

    components: {
        FieldDropdownItem,
    },

    props: {
        isRequired: {
            type: Boolean,
            default: false,
        },

        canEdit: {
            type: Boolean,
            default: true,
        },

        canRequire: {
            type: Boolean,
            default: true,
        },

        canClone: {
            type: Boolean,
            default: true,
        },

        canDelete: {
            type: Boolean,
            default: true,
        },
    },

    emits: ['edit', 'require', 'unrequire', 'clone', 'delete'],

    mounted() {
        Craft.initUiElements();
    },

    methods: {
        selectOption(action) {
            // Wait for UI to settle before actioning
            setTimeout(() => {
                this.$emit(action);
            }, 150);
        },
    },
};

</script>
