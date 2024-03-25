<template>
    <div class="fui-repeater-fields" :class="{ 'fui-empty': rows.length === 0 }">
        <div class="fui-fields-collection">
            <field-row
                v-for="(row, index) in rows"
                ref="rows"
                :key="row.__id"
                :row-index="index"
                :field-id="id"
                :parent-field-id="field.__id"
                :is-nested="true"
                v-bind="row"
            />

            <div class="fui-row no-padding">
                <div class="fui-col-12">
                    <dropzone-new-field v-if="!rows.length" :parent-id="field.__id" :is-nested="true" />
                </div>
            </div>

            <div v-if="rows.length !== 0 " class="fui-repeater-button">
                <span class="fui-field-pill-icon" v-html="fieldType.icon"></span>
                {{ field.settings.addLabel }}
            </div>
        </div>
    </div>
</template>

<script>
import FieldRow from '@components/FieldRow.vue';
import DropzoneNewField from '@components/DropzoneNewField.vue';

export default {
    name: 'FieldRepeater',

    components: {
        FieldRow,
        DropzoneNewField,
    },

    props: {
        id: {
            type: String,
            required: true,
        },
    },

    computed: {
        field() {
            const field = this.$store.getters['form/field'](this.id);

            if (!field) {
                return {
                    settings: {},
                };
            }

            return field;
        },

        fieldType() {
            return this.$store.getters['fieldtypes/fieldtype'](this.field.type);
        },

        rows() {
            return this.field.settings?.rows || [];
        },
    },
};
</script>
