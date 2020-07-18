import { Node } from 'tiptap';
import VariableTag from './VariableTag.vue';

export default class VariableTagNode extends Node {
    get name() {
        return 'variableTag';
    }

    get view() {
        return VariableTag;
    }

    get schema() {
        return {
            attrs: {
                label: {},
                value: {},
            },
            group: 'inline',
            inline: true,
            selectable: true,
            draggable: true,
            atom: true,
            toDOM: node => ['variable-tag', {}, JSON.stringify(node.attrs)],
            parseDOM: [{
                tag: 'variable-tag',
                getAttrs: dom => JSON.parse(dom.innerHTML),
            }],
        };
    }

    commands({ type, schema }) {
        return attrs => (state, dispatch) => {
            const { selection } = state;
            const position = selection.$cursor ? selection.$cursor.pos : selection.$to.pos;
            const node = type.create(attrs);
            const transaction = state.tr.insert(position, node);

            dispatch(transaction);
        };
    }
}
