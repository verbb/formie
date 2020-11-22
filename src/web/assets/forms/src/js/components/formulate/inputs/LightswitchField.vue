<template>
    <div class="ltr">
        <div
            :id="context.id"
            ref="container"
            tabindex="0"
            role="checkbox"
            class="lightswitch"
            :class="[{
                on: toBoolean(context.model),
                indeterminate: indeterminate,
                dragging: dragging,
            }, localClasses]"
            :aria-labelledby="`${context.id}-label`"
            :aria-checked="context.model ? 'true' : (indeterminate ? 'mixed' : 'false')"
            v-on="$listeners"
            @mouseup="onMouseUp"
            @keydown="onKeyDown"
        >
            <div ref="innerContainer" class="lightswitch-container">
                <div class="handle"></div>
            </div>

            <input v-model="context.model" type="hidden">
        </div>
    </div>
</template>

<script>
import { toBoolean } from '../../../utils/bool';

import FormulateInputMixin from '@braid/vue-formulate/src/FormulateInputMixin';

export default {
    name: 'LightswitchField',

    mixins: [FormulateInputMixin],

    props: {
        small: {
            type: Boolean,
            default: false,
        },

        indeterminate: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            dragger: null,
            dragging: false,
            innerStyle: {},
        };
    },

    computed: {
        localClasses() {
            return this.context.attributes.classes;
        },

        offMargin() {
            return this.small ? -10 : -12;
        },
    },

    mounted() {
        const { container } = this.$refs;

        this.$nextTick(() => {
            const lightswitch = $(container).data('lightswitch');
            if (lightswitch) {
                lightswitch.destroy();
            }

            this.dragger = new Garnish.BaseDrag(container, {
                axis: Garnish.X_AXIS,
                ignoreHandleSelector: null,
                onDragStart: this.onDragStart.bind(this),
                onDrag: this.onDrag.bind(this),
                onDragStop: this.onDragStop.bind(this),
            });
        });
    },

    beforeDestroy() {
        if (this.dragger) {
            this.dragger.destroy();
        }
    },

    methods: {
        turnOn() {
            this.context.model = true;
            this.indeterminate = false;
            this.dragging = true;

            const { innerContainer } = this.$refs;
            const animateCss = {
                [`margin-${Craft.left}`]: 0,
            };

            $(innerContainer).velocity('stop').velocity(animateCss, Craft.LightSwitch.animationDuration, this.onSettle.bind(this));
        },

        turnOff() {
            this.context.model = false;
            this.indeterminate = false;
            this.dragging = true;

            const { innerContainer } = this.$refs;
            const animateCss = {
                [`margin-${Craft.left}`]: this.offMargin,
            };

            $(innerContainer).velocity('stop').velocity(animateCss, Craft.LightSwitch.animationDuration, this.onSettle.bind(this));
        },

        toggle() {
            if (this.indeterminate || !this.context.model) {
                this.turnOn();
            } else {
                this.turnOff();
            }
        },

        onMouseUp() {
            // Was this a click?
            if (!this.dragger.dragging) {
                this.toggle();
            }
        },

        onKeyDown(event) {
            switch (event.keyCode) {
            case Garnish.SPACE_KEY: {
                this.toggle();
                event.preventDefault();
                break;
            }
            case Garnish.RIGHT_KEY: {
                if (Craft.orientation === 'ltr') {
                    this.turnOn();
                }
                else {
                    this.turnOff();
                }

                event.preventDefault();
                break;
            }
            case Garnish.LEFT_KEY: {
                if (Craft.orientation === 'ltr') {
                    this.turnOff();
                }
                else {
                    this.turnOn();
                }

                event.preventDefault();
                break;
            }
            }
        },

        onDragStart() {
            this.dragging = true;
            this.dragStartMargin = this.getMargin();
        },

        onDrag() {
            let margin;

            if (Craft.orientation === 'ltr') {
                margin = this.dragStartMargin + this.dragger.mouseDistX;
            } else {
                margin = this.dragStartMargin - this.dragger.mouseDistX;
            }

            if (margin < this.offMargin) {
                margin = this.offMargin;
            } else if (margin > 0) {
                margin = 0;
            }

            const { innerContainer } = this.$refs;
            $(innerContainer).css(`margin-${Craft.left}`, margin);
        },

        onDragStop() {
            const margin = this.getMargin();

            if (margin > (this.offMargin / 2)) {
                this.turnOn();
            } else {
                this.turnOff();
            }
        },

        onSettle() {
            this.dragging = false;
        },

        getMargin() {
            const { innerContainer } = this.$refs;
            const style = innerContainer.currentStyle || window.getComputedStyle(innerContainer);

            return parseInt(style.marginLeft);
        },

        toBoolean(value) {
            return toBoolean(value);
        },
    },

};

</script>

