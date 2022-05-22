<template>
    <multiselect
        v-model="context._value"
        v-bind="context.attrs"
        :options="options"
        :multiple="true"
        track-by="value"
        label="label"
        :searchable="true"
        :hide-selected="true"
        :allow-empty="true"
        :show-labels="false"
        :close-on-select="false"
        tag-placeholder="test"
    />
</template>

<script>
import { get } from 'lodash-es';
import Multiselect from 'vue-multiselect';

export default {
    components: {
        Multiselect,
    },

    props: {
        context: {
            type: Object,
            default: () => {},
        },
    },

    computed: {
        options() {
            return get(this.context.attrs, 'options', {});
        },
    },

    created() {
        if (!Array.isArray(this.context._value)) {
            this.context.node.input([]);
        }
    },
};

</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<style lang="scss">

.multiselect__tags {
    border: 1px solid rgba(96, 125, 159, 0.25);
    font-size: 13px;
}

.multiselect__tag {
    color: #3f4d5a;
    background-color: rgba(96, 125, 159, 0.25);
    padding: 6px 26px 6px 10px;
    border-radius: 3px;
}

.multiselect__tag-icon {
    border-radius: 0;
}

.multiselect__tag-icon:focus:after,
.multiselect__tag-icon:hover:after,
.multiselect__tag-icon:after {
    color: #3f4d5a;
}

.multiselect__tag-icon:focus,
.multiselect__tag-icon:hover {
    background: rgba(96, 125, 159, 0.1);
}

.multiselect__input,
.multiselect__single {
    font-size: 14px;
    min-height: 18px;
    line-height: 26px;
    border-radius: 0;
    padding: 0;

    &:focus {
        box-shadow: none;
    }
}

.multiselect__option {
    padding: 8px;
    min-height: auto;
    font-size: 14px;
    line-height: 14px;
}

.multiselect__option--highlight {
    background: #e4edf6;
    color: inherit;
}

.multiselect__placeholder {
    color: #596673;
    font-size: 14px;
}


</style>
