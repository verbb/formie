<template>
    <button v-tooltip="button.text" class="btn fui-toolbar-btn" :class="{ active }" @click.prevent="editor.commands[button.command](button.args)">
        <i v-if="button.icon" class="far" :class="'fa-' + button.icon"></i>
        <div v-if="button.html" class="flex flex-nowrap items-center" v-html="button.html"></div>
    </button>
</template>

<script>

export default {
    name: 'RichTextToolbarButton',

    props: {
        button: {
            type: Object,
            default: () => {},
        },

        active: {
            type: Boolean,
            default: false,
        },

        config: {
            type: Object,
            default: () => {},
        },

        field: {
            type: Object,
            default: () => {},
        },

        editor: {
            type: Object,
            default: () => {},
        },
    },
};

</script>

<style lang="scss">

.fui-toolbar-btn {
    background: transparent;
    color: #1c2e36;
    border-radius: 3px;
    padding-left: 4px;
    padding-right: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    text-align: center;
    height: 32px;
    width: 32px;
    margin: 0 2px;

    &:hover {
        background-color: #edf3fa;
    }

    sup {
        vertical-align: text-top;
    }
}

</style>
