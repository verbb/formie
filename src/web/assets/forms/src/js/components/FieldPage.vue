<template>
    <div class="fui-fields-collection">
        <field-row
            v-for="(row, index) in rows"
            ref="rows"
            :key="row.id"
            :row-index="index"
            :page-index="pageIndex"
            v-bind="row"
        />

        <div class="fui-row no-padding">
            <div class="fui-col-12">
                <dropzone-new-field v-if="!rows.length" :page-index="pageIndex" />
            </div>
        </div>

        <div v-if="rows.length" class="fui-row no-padding">
            <div class="fui-col-12">
                <submit-buttons :page-index="pageIndex" :page-id="id" />
            </div>
        </div>
    </div>
</template>

<script>
import FieldRow from '@components/FieldRow.vue';
import DropzoneNewField from '@components/DropzoneNewField.vue';
import SubmitButtons from '@components/SubmitButtons.vue';

export default {
    name: 'FieldPage',

    components: {
        FieldRow,
        DropzoneNewField,
        SubmitButtons,
    },

    props: {
        id: {
            type: [String, Number],
            default: '',
        },

        pageIndex: {
            type: Number,
            default: 0,
        },

        label: {
            type: String,
            default: '',
        },

        rows: {
            type: Array,
            default: () => { return []; },
        },

        settings: {
            type: Object,
            default: () => {},
        },
    },

    data() {
        return {
            dropzonesActive: false,
        };
    },

    created() {
        this.$events.on('formie:dragging-active', () => {
            this.dropzonesActive = true;
        });

        this.$events.on('formie:dragging-inactive', () => {
            this.dropzonesActive = false;
        });

        // Generate a new submit button
        if (!this.settings) {
            const payload = {
                pageIndex: this.pageIndex,
                data: {
                    submitButtonLabel: Craft.t('formie', 'Submit'),
                    showBackButton: this.pageIndex !== 0,
                    backButtonLabel: Craft.t('formie', 'Back'),
                    buttonsPosition: 'left',
                    saveButtonLabel: Craft.t('formie', 'Save'),
                    saveButtonStyle: 'link',
                },
            };

            this.$store.dispatch('form/addPageSettings', payload);
        }
    },

};

</script>
