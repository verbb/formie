<template>
    <div class="fui-col-6">
        <div class="fui-existing-item" :class="{ 'sel': selected, 'disabled': isDisabled }" @click="click">
            <div class="fui-existing-item-wrap">
                <div class="fui-existing-item-title">
                    {{ name }}
                </div>

                <div class="fui-existing-item-type">
                    {{ subject }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ExistingNotification',

    props: {
        id: {
            type: [String, Number],
            default: '',
        },

        name: {
            type: String,
            default: '',
        },

        subject: {
            type: String,
            default: '',
        },

        notification: {
            type: Object,
            default: () => {},
        },

        selected: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['selected'],

    computed: {
        notificationIds() {
            return this.$store.getters['form/notificationIds'];
        },

        isDisabled() {
            return this.notificationIds.indexOf(this.id) !== -1;
        },
    },

    methods: {
        click() {
            this.$emit('selected', this, !this.selected);
        },
    },
};

</script>
