<template>
    <li>
        <a
            class="fui-tab-item"
            :class="{ 'sel': isActive, 'error': hasError }"
            :href="hash"
            @click.prevent="selectTab"
        >
            {{ label }}
        </a>
    </li>
</template>

<script>
import { kebabCase } from 'lodash-es';

export default {
    name: 'FieldEditTab',

    emits: ['selected'],

    props: {
        pageIndex: {
            type: Number,
            default: 0,
        },

        label: {
            type: String,
            default: '',
        },

        handle: {
            type: String,
            default: '',
        },

        hasError: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            isActive: false,
        };
    },

    computed: {
        hash() {
            return 'tab-' + kebabCase(this.label);
        },
    },

    mounted() {
        if (this.pageIndex == 0) {
            this.isActive = true;
        }
    },

    methods: {
        selectTab(event) {
            this.$emit('selected', this.handle);
        },
    },
};

</script>
