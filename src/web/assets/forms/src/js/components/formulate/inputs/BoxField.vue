<template>
    <div
        :class="`formulate-input-element formulate-input-element--${context.type}`"
        :data-type="context.type"
    >
        <!--
      This explicit break out of types is due to a Vue bug that causes IE11 to
      not when using v-model + dynamic :type + :value (thanks @Christoph-Wagner)
      https://github.com/vuejs/vue/issues/8379
    -->
        <input
            v-if="type === 'radio'"
            v-model="context.model"
            type="radio"
            :value="context.value"
            v-bind="attributes"
            v-on="$listeners"
            @blur="context.blurHandler"
        >
        <input
            v-else
            v-model="context.model"
            type="checkbox"
            class="checkbox"
            :value="context.value"
            v-bind="attributes"
            v-on="$listeners"
            @blur="context.blurHandler"
        >
        <label
            class="formulate-input-element-decorator"
            :for="context.id"
        ></label>
    </div>
</template>

<script>
import FormulateInputMixin from '@braid/vue-formulate/src/FormulateInputMixin';

export default {
    name: 'BoxField',

    mixins: [FormulateInputMixin],
};

</script>
