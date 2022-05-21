<script>
import { h } from 'vue';

export default {
    name: 'SlideUpDown',

    props: {
        active: {
            type: Boolean,
            default: true,
        },

        duration: {
            type: Number,
            default: 500,
        },

        tag: {
            type: String,
            default: 'div',
        },

        useHidden: {
            type: Boolean,
            default: true,
        },
    },

    emits: ['open-start', 'open-end', 'close-start', 'close-end'],

    data: () => {
        return {
            style: {},
            initial: false,
            hidden: false,
        };
    },

    computed: {
        el() {
            return this.$refs.container;
        },

        attrs() {
            const attrs = {
                'aria-hidden': !this.active,
                'aria-expanded': this.active,
            };

            if (this.useHidden) {
                attrs.hidden = this.hidden;
            }

            return attrs;
        },
    },

    watch: {
        active() {
            this.layout();
        },
    },

    mounted() {
        this.layout();
        this.initial = true;
    },

    created() {
        this.hidden = !this.active;
    },

    methods: {
        layout() {
            if (this.active) {
                this.hidden = false;
                this.$emit('open-start');

                if (this.initial) {
                    this.setHeight('0px', () => { return `${this.el.scrollHeight}px`; });
                }
            } else {
                this.$emit('close-start');
                this.setHeight(`${this.el.scrollHeight}px`, () => { return '0px'; });
            }
        },

        asap(callback) {
            if (!this.initial) {
                callback();
            } else {
                this.$nextTick(callback);
            }
        },

        setHeight(temp, afterRelayout) {
            this.style = { height: temp };

            this.asap(() => {
                // force relayout so the animation will run
                this.__ = this.el.scrollHeight;

                this.style = {
                    height: afterRelayout(),
                    overflow: 'hidden',
                    'transition-property': 'height',
                    'transition-timing-function': 'ease-out',
                    'transition-duration': `${this.duration}ms`,
                };
            });
        },

        onTransitionEnd(event) {
            // Don't do anything if the transition doesn't belong to the container
            if (event.target !== this.el) { return; }

            if (this.active) {
                this.style = {};
                this.$emit('open-end');
            } else {
                this.style = {
                    height: '0',
                    overflow: 'hidden',
                };

                this.hidden = true;
                this.$emit('close-end');
            }
        },
    },

    render() {
        return h(
            this.tag,
            {
                style: this.style,
                attrs: this.attrs,
                ref: 'container',
                on: { transitionend: this.onTransitionEnd },
            },
            this.$slots.default(),
        );
    },
};

</script>
