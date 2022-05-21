import { NodeSelection, Selection } from 'prosemirror-state';
import { Fragment } from 'prosemirror-model';
import { findParentNodeOfType, findPositionOfNodeBefore } from './selection';
import {
    cloneTr,
    isNodeSelection,
    replaceNodeAtPos,
    removeNodeAtPos,
    canInsert,
    isEmptyParagraph,
} from './helpers';

export const removeParentNodeOfType = nodeType => tr => {
    const parent = findParentNodeOfType(nodeType)(tr.selection);
    if (parent) {
        return removeNodeAtPos(parent.pos)(tr);
    }
    return tr;
};

export const replaceParentNodeOfType = (nodeType, content) => tr => {
    if (!Array.isArray(nodeType)) {
        nodeType = [nodeType];
    }
    for (let i = 0, count = nodeType.length; i < count; i++) {
        const parent = findParentNodeOfType(nodeType[i])(tr.selection);
        if (parent) {
            const newTr = replaceNodeAtPos(parent.pos, content)(tr);
            if (newTr !== tr) {
                return newTr;
            }
        }
    }
    return tr;
};

export const removeSelectedNode = tr => {
    if (isNodeSelection(tr.selection)) {
        const from = tr.selection.$from.pos;
        const to = tr.selection.$to.pos;
        return cloneTr(tr.delete(from, to));
    }
    return tr;
};

export const replaceSelectedNode = content => tr => {
    if (isNodeSelection(tr.selection)) {
        const { $from, $to } = tr.selection;
        if (
            (content instanceof Fragment &&
        $from.parent.canReplace($from.index(), $from.indexAfter(), content)) ||
      $from.parent.canReplaceWith(
          $from.index(),
          $from.indexAfter(),
          content.type
      )
        ) {
            return cloneTr(
                tr
                    .replaceWith($from.pos, $to.pos, content)
                // restore node selection
                    .setSelection(new NodeSelection(tr.doc.resolve($from.pos)))
            );
        }
    }
    return tr;
};

export const setTextSelection = (position, dir = 1) => tr => {
    const nextSelection = Selection.findFrom(tr.doc.resolve(position), dir, true);
    if (nextSelection) {
        return tr.setSelection(nextSelection);
    }
    return tr;
};

const isSelectableNode = node => node.type && node.type.spec.selectable;
const shouldSelectNode = node => isSelectableNode(node) && node.type.isLeaf;

const setSelection = (node, pos, tr) => {
    if (shouldSelectNode(node)) {
        return tr.setSelection(new NodeSelection(tr.doc.resolve(pos)));
    }
    return setTextSelection(pos)(tr);
};

export const safeInsert = (content, position, tryToReplace) => tr => {
    const hasPosition = typeof position === 'number';
    const { $from } = tr.selection;
    const $insertPos = hasPosition
        ? tr.doc.resolve(position)
        : isNodeSelection(tr.selection)
            ? tr.doc.resolve($from.pos + 1)
            : $from;
    const { parent } = $insertPos;

    // try to replace selected node
    if (isNodeSelection(tr.selection) && tryToReplace) {
        const oldTr = tr;
        tr = replaceSelectedNode(content)(tr);
        if (oldTr !== tr) {
            return tr;
        }
    }

    // try to replace an empty paragraph
    if (isEmptyParagraph(parent)) {
        const oldTr = tr;
        tr = replaceParentNodeOfType(parent.type, content)(tr);
        if (oldTr !== tr) {
            const pos = isSelectableNode(content)
                ? // for selectable node, selection position would be the position of the replaced parent
                $insertPos.before($insertPos.depth)
                : $insertPos.pos;
            return setSelection(content, pos, tr);
        }
    }

    // given node is allowed at the current cursor position
    if (canInsert($insertPos, content)) {
        tr.insert($insertPos.pos, content);
        const pos = hasPosition
            ? $insertPos.pos
            : isSelectableNode(content)
                ? // for atom nodes selection position after insertion is the previous pos
                tr.selection.$anchor.pos - 1
                : tr.selection.$anchor.pos;
        return cloneTr(setSelection(content, pos, tr));
    }

    // looking for a place in the doc where the node is allowed
    for (let i = $insertPos.depth; i > 0; i--) {
        const pos = $insertPos.after(i);
        const $pos = tr.doc.resolve(pos);
        if (canInsert($pos, content)) {
            tr.insert(pos, content);
            return cloneTr(setSelection(content, pos, tr));
        }
    }
    return tr;
};

export const setParentNodeMarkup = (nodeType, type, attrs, marks) => tr => {
    const parent = findParentNodeOfType(nodeType)(tr.selection);
    if (parent) {
        return cloneTr(
            tr.setNodeMarkup(
                parent.pos,
                type,
                Object.assign({}, parent.node.attrs, attrs),
                marks
            )
        );
    }
    return tr;
};

export const selectParentNodeOfType = nodeType => tr => {
    if (!isNodeSelection(tr.selection)) {
        const parent = findParentNodeOfType(nodeType)(tr.selection);
        if (parent) {
            return cloneTr(tr.setSelection(NodeSelection.create(tr.doc, parent.pos)));
        }
    }
    return tr;
};

export const removeNodeBefore = tr => {
    const position = findPositionOfNodeBefore(tr.selection);
    if (typeof position === 'number') {
        return removeNodeAtPos(position)(tr);
    }
    return tr;
};
