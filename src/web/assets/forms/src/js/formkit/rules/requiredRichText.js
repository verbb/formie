import { empty } from '@formkit/utils';
import Document from '@tiptap/extension-document';
import Paragraph from '@tiptap/extension-paragraph';
import Text from '@tiptap/extension-text';
import { getSchema } from '@tiptap/core';
import { Node, DOMSerializer, Schema } from 'prosemirror-model';
import { createHTMLDocument } from 'hostic-dom';

const getHTMLFromFragment = (doc, schema) => {
    return DOMSerializer
        .fromSchema(schema)
        .serializeFragment(doc.content, {
            document: createHTMLDocument(),
        })
        .render();
};

const generateHTML = (doc, extensions) => {
    const schema = getSchema(extensions);
    const contentNode = Node.fromJSON(schema, doc);

    return getHTMLFromFragment(contentNode, schema);
};

const requiredRichText = (node, address) => {
    let value = node.at(`$root.${node.name}`)?.value;

    // Parse the Tiptap schema first before validating the content
    try {
        if (value) {
            value = generateHTML(JSON.parse(value), [
                Document,
                Paragraph,
                Text,
            ]);
        }
    } catch (e) {
        console.log(e);
        console.log(value);
    }

    return !empty(value);
};

requiredRichText.skipEmpty = false;

export default requiredRichText;
