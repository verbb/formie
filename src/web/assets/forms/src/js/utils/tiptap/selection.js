import { Selection } from 'prosemirror-state';
import { equalNodeType, isNodeSelection } from './helpers';


export const findParentNode = predicate => ({ $from }) =>
    findParentNodeClosestToPos($from, predicate);

export const findParentNodeClosestToPos = ($pos, predicate) => {
    for (let i = $pos.depth; i > 0; i--) {
        const node = $pos.node(i);
        if (predicate(node)) {
            return {
                pos: i > 0 ? $pos.before(i) : 0,
                start: $pos.start(i),
                depth: i,
                node,
            };
        }
    }
};

export const findParentDomRef = (predicate, domAtPos) => selection => {
    const parent = findParentNode(predicate)(selection);
    if (parent) {
        return findDomRefAtPos(parent.pos, domAtPos);
    }
};

export const hasParentNode = predicate => selection => {
    return !!findParentNode(predicate)(selection);
};

export const findParentNodeOfType = nodeType => selection => {
    return findParentNode(node => equalNodeType(nodeType, node))(selection);
};

export const findParentNodeOfTypeClosestToPos = ($pos, nodeType) => {
    return findParentNodeClosestToPos($pos, node =>
        equalNodeType(nodeType, node)
    );
};

export const hasParentNodeOfType = nodeType => selection => {
    return hasParentNode(node => equalNodeType(nodeType, node))(selection);
};

export const findParentDomRefOfType = (nodeType, domAtPos) => selection => {
    return findParentDomRef(node => equalNodeType(nodeType, node), domAtPos)(
        selection
    );
};

export const findSelectedNodeOfType = nodeType => selection => {
    if (isNodeSelection(selection)) {
        const { node, $from } = selection;
        if (equalNodeType(nodeType, node)) {
            return { node, pos: $from.pos, depth: $from.depth };
        }
    }
};

export const findPositionOfNodeBefore = selection => {
    const { nodeBefore } = selection.$from;
    const maybeSelection = Selection.findFrom(selection.$from, -1);
    if (maybeSelection && nodeBefore) {
    // leaf node
        const parent = findParentNodeOfType(nodeBefore.type)(maybeSelection);
        if (parent) {
            return parent.pos;
        }
        return maybeSelection.$from.pos;
    }
};

export const findDomRefAtPos = (position, domAtPos) => {
    const dom = domAtPos(position);
    const node = dom.node.childNodes[dom.offset];

    if (dom.node.nodeType === Node.TEXT_NODE) {
        return dom.node.parentNode;
    }

    if (!node || node.nodeType === Node.TEXT_NODE) {
        return dom.node;
    }

    return node;
};
