<template>
    <div>
        <multiselect
            v-model="context.model"
            v-bind="context.attributes"
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
    </div>
</template>

<script>
import Multiselect from 'vue-multiselect';
import FormulateInputMixin from '@braid/vue-formulate/src/FormulateInputMixin';

export default {
    name: 'MultiSelectField',

    components: {
        Multiselect,
    },

    mixins: [FormulateInputMixin],

    computed: {
        options() {
            return this.context.options || {};
        },
    },

    created() {
        if (!Array.isArray(this.context.model)) {
            this.context.model = [];
        }
    },
};

</script>

<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>

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
