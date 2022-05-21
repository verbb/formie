<template>
    <div class="fui-repeater-fields" :class="{ 'fui-empty': field.rows.length === 0 }">
        <div class="fui-fields-collection">
            <field-row
                v-for="(row, index) in field.rows"
                ref="rows"
                :key="row.id"
                :row-index="index"
                :field-id="id"
                :parent-field-id="field.vid"
                :is-nested="true"
                v-bind="row"
            />

            <div class="fui-row no-padding">
                <div class="fui-col-12">
                    <dropzone-new-field v-if="!field.rows.length" :field-id="id" :is-nested="true" />
                </div>
            </div>

            <div v-if="field.rows.length !== 0 " class="fui-repeater-button">
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
    },
};
</script>
