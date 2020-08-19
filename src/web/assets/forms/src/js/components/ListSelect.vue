<template>
    <div class="field">
        <div class="heading">
            <label :id="id + '-label'" :for="id" :class="{ 'required': required }">{{ label }}</label>

            <div class="instructions">
                <p>{{ instructions }}</p>
            </div>
        </div>

        <div class="input ltr">
            <div class="select">
                <select v-model="proxyValue" :name="name" :required="required" @input="onInput">
                    <option v-for="(option, index) in proxyOptions" :key="index" :value="option.value">{{ option.label }}</option>
                </select>
            </div>

            <button class="btn fui-btn-transparent" :class="{ 'fui-loading fui-loading-sm': loading }" data-icon="refresh" @click.prevent="refresh"></button>
        </div>

        <div v-if="error" class="error" style="margin-top: 10px;">
            <span data-icon="alert"></span>
            <span v-html="errorMessage"></span>
        </div>
    </div>
</template>

<script>

export default {
    name: 'ListSelect',

    props: {
        label: {
            type: String,
            default: '',
        },

        instructions: {
            type: String,
            default: '',
        },

        id: {
            type: String,
            default: '',
        },

        name: {
            type: String,
            default: '',
        },

        nameRaw: {
            type: String,
            default: '',
        },

        first: {
            type: Boolean,
            default: false,
        },

        required: {
            type: Boolean,
            default: false,
        },

        value: {
            type: String,
            default: '',
        },

        handle: {
            type: String,
            default: '',
        },

        options: {
            type: Array,
            default: () => [],
        },

        inputAttributes: {
            type: Object,
            default: () => {},
        },
    },

    data() {
        return {
            proxyValue: '',
            proxyOptions: [],
            error: false,
            errorMessage: '',
            loading: false,
        };
    },

    created() {
        this.proxyOptions = this.options;
        this.proxyValue = this.value;
    },

    methods: {
        onInput($event) {
            this.emitEvent($event.target.value);
        },

        emitEvent(listId) {
            var eventKey = 'formie:integration-' + this.handle + '-' + this.nameRaw;

            this.$events.$emit(eventKey, { listId, integration: this.handle });
        },

        refresh() {
            this.error = false;
            this.errorMessage = '';
            this.loading = true;

            const payload = {
                integration: this.handle,
            };

            this.$axios.post(Craft.getActionUrl('formie/integrations/refresh-list'), payload).then((response) => {
                this.loading = false;

                if (response.data.error) {
                    this.error = true;

                    this.errorMessage = this.$options.filters.t('An error occurred.', 'formie');
                
                    if (response.data.error) {
                        this.errorMessage += '<br><code>' + response.data.error + '</code>';
                    }

                    return;
                }

                this.proxyOptions = response.data.listOptions;

                this.emitEvent(this.proxyValue);
            }).catch(error => {
                this.loading = false;
                this.error = true;

                this.errorMessage = error;
                
                if (error.response.data.error) {
                    this.errorMessage += '<br><code>' + error.response.data.error + '</code>';
                }
            });
        },
    },

};

</script>
