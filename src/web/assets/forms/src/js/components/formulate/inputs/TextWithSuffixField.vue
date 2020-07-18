<template>
    <div
        :class="`formulate-input-element formulate-input-element--${context.type}`"
        :data-type="context.type"
    >
        <input
            v-model="context.model"
            :type="context.type"
            v-bind="context.attributes || {}"
            v-on="$listeners"
            @blur="context.blurHandler"
        >

        <span style="color: #7b8793;">{{ context.attributes.suffix }}</span>
    </div>
</template>

<script>
import FormulateInputMixin from '@braid/vue-formulate/src/FormulateInputMixin';

export default {
    name: 'TextWithSuffixField',

    mixins: [FormulateInputMixin],
};

</script>
