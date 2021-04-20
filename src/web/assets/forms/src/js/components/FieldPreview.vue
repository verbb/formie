<script>
import { mapState } from 'vuex';
import { parseDate } from '../utils/string';
import HtmlBlocks from './HtmlBlocks.vue';

export default {
    name: 'FieldPreview',

    components: {
        HtmlBlocks,
    },

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

    data() {
        return {
            templateRender: null,
        };
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

    methods: {
        // JS date handling is a pain, this is mostly for date-pickers
        parseDate(date) {
            return parseDate(date);
        },

        getMonthName(date) {
            const parsed = parseDate(date);
            return new Intl.DateTimeFormat('en-US', { month: 'long' }).format(new Date(parsed));
        },
    },

    render() {
        let { preview } = this.fieldType;

        // Can't figure out a way to use custom delimiters, otherwise becomes annoying to use Twig
        preview = preview.replace(/\${/g, '{{').replace(/}/g, '}}');

        const res = Vue.compile('<div>' + preview + '</div>');

        this.templateRender = res.render;

        this.$options.staticRenderFns = [];

        // Clean the cache of static elements
        // This is a cache with the results from the staticRenderFns
        this._staticTrees = [];

        // Fill it with the new staticRenderFns
        for (const fn of res.staticRenderFns) {
            this.$options.staticRenderFns.push(fn);
        }

        return this.templateRender();
    },
};

</script>
