import { empty } from '@formkit/utils';

const uniqueHandle = (node, args) => {
    const $store = node.config.rootConfig.formieConfig;

    if ($store) {
        const { editingField } = $store.state.formie;

        if (editingField) {
            return editingField.fieldHandles.indexOf(node.value) === -1;
        }
    }

    return true;
};

uniqueHandle.skipEmpty = false;

export default uniqueHandle;
