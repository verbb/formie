<script>
import { h, compile } from 'vue';
import { mapState } from 'vuex';
import { parseDate } from '@utils/string';
import sanitizeHtml from 'sanitize-html';

export default {
    name: 'FieldPreview',

    props: {
        id: {
            type: String,
            default: '',
        },

        expectedType: {
            type: String,
            default: '',
        },
    },

    computed: {
        ...mapState(['fields']),

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

    render() {
        let { preview } = this.fieldType;

        const props = {
            field: this.field,
            fieldType: this.fieldType,

            // JS date handling is a pain, this is mostly for date-pickers
            parseDate(date) {
                return parseDate(date);
            },

            getMonthName(date) {
                const parsed = parseDate(date);
                return new Intl.DateTimeFormat('en-US', { month: 'long' }).format(new Date(parsed));
            },

            // Sanitize HTML for HTML fields
            sanitize(html) {
                return sanitizeHtml(html);
            },
        };

        // Can't figure out a way to use custom delimiters, otherwise becomes annoying to use Twig
        preview = preview.replace(/\${/g, '{{').replace(/}/g, '}}');

        return h(compile(`<div>${preview}</div>`), props);
    },
};

</script>
