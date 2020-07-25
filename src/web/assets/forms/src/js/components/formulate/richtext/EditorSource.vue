<template>
    <div class="fui-editor-source">
        {{ html }}
    </div>
</template>

<script>

export default {
    name: 'EditorSource',

    props: {
        html: {
            type: String,
            default: '',
        },
    },
};

</script>

<style lang="scss">

.fui-editor-source {
    outline: none;
    word-wrap: normal;
    overflow: hidden;
    padding: 6px 9px;
    min-height: 10rem;
    padding-top: 47px;
    border-radius: 3px;
    border: 1px solid rgba(96, 125, 159, 0.25);
    background-color: #fbfcfe;
    box-shadow: inset 0 1px 4px -1px rgba(96, 125, 159, 0.25);
    background-clip: padding-box;
}
    
</style>
